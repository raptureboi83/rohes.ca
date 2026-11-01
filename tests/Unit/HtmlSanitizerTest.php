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

    public function test_it_formats_plain_text_bullet_lists_and_escapes_list_items(): void
    {
        $formatted = HtmlSanitizer::sanitizeItemBody(
            "Intro paragraph.\n\n- First point\n- <script>alert(1)</script>\n\nClosing paragraph."
        );

        $this->assertStringContainsString('<p>Intro paragraph.</p>', $formatted);
        $this->assertStringContainsString('<ul><li>First point</li><li>&lt;script&gt;alert(1)&lt;/script&gt;</li></ul>', $formatted);
        $this->assertStringContainsString('<p>Closing paragraph.</p>', $formatted);
        $this->assertStringNotContainsString('<script', $formatted);
    }

    public function test_it_sanitizes_existing_html_in_item_bodies(): void
    {
        $formatted = HtmlSanitizer::sanitizeItemBody(
            '<ul><li>First point</li></ul><p onclick="alert(1)">Safe</p>'
        );

        $this->assertStringContainsString('<ul><li>First point</li></ul>', $formatted);
        $this->assertStringContainsString('<p>Safe</p>', $formatted);
        $this->assertStringNotContainsString('onclick', $formatted);
    }

    public function test_it_keeps_bullets_with_blank_lines_in_one_list(): void
    {
        $formatted = HtmlSanitizer::sanitizeItemBody(
            "Intro paragraph.\n\n- First point\n\n- Second point\n\n- Third point\n\nClosing paragraph."
        );

        $this->assertStringContainsString('<p>Intro paragraph.</p>', $formatted);
        $this->assertStringContainsString(
            '<ul><li>First point</li><li>Second point</li><li>Third point</li></ul>',
            $formatted,
        );
        $this->assertStringContainsString('<p>Closing paragraph.</p>', $formatted);
        $this->assertSame(1, substr_count($formatted, '<ul>'));
    }
}
