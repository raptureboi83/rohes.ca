<?php

namespace Tests\Feature;

use App\Http\Controllers\ContactController;
use App\Models\Media;
use App\Models\Page;
use App\Models\Section;
use App\Models\SectionItem;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\LegacyContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_contact_content_renders_and_survives_reseeding(): void
    {
        $this->seed(LegacyContentSeeder::class);

        $contactPage = Page::query()->where('slug', 'contact')->firstOrFail();
        $contactSection = $contactPage->sections()->where('slug', 'contact-form')->firstOrFail();
        $contactSection->update(['title' => 'Talk with our team']);

        $this->seed(LegacyContentSeeder::class);

        $this->assertSame('Talk with our team', $contactSection->fresh()->title);
        $this->assertDatabaseHas('section_items', [
            'title' => 'ROHES construction services card',
            'link_url' => 'http://www.regionofhuronia.com/images/4462%20ROHES%20construct%20card1.pdf',
        ]);

        $response = $this->get('/contact')->assertOk();
        $response->assertSee('Talk with our team');
        $response->assertSee('Have a salesperson contact me');
        $this->assertSame(1, preg_match_all('~<h1\b~i', $response->getContent()));

        $compatibilityHtml = app(ContactController::class)->show()->render();
        $this->assertStringContainsString('Talk with our team', $compatibilityHtml);
    }

    public function test_admin_can_edit_settings_for_a_section_template(): void
    {
        $page = Page::create([
            'title' => 'Home',
            'slug' => 'home',
            'is_home' => true,
            'is_active' => true,
        ]);
        $section = $page->sections()->create([
            'title' => 'Main banner',
            'slug' => 'hero',
            'template_key' => 'hero',
            'settings' => ['cta_label' => 'Old label'],
        ]);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->put(route('admin.sections.update', $section), [
                'title' => 'Main banner',
                'slug' => 'hero',
                'template_key' => 'hero',
                'anchor_id' => '',
                'eyebrow' => '',
                'intro' => '',
                'sort_order' => 0,
                'is_visible' => 1,
                'settings' => [
                    'cta_label' => 'Request a quote',
                    'cta_url' => '/contact',
                    'secondary_label' => 'Services',
                    'secondary_url' => '/services',
                    'unrecognized' => 'must not be stored',
                ],
            ])
            ->assertRedirect(route('admin.pages.sections.index', $page));

        $this->assertSame([
            'cta_label' => 'Request a quote',
            'cta_url' => '/contact',
            'secondary_label' => 'Services',
            'secondary_url' => '/services',
        ], $section->fresh()->settings);
    }

    public function test_page_social_image_is_used_in_open_graph_metadata(): void
    {
        Page::create([
            'title' => 'About',
            'slug' => 'about',
            'meta_image' => '/media/legacy/logo.jpeg',
            'is_active' => true,
        ]);

        $this->get('/about')
            ->assertOk()
            ->assertSee('<meta property="og:image" content="'.url('/media/legacy/logo.jpeg').'">', false);
    }

    public function test_page_edit_exposes_inline_item_content_images_and_metadata(): void
    {
        $page = Page::create([
            'title' => 'News',
            'slug' => 'news',
            'is_active' => true,
        ]);
        $section = $page->sections()->create([
            'title' => 'Latest updates',
            'slug' => 'latest',
            'template_key' => 'news_list',
        ]);
        $item = $section->items()->create([
            'item_type' => 'news',
            'title' => 'Example news item',
            'body' => 'Editable description.',
            'image_path' => '/media/legacy/editor-photo.jpg',
            'data' => ['date' => 'Company news', 'is_archived' => true, 'preserve_me' => 'retained'],
        ]);
        Media::create([
            'disk' => 'media',
            'folder' => 'legacy',
            'filename' => 'editor-photo.jpg',
            'original_name' => 'Editor photo.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 10,
            'width' => 10,
            'height' => 10,
        ]);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.pages.index'))
            ->assertOk()
            ->assertSee('Edit page')
            ->assertSee(route('admin.pages.edit', $page), false);

        $this->actingAs($admin)
            ->get(route('admin.pages.edit', $page))
            ->assertOk()
            ->assertSee('Change News')
            ->assertSee("Change this part's text and layout", false)
            ->assertSee('Add another entry')
            ->assertSee('Example news item')
            ->assertSee('admin-image-preview', false)
            ->assertSee('admin-image-inline-preview', false)
            ->assertSee('Date or date label')
            ->assertSee('Editor photo.jpg');

        $this->actingAs($admin)
            ->put(route('admin.sections.items.update', [$section, $item]), [
                'return_to_page' => 1,
                'item_type' => 'news',
                'title' => 'Updated news item',
                'subtitle' => '',
                'body' => 'Updated description.',
                'image_path' => '/media/legacy/editor-photo.jpg',
                'image_alt' => 'Updated alt text',
                'link_url' => '',
                'link_text' => '',
                'sort_order' => 0,
                'data' => ['date' => 'Updated label', 'is_archived' => '0'],
            ])
            ->assertRedirect(route('admin.pages.edit', $page));

        $item->refresh();
        $this->assertSame('/media/legacy/editor-photo.jpg', $item->image_path);
        $this->assertSame('Updated label', $item->meta('date'));
        $this->assertFalse($item->meta('is_archived'));
        $this->assertSame('retained', $item->meta('preserve_me'));

        $this->actingAs($admin)
            ->put(route('admin.sections.update', $section), [
                'return_to_page' => 1,
                'title' => 'Latest updates edited',
                'slug' => 'latest',
                'template_key' => 'news_list',
                'anchor_id' => '',
                'eyebrow' => '',
                'intro' => 'Updated section introduction.',
                'sort_order' => 0,
                'is_visible' => 1,
            ])
            ->assertRedirect(route('admin.pages.edit', $page));

        $this->assertSame('Latest updates edited', $section->fresh()->title);
    }

    public function test_contact_page_editor_updates_shared_address_and_phone_details(): void
    {
        $this->seed(LegacyContentSeeder::class);
        $page = Page::query()->where('slug', 'contact')->firstOrFail();
        $section = $page->sections()->where('template_key', 'contact_form')->firstOrFail();
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.pages.edit', $page))
            ->assertOk()
            ->assertSee('Contact details shared across the site')
            ->assertSee('Office address')
            ->assertSee('Toll-free number')
            ->assertSee('1 (800) 265-0046');

        $this->actingAs($admin)
            ->put(route('admin.sections.update', $section), [
                'return_to_page' => 1,
                'title' => $section->title,
                'slug' => $section->slug,
                'template_key' => 'contact_form',
                'anchor_id' => '',
                'eyebrow' => $section->eyebrow,
                'intro' => $section->intro,
                'sort_order' => $section->sort_order,
                'is_visible' => 1,
                'settings' => [
                    'heading_level' => 'h1',
                    'interests' => "Send service literature\nSend company literature",
                    'contact_address' => "22 Test Road\nNew Lowell, Ontario\nL0M 1N0",
                    'contact_toll_free' => '1 (800) 555-2013',
                    'contact_phone' => '1 (705) 555-2014',
                    'contact_mobile' => '',
                    'contact_fax' => '1 (705) 555-2015',
                    'contact_email' => 'contact@example.test',
                    'contact_hours' => 'Weekdays, 9am to 5pm',
                ],
            ])
            ->assertRedirect(route('admin.pages.edit', $page));

        $this->assertDatabaseHas('settings', ['key' => 'contact.toll_free', 'value' => '1 (800) 555-2013']);
        $this->assertDatabaseHas('settings', ['key' => 'contact.address', 'value' => "22 Test Road\nNew Lowell, Ontario\nL0M 1N0"]);
        $this->assertDatabaseHas('settings', ['key' => 'contact.email', 'value' => 'contact@example.test']);

        $contactHtml = $this->get('/contact')
            ->assertOk()
            ->assertSee('1 (800) 555-2013')
            ->assertSee('22 Test Road')
            ->getContent();
        $this->assertStringContainsString('22 Test Road<br />', $contactHtml);
        $this->assertStringContainsString('Weekdays, 9am to 5pm', $contactHtml);
        $this->assertStringNotContainsString('&lt;br', $contactHtml);
        $this->get('/')
            ->assertOk()
            ->assertSee('1 (800) 555-2013');
    }

    public function test_social_profile_links_are_editable_and_render_in_header_and_footer(): void
    {
        Page::create([
            'title' => 'Home',
            'slug' => 'home',
            'is_home' => true,
            'is_active' => true,
        ]);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Social profiles')
            ->assertSee('Facebook profile URL')
            ->assertSee('Instagram profile URL');

        $initialHtml = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString('social-links--header', $initialHtml);
        $this->assertStringNotContainsString('social-links--footer', $initialHtml);

        $this->actingAs($admin)
            ->from(route('admin.settings.index'))
            ->put(route('admin.settings.update'), [
                'settings' => [
                    'social.facebook' => 'https://www.facebook.com/rohes',
                    'social.linkedin' => 'https://www.linkedin.com/company/rohes',
                    'social.instagram' => '',
                ],
            ])
            ->assertRedirect(route('admin.settings.index'));

        $this->assertDatabaseHas('settings', [
            'key' => 'social.facebook',
            'value' => 'https://www.facebook.com/rohes',
        ]);

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertSame(2, substr_count($html, 'aria-label="ROHES on Facebook"'));
        $this->assertSame(2, substr_count($html, 'aria-label="ROHES on LinkedIn"'));
        $this->assertSame(0, substr_count($html, 'aria-label="ROHES on Instagram"'));
        $this->assertStringContainsString('social-links--header', $html);
        $this->assertStringContainsString('social-links--footer', $html);

        $this->actingAs($admin)
            ->from(route('admin.settings.index'))
            ->put(route('admin.settings.update'), [
                'settings' => ['social.facebook' => 'javascript:alert(1)'],
            ])
            ->assertSessionHasErrors('settings.social.facebook');
    }

    public function test_header_shows_brand_text_when_no_logo_is_set(): void
    {
        Page::create([
            'title' => 'Home',
            'slug' => 'home',
            'is_home' => true,
            'is_active' => true,
        ]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('<span class="brand__text">', $html);
        $this->assertStringNotContainsString('class="brand__logo"', $html);
    }

    public function test_header_hides_brand_text_until_the_logo_fails_to_load(): void
    {
        Page::create([
            'title' => 'Home',
            'slug' => 'home',
            'is_home' => true,
            'is_active' => true,
        ]);
        Setting::create(['key' => 'site.logo_path', 'value' => '/media/legacy/logo.jpeg']);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('class="brand__logo" x-show="! logoFailed" @error="logoFailed = true"', $html);
        $this->assertStringContainsString('class="brand__text" x-show="logoFailed" x-cloak', $html);
    }

    public function test_home_hero_background_can_be_selected_in_the_cms(): void
    {
        $page = Page::create([
            'title' => 'Home',
            'slug' => 'home',
            'is_home' => true,
            'is_active' => true,
        ]);
        $section = $page->sections()->create([
            'title' => 'Welcome',
            'slug' => 'hero',
            'template_key' => 'hero',
        ]);
        Media::create([
            'disk' => 'media',
            'folder' => 'uploads',
            'filename' => 'hero-field.jpg',
            'original_name' => 'Field photo.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 100,
            'width' => 1200,
            'height' => 600,
        ]);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.pages.edit', $page))
            ->assertOk()
            ->assertSee('Hero background image')
            ->assertSee('Background image visibility')
            ->assertSee('15%')
            ->assertSee('Field photo.jpg');

        $this->get('/')
            ->assertOk()
            ->assertDontSee('hero__background')
            ->assertDontSee('--hero-image-opacity');

        $section->update(['settings' => ['background_image' => '/media/uploads/hero-field.jpg']]);
        $this->get('/')
            ->assertOk()
            ->assertSee('--hero-image-opacity: 0.15');

        $this->actingAs($admin)
            ->put(route('admin.sections.update', $section), [
                'return_to_page' => 1,
                'title' => 'Welcome',
                'slug' => 'hero',
                'template_key' => 'hero',
                'anchor_id' => '',
                'eyebrow' => 'Serving Ontario',
                'intro' => 'Responsible biosolids management.',
                'sort_order' => 0,
                'is_visible' => 1,
                'settings' => [
                    'background_image' => '/media/uploads/hero-field.jpg',
                    'background_opacity' => 65,
                ],
            ])
            ->assertRedirect(route('admin.pages.edit', $page));

        $this->assertSame('/media/uploads/hero-field.jpg', $section->fresh()->setting('background_image'));
        $this->assertSame('65', $section->fresh()->setting('background_opacity'));
        $this->get('/')
            ->assertOk()
            ->assertSee('hero--with-image')
            ->assertSee('/media/uploads/hero-field.jpg')
            ->assertSee('--hero-image-opacity: 0.65');

        $this->actingAs($admin)
            ->from(route('admin.pages.edit', $page))
            ->put(route('admin.sections.update', $section), [
                'return_to_page' => 1,
                'title' => 'Welcome',
                'slug' => 'hero',
                'template_key' => 'hero',
                'anchor_id' => '',
                'eyebrow' => 'Serving Ontario',
                'intro' => 'Responsible biosolids management.',
                'sort_order' => 0,
                'is_visible' => 1,
                'settings' => [
                    'background_image' => '/media/uploads/hero-field.jpg',
                    'background_opacity' => 101,
                ],
            ])
            ->assertSessionHasErrors('settings.background_opacity');

        $this->assertSame('65', $section->fresh()->setting('background_opacity'));
    }
}