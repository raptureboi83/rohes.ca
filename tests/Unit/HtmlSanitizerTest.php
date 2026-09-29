<?php

namespace Tests\Unit;

use App\Support\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerTest extends TestCase
{
    public function test_it_keeps_basic_formatting_and_removes_active_content(): void
    {
        $html = '<p onclick="alert(1)">Safe <strong>formatting</strong>'
            .'<script>alert(2)</script></p>'
            .'<a href="javascript:alert(3)">Unsafe link</a>';

        $sanitized = HtmlSanitizer::sanitize($html);

        $this->assertStringContainsString('<p>Safe <strong>formatting</strong></p>', $sanitized);
        $this->assertStringContainsString('<a>Unsafe link</a>', $sanitized);
        $this->assertStringNotContainsString('onclick', $sanitized);
        $this->assertStringNotContainsString('javascript:', $sanitized);
        $this->assertStringNotContainsString('<script', $sanitized);
    }

    public function test_it_keeps_only_approved_embeds(): void
    {
        $safe = HtmlSanitizer::sanitize(
            '<iframe src="https://www.google.com/maps/embed?pb=example" width="600" height="450" allowfullscreen></iframe>'
        );
        $unsafe = HtmlSanitizer::sanitize(
            '<iframe src="https://evil.example/embed" onload="alert(1)"></iframe>'
        );

        $this->assertStringContainsString('https://www.google.com/maps/embed?pb=example', $safe);
        $this->assertStringContainsString('loading="lazy"', $safe);
        $this->assertStringNotContainsString('onload', $safe);
        $this->assertSame('', $unsafe);
    }

    public function test_it_restricts_images_and_external_link_targets(): void
    {
        $html = '<a href="https://example.com" target="_blank" onclick="alert(1)">Link</a>'
            .'<img src="/media/uploads/photo.jpg" alt="Photo" onerror="alert(2)">'
            .'<img src="data:text/html,evil" alt="Bad">';

        $sanitized = HtmlSanitizer::sanitize($html);

        $this->assertStringContainsString('rel="noopener noreferrer"', $sanitized);
        $this->assertStringContainsString('src="/media/uploads/photo.jpg"', $sanitized);
        $this->assertStringNotContainsString('onerror', $sanitized);
        $this->assertStringNotContainsString('data:text/html', $sanitized);
        $this->assertSame(1, substr_count($sanitized, '<img'));
    }
}