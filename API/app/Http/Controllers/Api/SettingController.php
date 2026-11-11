<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Core\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Admin › Generals › Settings: company-wide values from the `settings` table.
 *
 * Secret values (passwords, tokens) are write-only: they are never sent to
 * the browser, only whether one is set. An empty value on save keeps them.
 */
class SettingController extends Controller
{
    // Keys whose value must never leave the server
    private const SECRET_PATTERN = '/password|token|secret/i';

    // Cache keys that hold settings elsewhere (AuthController / UserController)
    private const CACHE_KEYS = ['system_setting_value', 'setting_company_name', 'setting_default_password'];

    public function all()
    {
        $settings = Setting::query()->orderBy('id')->get()->map(function ($s) {
            $secret = $this->isSecret($s->key);

            return [
                'id' => $s->id,
                'key' => $s->key,
                'name' => $s->name,
                'type' => $s->type,
                'enum_value' => $s->enum_value ? json_decode($s->enum_value, true) : null,
                'is_secret' => $secret,
                // Secrets: only whether a value exists
                'value' => $secret ? null : $s->value,
                'has_value' => $s->value !== null && $s->value !== '',
            ];
        });

        return response()->json(['status' => true, 'data' => $settings]);
    }

    /**
     * Save changed values: { values: { key: value, ... } }.
     */
    public function update(Request $request)
    {
        $request->validate(['values' => 'required|array']);

        DB::beginTransaction();
        try {
            $settings = Setting::query()->whereIn('key', array_keys($request->values))->get()->keyBy('key');
            $changed = [];

            foreach ($request->values as $key => $value) {
                $setting = $settings->get($key);
                if (!$setting) continue;

                // Empty secret = keep the current one
                if ($this->isSecret($key) && ($value === null || $value === '')) continue;

                $setting->value = $this->normalize($setting, $value);
                $setting->save();
                $changed[] = $key;
            }

            DB::commit();

            // Values cached by the login alert / user screens
            foreach (self::CACHE_KEYS as $cacheKey) Cache::forget($cacheKey);

            activity('settings')
                ->withProperties(['keys' => $changed]) // keys only, never values
                ->log('Settings updated');

            return response()->json([
                'status' => true,
                'message' => 'Settings saved!',
                'data' => ['changed' => $changed],
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            $code = $th instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $th->getStatusCode() : 500;
            return response()->json(['status' => false, 'message' => $th->getMessage()], $code);
        }
    }

    private function isSecret(string $key): bool
    {
        return (bool)preg_match(self::SECRET_PATTERN, $key);
    }

    /**
     * Check the value against the setting's type and turn it into the text
     * stored in `value`.
     */
    private function normalize(Setting $setting, $value): string
    {
        $label = $setting->name ?: $setting->key;
        $value = is_string($value) ? trim($value) : $value;

        switch ($setting->type) {
            case 'int':
                if (!is_numeric($value) || (int)$value != $value) abort(422, "{$label}: must be a whole number");
                return (string)(int)$value;
            case 'decimal':
                if (!is_numeric($value)) abort(422, "{$label}: must be a number");
                return (string)$value;
            case 'bool':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
            case 'date':
                if (!strtotime((string)$value)) abort(422, "{$label}: must be a date");
                return date('Y-m-d', strtotime($value));
            case 'time':
                if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', (string)$value)) abort(422, "{$label}: must be a time (HH:MM)");
                return (string)$value;
            case 'json':
                $encoded = is_string($value) ? $value : json_encode($value);
                json_decode($encoded);
                if (json_last_error() !== JSON_ERROR_NONE) abort(422, "{$label}: must be valid JSON");
                return $encoded;
            case 'enum':
                $options = json_decode((string)$setting->enum_value, true) ?: [];
                if (!in_array($value, $options, true)) abort(422, "{$label}: must be one of " . implode(', ', $options));
                return (string)$value;
            default: // string
                if ((string)$value === '') abort(422, "{$label}: is required");
                if (mb_strlen((string)$value) > 2000) abort(422, "{$label}: is too long");
                return (string)$value;
        }
    }
}
