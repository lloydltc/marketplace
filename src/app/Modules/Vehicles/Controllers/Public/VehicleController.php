<?php

namespace App\Modules\Vehicles\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Repositories\VehicleMakeRepositoryInterface;
use App\Modules\Vehicles\Repositories\VehicleRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VehicleController extends Controller
{
    public function __construct(
        private readonly VehicleRepositoryInterface $repository,
        private readonly VehicleMakeRepositoryInterface $makeRepository
    ) {}

    public function index(Request $request): View
    {
        $vehicles = $this->repository->paginatePublic([
            'vehicle_type' => $request->input('vehicle_type'),
            'search'       => $request->input('search'),
            'make_id'      => $request->input('make_id'),
            'model_id'     => $request->input('model_id'),
            'year_min'     => $request->input('year_min'),
            'year_max'     => $request->input('year_max'),
            'mileage_max'  => $request->input('mileage_max'),
            'min_price'    => $request->input('min_price'),
            'max_price'    => $request->input('max_price'),
            'body_type'    => $request->input('body_type'),
            'transmission' => $request->input('transmission'),
            'fuel_type'    => $request->input('fuel_type'),
            'condition'    => $request->input('condition'),
            'features'     => $request->input('features', []),
            'sort'         => $request->input('sort', 'latest'),
        ]);

        $makes = $this->makeRepository->allWithModels();
        $filterableFeatures = \App\Modules\Vehicles\Models\FeatureDefinition::filterable()->ordered()->get();

        return view('vehicles.index', compact('vehicles', 'makes', 'filterableFeatures'));
    }

    public function show(Vehicle $vehicle): View
    {
        abort_unless($vehicle->isActive(), 404);

        $vehicle->load([
            'make', 'vehicleModel', 'vendor', 'seller',
            'images' => fn ($q) => $q->whereNotNull('processed_at')->orderBy('display_order'),
            'featureValues.definition',
        ]);

        // H5: record a (deduped, bot-filtered) detail view for seller analytics.
        app(\App\Modules\Analytics\Services\AnalyticsService::class)->record('detail_view', $vehicle, request());

        return view('vehicles.show', compact('vehicle'));
    }

    /** H3: download all of a listing's (watermarked) images as a zip. */
    public function downloadImages(Vehicle $vehicle): BinaryFileResponse
    {
        abort_unless($vehicle->isActive(), 404);

        $images = $vehicle->images()->whereNotNull('processed_at')->orderBy('display_order')->get();
        abort_if($images->isEmpty(), 404);

        $slug = Str::slug($vehicle->displayTitle()) ?: 'listing';
        $tmp  = tempnam(sys_get_temp_dir(), 'sd_imgs') . '.zip';

        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        foreach ($images as $i => $img) {
            $disk = Storage::disk($img->disk);
            $path = $img->medium_path ?: $img->original_path; // serve the watermarked derivative
            if ($disk->exists($path)) {
                $zip->addFromString(sprintf('%s-%02d.jpg', $slug, $i + 1), $disk->get($path));
            }
        }
        $zip->close();

        return response()->download($tmp, "{$slug}-images.zip")->deleteFileAfterSend(true);
    }
}
