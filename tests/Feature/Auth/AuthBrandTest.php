<?php

namespace Tests\Feature\Auth;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Guards the sign-in screen's brand lockup.
 *
 * Staging shipped name "Region of Huronia Environmental Services (2013) Ltd."
 * with tagline "Environmental Services". The lockup rendered the full legal name
 * beside the logo inside a 26rem column, so it wrapped to three ragged lines and
 * said "Environmental Services" twice. Two rounds of CSS polish could not touch
 * that, because the cause was the content, not the styling.
 */
class AuthBrandTest extends TestCase
{
    use RefreshDatabase;

    /** Seed settings and drop the site_settings cache the helper reads. */
    private function setSettings(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::create(['key' => $key, 'group' => 'branding', 'value' => $value]);
        }
        Cache::forget('site_settings');
    }

    /**
     * Isolate the brand lockup. The page <title> legitimately uses the full
     * legal name, so whole-page assertions cannot detect this bug.
     */
    private function brandBlock(string $html): string
    {
        $this->assertSame(
            1,
            preg_match('/<a\b[^>]*class="auth-brand"[^>]*>(.*?)<\/a>/s', $html, $m),
            'Expected exactly one .auth-brand lockup on the page.'
        );

        return $m[1];
    }

    public function test_lockup_uses_the_short_name_not_the_full_legal_name(): void
    {
        $this->setSettings([
            'site.short_name' => 'ROHES',
            'site.name' => 'Region of Huronia Environmental Services (2013) Ltd.',
            'site.tagline' => 'Environmental Services',
        ]);

        $brand = $this->brandBlock((string) $this->get('/login')->getContent());

        $this->assertStringContainsString('ROHES', $brand);
        $this->assertStringNotContainsString('(2013) Ltd.', $brand);
        $this->assertStringNotContainsString('Region of Huronia', $brand);
    }

    public function test_lockup_does_not_repeat_the_name_in_the_tagline(): void
    {
        $this->setSettings([
            'site.short_name' => 'ROHES',
            'site.name' => 'Region of Huronia Environmental Services (2013) Ltd.',
            'site.tagline' => 'Environmental Services',
        ]);

        $brand = $this->brandBlock((string) $this->get('/login')->getContent());

        // "Environmental Services" may appear once as the tagline, never as a
        // repeat of the name beside it.
        $this->assertSame(
            1,
            substr_count($brand, 'Environmental Services'),
            'The tagline must not repeat the brand name.'
        );
    }

    public function test_tagline_is_suppressed_when_it_duplicates_the_short_name(): void
    {
        $this->setSettings([
            'site.short_name' => 'Environmental Services',
            'site.name' => 'Environmental Services',
            'site.tagline' => 'Environmental Services',
        ]);

        $brand = $this->brandBlock((string) $this->get('/login')->getContent());

        $this->assertSame(
            1,
            substr_count($brand, 'Environmental Services'),
            'A tagline identical to the name must be dropped, not stacked.'
        );
    }

    public function test_login_screen_declares_autofocus_exactly_once(): void
    {
        $this->setSettings([
            'site.short_name' => 'ROHES',
            'site.name' => 'Region of Huronia Environmental Services (2013) Ltd.',
            'site.tagline' => 'Environmental Services',
        ]);

        $html = (string) $this->get('/login')->getContent();

        // Regression: the field and a wrapper both carried autofocus, which is
        // invalid HTML and left keyboard focus ambiguous.
        $this->assertSame(1, substr_count($html, 'autofocus'));
    }

    public function test_primary_action_precedes_the_secondary_link(): void
    {
        $this->setSettings([
            'site.short_name' => 'ROHES',
            'site.name' => 'Region of Huronia Environmental Services (2013) Ltd.',
            'site.tagline' => 'Environmental Services',
        ]);

        $html = (string) $this->get('/login')->getContent();

        $this->assertSame(
            1,
            preg_match('/<div class="auth-actions">(.*?)<\/div>/s', $html, $m),
            'Expected exactly one .auth-actions block.'
        );

        $button = strpos($m[1], '<button');
        $link = strpos($m[1], 'Forgotten your password?');

        $this->assertNotFalse($button, 'Submit button missing from .auth-actions.');
        $this->assertNotFalse($link, 'Password reset link missing from .auth-actions.');
        $this->assertLessThan(
            $link,
            $button,
            'The primary submit button must come before the secondary link.'
        );
    }

    public function test_every_auth_screen_puts_its_primary_action_first(): void
    {
        $this->setSettings([
            'site.short_name' => 'ROHES',
            'site.name' => 'Region of Huronia Environmental Services (2013) Ltd.',
            'site.tagline' => 'Environmental Services',
        ]);

        $screens = [
            '/login',
            '/forgot-password',
        ];

        foreach ($screens as $path) {
            $html = (string) $this->get($path)->getContent();

            $this->assertSame(
                1,
                preg_match('/<div class="auth-actions">(.*?)<\/div>/s', $html, $m),
                "Expected exactly one .auth-actions block on {$path}."
            );

            $button = strpos($m[1], '<button');
            $link = strpos($m[1], '<a href');

            $this->assertNotFalse($button, "Submit button missing on {$path}.");
            $this->assertNotFalse($link, "Secondary link missing on {$path}.");
            $this->assertLessThan(
                $link,
                $button,
                "The primary submit button must come before the secondary link on {$path}."
            );
        }
    }
}
