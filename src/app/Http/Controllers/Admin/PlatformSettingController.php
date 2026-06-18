<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Settings\Models\PlatformSetting;
use App\Modules\Settings\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class PlatformSettingController extends Controller
{
    public function __construct(private readonly SettingsService $settings) {}

    public function index(): View
    {
        $groups = PlatformSetting::query()
            ->orderBy('group')
            ->orderBy('key')
            ->get()
            ->groupBy('group');

        return view('admin.settings.index', compact('groups'));
    }

    public function update(Request $request): RedirectResponse
    {
        $settings = PlatformSetting::query()->get();

        // Setting keys contain dots (e.g. "commission.default_rate"), which clash
        // with Laravel's dot-notation rule paths — so read the raw array (literal
        // keys preserved) and validate each value by its declared type manually.
        $raw       = $request->input('settings', []);
        $validator = Validator::make([], []);
        $values    = [];

        foreach ($settings as $setting) {
            $key = $setting->key;

            // Unchecked checkboxes are absent from the payload — coerce to false.
            if ($setting->type === 'boolean') {
                $values[$key] = (bool) ($raw[$key] ?? false);
                continue;
            }

            if (! array_key_exists($key, $raw)) {
                continue;
            }

            $value = $raw[$key];
            $error = $this->validateValue($setting->type, $value);

            if ($error !== null) {
                $validator->errors()->add('settings.' . $key, "The value for {$key} {$error}.");
            } else {
                $values[$key] = $value;
            }
        }

        if ($validator->errors()->isNotEmpty()) {
            return back()->withErrors($validator)->withInput();
        }

        $this->settings->updateMany($values, $request->user()->id);

        return redirect()
            ->route('admin.settings.index')
            ->with('status', 'Platform settings updated.');
    }

    private function validateValue(string $type, mixed $value): ?string
    {
        if (in_array($type, ['integer', 'decimal'], true)) {
            if (! is_numeric($value)) {
                return $type === 'integer' ? 'must be a whole number.' : 'must be a number.';
            }
            if ((float) $value < 0) {
                return 'must be zero or greater.';
            }
            if ($type === 'integer' && (int) $value != $value) {
                return 'must be a whole number.';
            }
        }

        if ($type === 'string' && ! is_string($value)) {
            return 'must be text.';
        }

        return null;
    }
}
