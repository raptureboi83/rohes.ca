<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\Page;
use App\Models\Section;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The dashboard previously shipped with plain English instruction text pasted
 * into the Blade markup and an <a> element wrapping three more <a> elements.
 * The page returned HTTP 200 the whole time, so every existing status-code
 * smoke test passed and nobody noticed. These assertions look at the rendered
 * structure instead of the status code.
 */
class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    /**
     * Find structurally invalid <a> usage in the raw markup.
     *
     * This deliberately does NOT use DOMDocument. The parser silently closes an
     * outer <a> when it meets an inner one, which is precisely the browser
     * behaviour that made this bug invisible — so parsing first would hide the
     * very thing being tested for. The raw tag stream is walked instead.
     *
     * @return array<int, string> human-readable descriptions of each problem
     */
    protected function anchorProblems(string $html): array
    {
        // Strip comments and script/style bodies so their contents cannot
        // produce phantom tags.
        $html = preg_replace('~<!--.*?-->~s', '', $html);
        $html = preg_replace('~<(script|style)\b[^>]*>.*?</\1>~si', '', $html);

        preg_match_all('~<a\b[^>]*>|</a\s*>~i', $html, $matches);

        $depth = 0;
        $problems = [];
        foreach ($matches[0] as $tag) {
            if (str_starts_with(strtolower($tag), '</a')) {
                $depth--;
                if ($depth < 0) {
                    $problems[] = 'stray closing </a> with no opening <a>';
                    $depth = 0;
                }
            } else {
                if ($depth > 0) {
                    $problems[] = 'an <a> opened while already inside another <a>';
                }
                $depth++;
            }
        }

        if ($depth > 0) {
            $problems[] = "{$depth} unclosed <a> element(s)";
        }

        return $problems;
    }

    public function test_dashboard_renders_without_nested_anchors(): void
    {
        Page::create(['title' => 'Home', 'slug' => 'home', 'is_home' => true, 'is_active' => true]);

        $response = $this->actingAs($this->admin())->get('/admin');

        $response->assertOk();
        $this->assertSame(
            [],
            $this->anchorProblems($response->getContent()),
            'Dashboard has structurally invalid anchor markup.'
        );
    }

    public function test_anchor_problem_detector_actually_detects_nesting(): void
    {
        // Guards the guard: a helper that cannot fail is worse than no helper,
        // because it reports the markup as clean when it is not.
        $this->assertNotEmpty($this->anchorProblems('<a href="#"><a href="#">x</a></a>'));
        $this->assertNotEmpty($this->anchorProblems('<a href="#">x</a></a>'));
        $this->assertNotEmpty($this->anchorProblems('<a href="#">x'));
        $this->assertSame([], $this->anchorProblems('<a href="#">x</a><a href="#">y</a>'));
        $this->assertSame([], $this->anchorProblems('<p>plain text</p>'));
    }

    public function test_dashboard_does_not_leak_instruction_text_into_the_page(): void
    {
        Page::create(['title' => 'Home', 'slug' => 'home', 'is_home' => true, 'is_active' => true]);

        $response = $this->actingAs($this->admin())->get('/admin');

        $response->assertOk();
        $response->assertDontSee('Choose Edit content to change');
    }

    public function test_dashboard_offers_an_edit_action_for_every_page(): void
    {
        $home = Page::create(['title' => 'Home', 'slug' => 'home', 'is_home' => true, 'is_active' => true]);
        $contact = Page::create(['title' => 'Contact ROHES', 'slug' => 'contact', 'is_active' => true]);

        $response = $this->actingAs($this->admin())->get('/admin');

        $response->assertOk();
        $response->assertSee(route('admin.pages.edit', $home));
        $response->assertSee(route('admin.pages.edit', $contact));
    }

    public function test_dashboard_offers_a_direct_home_page_edit_action(): void
    {
        $home = Page::create(['title' => 'Home', 'slug' => 'home', 'is_home' => true, 'is_active' => true]);

        $response = $this->actingAs($this->admin())->get('/admin');

        $response->assertOk();
        $response->assertSee('Edit home page');
        $response->assertSee(route('admin.pages.edit', $home));
        $response->assertSee('Choose a page to change its words, photos or links.');
    }

    public function test_website_preview_is_separate_at_the_bottom_of_the_sidebar(): void
    {
        Page::create(['title' => 'Home', 'slug' => 'home', 'is_home' => true, 'is_active' => true]);

        $response = $this->actingAs($this->admin())->get('/admin');

        $response->assertOk();
        $response->assertSeeInOrder([
            '<nav class="admin-nav">',
            '<div class="admin-side__preview">',
            'Preview website &nearr;',
            '<div class="admin-side__foot">',
        ], false);
        $response->assertDontSee('class="abtn abtn--ghost">Preview website', false);
    }

    public function test_dashboard_does_not_call_settings_driven_content_empty(): void
    {
        $home = Page::create(['title' => 'Home', 'slug' => 'home', 'is_home' => true, 'is_active' => true]);
        $home->sections()->create([
            'title' => 'Contact us',
            'slug' => 'cta',
            'template_key' => 'cta_banner',
            'intro' => 'Talk with our team.',
            'settings' => ['cta_label' => 'Contact us', 'cta_url' => '/contact'],
        ]);

        $response = $this->actingAs($this->admin())->get('/admin');

        $response->assertOk();
        $response->assertDontSee('visible section(s) have no content items');
    }

    public function test_dashboard_warns_when_a_section_layout_cannot_be_rendered(): void
    {
        $home = Page::create(['title' => 'Home', 'slug' => 'home', 'is_home' => true, 'is_active' => true]);
        Section::create([
            'page_id' => $home->id,
            'title' => 'Broken section',
            'slug' => 'broken',
            'template_key' => 'a_layout_that_does_not_exist',
            'is_visible' => true,
        ]);

        $response = $this->actingAs($this->admin())->get('/admin');

        $response->assertOk();
        $response->assertSee('use a display style that is no longer available');
    }

    public function test_dashboard_warns_when_no_home_page_is_set(): void
    {
        Page::create(['title' => 'Orphan', 'slug' => 'orphan', 'is_active' => true]);

        $response = $this->actingAs($this->admin())->get('/admin');

        $response->assertOk();
        $response->assertSee('The homepage is not set');
    }

    public function test_dashboard_warns_when_no_office_address_is_set(): void
    {
        Page::create(['title' => 'Home', 'slug' => 'home', 'is_home' => true, 'is_active' => true]);

        $response = $this->actingAs($this->admin())->get('/admin');

        $response->assertOk();
        $response->assertSee('No business address is set');
    }

    public function test_dashboard_address_warning_clears_once_an_address_is_set(): void
    {
        Page::create(['title' => 'Home', 'slug' => 'home', 'is_home' => true, 'is_active' => true]);
        Setting::create(['key' => 'contact.address', 'value' => '10367 County Road 10']);

        $response = $this->actingAs($this->admin())->get('/admin');

        $response->assertOk();
        $response->assertDontSee('No office address is set');
    }

    public function test_unread_enquiries_are_surfaced_and_hidden_once_read(): void
    {
        Page::create(['title' => 'Home', 'slug' => 'home', 'is_home' => true, 'is_active' => true]);

        Message::create([
            'name' => 'Alex Reader',
            'email' => 'alex@example.com',
            'subject' => 'Already handled',
            'message' => 'This message has been read.',
            'is_read' => true,
        ]);

        $message = Message::create([
            'name' => 'Dana Fields',
            'email' => 'dana@example.com',
            'subject' => 'Storage capacity',
            'message' => 'Do you have capacity for 800 m3?',
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->admin())->get('/admin');
        $response->assertOk();
        $response->assertSee('Messages to read');
        $response->assertSee(route('admin.messages.show', $message));
        $response->assertDontSee('Already handled');

        $message->update(['is_read' => true]);

        $this->actingAs($this->admin())->get('/admin')
            ->assertOk()
            ->assertDontSee('Messages to read');
    }

    public function test_dashboard_requires_an_administrator(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->get('/admin')
            ->assertForbidden();
    }
}