<?php

namespace App\Modules\History\Services;

use App\Modules\History\Models\HistoryDataSource;
use App\Modules\History\Models\HistoryReport;
use App\Modules\Settings\Services\SettingsService;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Support\Facades\DB;

/**
 * HR2: assembles a vehicle history report by running each registered data source.
 * Usable sources (live/manual) produce sections via their adapter; gated sources
 * produce an honest "unavailable" section. Manual entries (e.g. dealer service
 * records) are preserved across re-assembly. Never fabricates.
 */
class ReportAssembler
{
    public function __construct(private readonly SettingsService $settings) {}

    public function assembleFor(Vehicle $vehicle, ?string $userId = null): HistoryReport
    {
        $report = HistoryReport::firstOrNew([
            'vehicle_id'   => $vehicle->id,
            'requested_by' => $userId,
        ]);

        if (! $report->exists) {
            $report->vin = $vehicle->vin;
            $report->status = 'draft';
        }

        // Price is fixed at purchase time; (re)set only while still unpurchased.
        if (! $report->isPurchased()) {
            $report->price_minor = (int) round($this->settings->getDecimal('history.report_price_usd', (float) config('history.report_price_usd', 5)) * 100);
        }
        $report->save();

        $this->rebuildSections($report, $vehicle);

        return $report->fresh('sections');
    }

    private function rebuildSections(HistoryReport $report, Vehicle $vehicle): void
    {
        // Preserve manually-entered service records across re-assembly.
        $manualService = $report->sections()->where('type', 'service')->value('data');

        DB::transaction(function () use ($report, $vehicle, $manualService) {
            $report->sections()->delete();

            $order = 0;
            foreach (HistoryDataSource::orderBy('created_at')->get() as $source) {
                $section = $this->sectionFor($source, $vehicle);
                $section['source'] = $source->key;
                $section['sort_order'] = $order++;

                // Carry forward existing manual service records.
                if ($source->type === 'service' && ! empty($manualService['records'])) {
                    $section['data'] = ['records' => $manualService['records']];
                    $section['availability'] = 'manual';
                }

                $report->sections()->create($section);
            }
        });
    }

    /** @return array<string, mixed> */
    private function sectionFor(HistoryDataSource $source, Vehicle $vehicle): array
    {
        // Gated / no adapter → honest unavailable section.
        if ($source->isUnavailable() || empty($source->adapter) || ! class_exists($source->adapter)) {
            return [
                'type'         => $source->type,
                'availability' => 'unavailable',
                'data'         => null,
                'confidence'   => 'low',
                'provenance'   => 'Not available — pending data partnership',
                'retrieved_at' => null,
            ];
        }

        /** @var \App\Modules\History\Adapters\HistoryDataSourceAdapter $adapter */
        $adapter = app($source->adapter);

        return $adapter->assemble($vehicle);
    }
}
