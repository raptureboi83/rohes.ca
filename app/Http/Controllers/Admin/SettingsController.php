<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\InvalidatesContentCache;
use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SettingsController extends Controller
{
    use InvalidatesContentCache;

    public function index(): View
    {
        $settings = Setting::query()->orderBy('group')->orderBy('key')->get()->keyBy('key');

        return view('admin.settings.index', [
            'settings' => $settings,
            'groups' => Setting::GROUPS,
            'availableImages' => Media::query()->images()->orderBy('original_name')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'settings' => ['nullable', 'array'],
            'settings.*' => ['nullable', 'string', 'max:2000'],
        ]);

        $submitted = $data['settings'] ?? [];

        // Only update keys the client actually sent. This keeps the handler
        // from being able to write arbitrary keys, and means the settings
        // form defines which fields exist.
        foreach ($submitted as $key => $value) {
            if (! preg_match('/^[a-z0-9_\.]+$/', (string) $key)) {
                continue;
            }

            $value = is_string($value) ? trim($value) : $value;

            if (str_starts_with($key, 'social.')) {
                $network = substr($key, strlen('social.'));
                if (! array_key_exists($network, Setting::SOCIAL_NETWORKS)) {
                    continue;
                }

                if ($value !== '') {
                    $parts = parse_url($value);
                    if (strlen($value) > 255
                        || ! is_array($parts)
                        || strtolower($parts['scheme'] ?? '') !== 'https'
                        || empty($parts['host'])
                        || isset($parts['user'])
                        || isset($parts['pass'])) {
                        throw ValidationException::withMessages([
                            'settings.'.$key => 'Enter a full HTTPS profile link.',
                        ]);
                    }
                }
            }

            if ($value === '') {
                // Blank removes the override so the default applies again.
                Setting::query()->where('key', $key)->delete();

                continue;
            }

            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => $this->groupFor($key)],
            );
        }

        $this->forgetContentCache();

        return back()->with('success', 'Settings saved.');
    }

    /**
     * Derive the settings group from the key prefix. Keeping this
     * deterministic means the grouping stays correct even if a new
     * setting is added to the form without touching the controller.
     */
    protected function groupFor(string $key): string
    {
        $prefix = explode('.', $key)[0];

        return array_key_exists($prefix, Setting::GROUPS) ? $prefix : 'general';
    }
}
