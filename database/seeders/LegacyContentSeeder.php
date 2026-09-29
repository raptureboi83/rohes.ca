<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\Page;
use App\Models\Section;
use App\Models\SectionItem;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

/**
 * Migrates the entire legacy rohes.ca site into the CMS.
 *
 * Copy has been MODERNISED, not copied verbatim. The changes made are all
 * listed in docs/CHANGELOG.md under "Content that had gone stale", and the
 * items still needing client confirmation are under "Open items".
 *
 * Every write is INSERT-IF-ABSENT, never an update. Re-running this seeder
 * fills in anything that is missing and leaves every existing row alone, so
 * edits made in the CMS always win. This is deliberate: the client owns the
 * data once it is in the CMS, and a seeder that overwrites would silently
 * revert their work. To change content that already exists, edit it in the
 * admin, or write a one-off migration for a specific change you have
 * approved.
 */
class LegacyContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Migrating legacy rohes.ca content…');

        $this->seedMedia();
        $this->seedSettings();
        $this->seedHome();
        $this->seedAbout();
        $this->seedServices();
        $this->seedBiosolids();
        $this->seedNews();
        $this->seedResources();
        $this->seedContact();
        $this->seedUsers();

        $this->command->info('Done. Pages: '.Page::count()
            .'  Sections: '.Section::count()
            .'  Items: '.SectionItem::count()
            .'  Settings: '.Setting::count()
            .'  Media: '.Media::count());

        // Settings and navigation are cached, and this seeder writes settings
        // directly rather than through a controller, so it would not otherwise
        // invalidate anything. Without this, re-running the seeder appears to
        // do nothing on the public site: the database changes but the cached
        // copies are still served. Hit for real while relabelling the phone
        // numbers - the new labels did not appear until this was added.
        Cache::forget('site_settings');
        Cache::forget('nav_pages');
        Cache::forget('admin_unread_badge');
    }

    // ------------------------------------------------------------------ media

    protected function seedMedia(): void
    {
        // Recovered from the base64 data URIs in the legacy stylesheet.
        // Dimensions are the real ones; do not upscale these.
        $files = [
            ['legacy/logo.jpeg',                  'logo.jpeg',                  485, 203, 'Region of Huronia Environmental Services logo'],
            ['legacy/svc-dredge.jpg',             'dredge.jpg',                 307, 198, 'Dredge equipment at work'],
            ['legacy/svc-mobile-screening-1.jpg', 'mobile-screening-1.jpg',     300, 192, 'Mobile screening plant'],
            ['legacy/svc-mobile-screening-2.jpg', 'mobile-screening-2.jpg',     300, 188, 'Mobile screening plant on site'],
            ['legacy/svc-fleet-truck.jpg',        'fleet-truck.jpg',            125,  75, 'ROHES tanker truck'],
            ['legacy/svc-gps-spreading.jpg',      'gps-spreading.jpg',          125,  75, 'GPS-guided spreading equipment'],
            ['legacy/svc-hydro-vac.jpg',          'hydro-vac.jpg',              125,  75, 'Hydrovac excavation unit'],
            ['legacy/svc-storage-lagoon.jpg',     'storage-lagoon.jpg',         125,  75, 'Membrane-lined storage cell'],
            ['legacy/news-storage-lagoon.jpg',    'news-storage-lagoon.jpg',    301, 180, 'One of ROHES nine membrane-lined storage cells'],
            ['legacy/news-drainage-bag.jpg',      'news-drainage-bag.jpg',      132,  95, 'Drainage bag ready for collection'],
            ['legacy/news-roll-on-field.jpg',     'news-roll-on-field.jpg',     132,  85, 'Roll-on-field drainage system'],
            ['legacy/news-aerway-truck.jpg',      'news-aerway-truck.jpg',      100,  67, 'AerWay sub-surface injection unit loaded for transport'],
            ['legacy/news-case-tractor.jpg',      'news-case-tractor.jpg',      100,  59, 'Tractor pulling the injection implement across a field'],
            ['docs/aerway-rack-card.pdf',         'aerway-rack-card.pdf',      null, null, 'AerWay SSD sub-surface injection rack card'],
        ];

        foreach ($files as [$folderFile, $name, $w, $h, $alt]) {
            [$folder, $filename] = explode('/', $folderFile, 2);

            Media::firstOrCreate(
                ['folder' => $folder, 'filename' => $filename],
                [
                    'disk' => 'media',
                    'original_name' => $name,
                    'mime_type' => str_ends_with($filename, '.pdf') ? 'application/pdf' : 'image/jpeg',
                    'size' => 0,
                    'width' => $w,
                    'height' => $h,
                    'alt_text' => $alt,
                    'title' => $name,
                ],
            );
        }
    }

    // --------------------------------------------------------------- settings

    protected function seedSettings(): void
    {
        $settings = [
            // Branding
            'site.name'         => 'Region of Huronia Environmental Services (2013) Ltd.',
            'site.short_name'   => 'ROHES',
            'site.tagline'      => 'Environmental Services',
            'site.logo_path'    => '/media/legacy/logo.jpeg',
            'site.footer_blurb' => 'Biosolids management, treatment, storage, transport and land application across Simcoe County, Ontario.',

            // Contact. Labels are Toll Free / Local / Fax; the numbers were
            // confirmed by the client on 2026-09-29 and are displayed in the
            // form 1 (555) 555-5555. Note the 1-800 number is the toll-free
            // line, NOT the fax, and the fax is a 705 number — this corrects
            // an earlier assumption carried in the legacy data.
            'contact.toll_free' => '1 (800) 265-0046',
            'contact.phone'     => '1 (705) 424-9799', // Local
            'contact.fax'       => '1 (705) 424-6075',
            'contact.mobile'    => '', // Not supplied by the client. Blank fields are hidden site-wide.
            'contact.email'  => 'info@regionofhuronia.com',
            'contact.hours'  => 'Monday to Friday, 8:00am – 5:00pm',
            // Multi-line exactly as the client entered it in the admin. Seeded
            // so a fresh install is not blank, and harmless in production
            // because the write below only inserts when the key is absent.
            'contact.address' => "10367 County Road 10\nNew Lowell, Ontario \nL0M 1N0",

            // SEO
            'site.seo_title'       => 'ROHES — Biosolids Management & Land Application | Simcoe County, Ontario',
            'site.seo_description' => 'ROHES provides biosolids pumping, transport, storage, screening, treatment and sub-surface land application for municipalities across Simcoe County, Ontario.',

            // Legal — the licence number belongs in Settings. Regulatory copy
            // lives in the editable Biosolids page sections.
            'legal.license_number' => '',
        ];

        foreach ($settings as $key => $value) {
            if ($value === '') {
                continue; // leave unset so the admin form shows the field empty
            }

            Setting::firstOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => explode('.', $key)[0]],
            );
        }

        // Retired key. "contact.phone2" is no longer rendered; it is left in
        // place so re-seeding never deletes data managed through the CMS.
    }

    // ------------------------------------------------------------------ pages

    protected function makePage(array $attrs): Page
    {
        return Page::firstOrCreate(
            ['slug' => $attrs['slug']],
            array_merge([
                'is_active' => true,
                'show_in_nav' => true,
                'is_home' => false,
                'sort_order' => 0,
            ], $attrs),
        );
    }

    /**
     * Insert-if-absent for a section and its items.
     *
     * An existing section is left exactly as it is, so a client who has
     * rewritten the title or intro keeps their version. Items are matched on
     * item_type + title within the section and are only inserted when no such
     * item exists yet, which also means a re-run never resets somebody's
     * reordering: existing rows keep whatever sort_order they have.
     */
    protected function makeSection(Page $page, array $attrs, array $items = []): Section
    {
        $section = Section::firstOrCreate(
            ['page_id' => $page->id, 'slug' => $attrs['slug']],
            array_merge(['is_visible' => true, 'sort_order' => 0], $attrs),
        );

        foreach ($items as $i => $item) {
            $this->makeItem($section, $item, $i);
        }

        return $section;
    }

    protected function makeItem(Section $section, array $item, int $sortOrder): void
    {
        $item = array_merge(['item_type' => 'default'], $item);

        $existing = $section->items()
            ->where('item_type', $item['item_type'])
            ->when(
                isset($item['title']),
                fn ($q) => $q->where('title', $item['title']),
                // Untitled items (plain paragraphs) can only be matched on
                // their body text, which is still far better than deleting
                // and recreating the row, which would lose client edits.
                fn ($q) => $q->where('body', $item['body'] ?? null),
            )
            ->first();

        if ($existing) {
            return;
        }

        $section->items()->create(array_merge(['sort_order' => $sortOrder], $item));
    }

    protected function seedHome(): void
    {
        $page = $this->makePage([
            'title' => 'Home', 'slug' => 'home', 'is_home' => true, 'sort_order' => 0,
            'show_in_nav' => true, 'nav_label' => 'Home',
            'meta_description' => 'ROHES provides regulated biosolids management, storage, transport and land application services across Simcoe County, Ontario.',
        ]);

        $this->makeSection($page, [
            'title' => 'Responsible biosolids management for Ontario communities',
            'slug' => 'hero',
            'template_key' => 'hero',
            'eyebrow' => 'Serving Simcoe County since 2001',
            'intro' => 'For over 25 years we have helped Ontario municipalities manage municipal, commercial and residential wastewater solids safely, reliably and within regulation — from the vacuum truck to the field.',
            'settings' => [
                'cta_label' => 'Request a quote',
                'cta_url' => '/contact',
                'secondary_label' => 'Our services',
                'secondary_url' => '/services',
            ],
        ], [
            ['item_type' => 'stat', 'title' => '25+', 'subtitle' => 'years serving Ontario municipalities'],
            ['item_type' => 'stat', 'title' => '9', 'subtitle' => 'membrane-lined storage cells'],
            ['item_type' => 'stat', 'title' => '95.8%', 'subtitle' => 'highest Ministry facility audit rating'],
            ['item_type' => 'stat', 'title' => 'Excellent', 'subtitle' => 'Ontario safety rating (Highway Traffic Act)'],
        ]);

        $this->makeSection($page, [
            'title' => 'What we do',
            'slug' => 'services-overview',
            'template_key' => 'card_grid',
            'eyebrow' => 'Our services',
            'intro' => 'Complete wastewater solids handling, from collection through to sub-surface land application.',
            'settings' => ['background' => 'section--sand'],
        ], [
            ['item_type' => 'service', 'title' => 'Pumping & transport', 'image_path' => '/media/legacy/svc-fleet-truck.jpg',
             'image_alt' => 'ROHES tanker truck', 'link_url' => '/services',
             'body' => 'Purpose-built vacuum trucks move biosolids from treatment plants to storage and to land. Available for scheduled and emergency response.'],
            ['item_type' => 'service', 'title' => 'Mobile screening', 'image_path' => '/media/legacy/svc-mobile-screening-1.jpg',
             'image_alt' => 'Mobile screening plant', 'link_url' => '/services',
             'body' => 'On-site screening dewateres and reduces solids volume before transport, cutting costs and shortening the haul.'],
            ['item_type' => 'service', 'title' => 'Storage lagoons', 'image_path' => '/media/legacy/svc-storage-lagoon.jpg',
             'image_alt' => 'Membrane-lined storage cell', 'link_url' => '/services',
             'body' => 'Nine membrane-lined storage cells hold biosolids through winter and inclement weather, when land application is not feasible.'],
            ['item_type' => 'service', 'title' => 'Land application', 'image_path' => '/media/legacy/news-case-tractor.jpg',
             'image_alt' => 'Tractor pulling the injection implement across a field', 'link_url' => '/biosolids',
             'body' => 'Sub-surface injection with AerWay equipment places solids below the surface — reducing odour, giving even application, and aerating the soil as it goes.'],
        ]);

        $this->makeSection($page, [
            'title' => 'Why municipalities choose ROHES',
            'slug' => 'why-rohes',
            'template_key' => 'checklist',
            'eyebrow' => 'Our difference',
            'intro' => 'A long-term supplier you can rely on through winter shutdowns, storm events and regulatory change.',
        ], [
            ['item_type' => 'checklist', 'title' => 'Decades of operating history in Simcoe County'],
            ['item_type' => 'checklist', 'title' => 'A modern fleet of purpose-built vehicles'],
            ['item_type' => 'checklist', 'title' => 'Full in-house capability — no subcontracting'],
            ['item_type' => 'checklist', 'title' => 'Long-term municipal and commercial clients'],
            ['item_type' => 'checklist', 'title' => 'Staff trained and qualified in every department'],
            ['item_type' => 'checklist', 'title' => 'Available for both large and small projects'],
        ]);

        $this->makeSection($page, [
            'title' => 'Ready to talk about your next project?',
            'slug' => 'cta',
            'template_key' => 'cta_banner',
            'intro' => 'Tell us what you are managing and we will put together a practical approach and a clear price.',
            'settings' => [
                'cta_label' => 'Contact ROHES',
                'cta_url' => '/contact',
                'secondary_label' => '705-424-6075',
                'secondary_url' => 'tel:7054246075',
            ],
        ]);
    }

    protected function seedAbout(): void
    {
        // The legacy site had this content but no navigation button pointed
        // at it, so it was effectively unreachable. It is now a real page.
        $page = $this->makePage([
            'title' => 'About', 'slug' => 'about', 'sort_order' => 1,
            'meta_description' => 'Learn about Region of Huronia Environmental Services — our history, fleet, people and approach to biosolids management.',
        ]);

        $this->makeSection($page, [
            'title' => 'About ROHES',
            'slug' => 'intro',
            'template_key' => 'text_block',
        ], [
                ['item_type' => 'default', 'body' => 'Region of Huronia Environmental Services (2013) Ltd., or ROHES, has been a leader in the provision of environmental services for over 25 years. The company\'s focus has been the pumping, transport, storage and disposal of municipal, commercial and residential biological waste, along with a range of related services.'],
                ['item_type' => 'default', 'body' => 'Customer satisfaction is a paramount concern at ROHES. The large number of municipalities which have been long-term customers reflects the leading-edge approach the company has taken with its services and its competitiveness.'],
                ['item_type' => 'default', 'body' => 'As an industry leader, ROHES employs a loyal group of key, well-trained staff to meet the needs of its customers. The company takes pride in employing the most qualified and skilled individuals for every department — from management to drivers to mechanics to office staff.'],
                ['item_type' => 'default', 'body' => 'ROHES operates a modern fleet of purpose-built vehicles to meet the specific needs of its customers. With concerns over liability, it is important that you, the customer, have confidence in your supplier\'s ability to service.'],
                ['item_type' => 'default', 'body' => 'ROHES is ready and available for both large and small projects.'],
            ],
        );

        $this->makeSection($page, [
            'title' => 'A record of safety',
            'slug' => 'safety',
            'template_key' => 'feature_split',
            'eyebrow' => 'Accreditation',
        ], [
            ['item_type' => 'feature', 'title' => '95.8% facility audit rating',
             'image_path' => '/media/legacy/svc-fleet-truck.jpg', 'image_alt' => 'ROHES tanker truck',
             'body' => 'ROHES underwent a facility audit by the Ontario Ministry of Transportation and received an overall rating of 95.8% — the highest rating awarded in that audit programme.'],
            ['item_type' => 'feature', 'title' => '“Excellent” safety rating',
             'image_path' => '/media/legacy/svc-dredge.jpg', 'image_alt' => 'Dredge equipment at work',
             'body' => 'The Registrar of Motor Vehicles, Ontario Ministry of Transportation, awarded ROHES an “Excellent” safety rating under Section 17.1(1) of the Highway Traffic Act.'],
        ]);

        $this->makeSection($page, [
            'title' => 'Talk to us',
            'slug' => 'cta',
            'template_key' => 'cta_banner',
            'intro' => 'We are ready and available for both large and small projects.',
            'settings' => ['cta_label' => 'Get in touch', 'cta_url' => '/contact'],
        ]);
    }

    protected function seedServices(): void
    {
        $page = $this->makePage([
            'title' => 'Services', 'slug' => 'services', 'sort_order' => 2,
            'meta_description' => 'Pumping and transport, mobile screening, dredging, storage lagoons, hydrovac and sub-surface land application services from ROHES.',
        ]);

        $this->makeSection($page, [
            'title' => 'Our services',
            'slug' => 'all-services',
            'template_key' => 'card_grid',
            'eyebrow' => 'What we offer',
            'intro' => 'Complete capability in-house, so you deal with one supplier from collection through to application.',
        ], [
            ['item_type' => 'service', 'title' => 'Pumping and transport', 'image_path' => '/media/legacy/svc-fleet-truck.jpg',
             'image_alt' => 'ROHES tanker truck', 'body' => 'Purpose-built vacuum trucks collect biosolids from municipal, commercial and residential treatment facilities and move them to storage or directly to land.'],
            ['item_type' => 'service', 'title' => 'Mobile screening', 'image_path' => '/media/legacy/svc-mobile-screening-1.jpg',
             'image_alt' => 'Mobile screening plant', 'body' => 'On-site screening and roller pressing remove hair and other products found in sewage. We also screen sand bedding.'],
            ['item_type' => 'service', 'title' => 'Dredging', 'image_path' => '/media/legacy/svc-dredge.jpg',
             'image_alt' => 'Dredge equipment at work', 'body' => 'Dredge and lagoon pumping to remove accumulated solids from storage cells and settling basins, including dewatering and loading out.'],
            ['item_type' => 'service', 'title' => 'Storage lagoons', 'image_path' => '/media/legacy/svc-storage-lagoon.jpg',
             'image_alt' => 'Membrane-lined storage cell', 'body' => 'ROHES is one of the few operators able to provide winter storage at its own MOE-approved storage site. With the Nutrient Management Act, that capacity has become an absolute necessity for larger municipalities.'],
            ['item_type' => 'service', 'title' => 'Sub-surface land application', 'image_path' => '/media/legacy/news-aerway-truck.jpg',
             'image_alt' => 'AerWay sub-surface injection unit loaded for transport', 'body' => 'AerWay SSD injection places solids below the soil surface, reducing odour, applying material evenly across the full width of the implement, and aerating the soil. It suits hay and pasture crops and can be used in no-till situations.'],
            ['item_type' => 'service', 'title' => 'Specialty hydrovac excavation', 'image_path' => '/media/legacy/svc-hydro-vac.jpg',
             'image_alt' => 'Hydrovac excavation unit', 'body' => 'A modern hydro-vac unit for excavation in sensitive areas such as around municipal and public buildings, where digging risks striking buried hydro, water or gas lines. Many municipalities now require this method. Suitable for both wet and dry materials.'],
            ['item_type' => 'service', 'title' => 'GPS spreading', 'image_path' => '/media/legacy/svc-gps-spreading.jpg',
             'image_alt' => 'GPS-guided spreading equipment', 'body' => 'GPS-guided application provides documented, verifiable assurance of where and how much was spread.'],
            ['item_type' => 'service', 'title' => 'Rapid emergency response',
             'body' => 'A guaranteed response time of four hours or less, with some calls attended in under an hour. Emergency service is available 24 hours a day.'],
            ['item_type' => 'service', 'title' => 'Environmental specialist',
             'body' => 'In response to public concern about soil and water contamination, ROHES employs an environmental specialist who monitors the company\'s own activities.'],
        ]);

        $this->makeSection($page, [
            'title' => 'In addition, ROHES can provide',
            'slug' => 'also-provide',
            'template_key' => 'checklist',
        ], [
            ['item_type' => 'checklist', 'title' => 'Pumping, hauling and disposal of waste bentonite from underground drilling'],
            ['item_type' => 'checklist', 'title' => 'Pumping, transport and disposal of waste such as oily water from ships and abandoned fuel tanks'],
            ['item_type' => 'checklist', 'title' => 'Pumping and storage of the contents of wash pits to facilitate cleaning'],
            ['item_type' => 'checklist', 'title' => 'Pumping, transport and disposal of used motor oil or furnace oil intended for re-use'],
            ['item_type' => 'checklist', 'title' => 'Emergency pumping of spills, overflows, and hazardous materials accumulating in catch basins or manholes'],
            ['item_type' => 'checklist', 'title' => 'Pumping, transport and disposal of sludge from lift locks'],
            ['item_type' => 'checklist', 'title' => 'Pumping, transport and disposal of pulp and plastic waste'],
            ['item_type' => 'checklist', 'title' => 'Cleaning of septic chambers, grit chambers, digestors and interceptors'],
            ['item_type' => 'checklist', 'title' => 'Pumping, temporary storage and transport of chemical, and hazardous non-toxic, waste'],
            ['item_type' => 'checklist', 'title' => 'Provision of pressurised water for testing underground gas lines'],
        ]);
    }

    protected function seedBiosolids(): void
    {
        $page = $this->makePage([
            'title' => 'Biosolids', 'slug' => 'biosolids', 'sort_order' => 3,
            'meta_description' => 'How ROHES manages biosolids land application, from regulation and nutrient management to sub-surface injection and odour control.',
        ]);

        $this->makeSection($page, [
            'title' => 'Biosolids and land application',
            'slug' => 'intro',
            'template_key' => 'text_block',
        ], [
                ['item_type' => 'default', 'body' => 'Biosolids are the nutrient-rich organic solids produced during wastewater treatment. Treated and managed properly, they are a safe and useful soil amendment — an alternative to synthetic fertiliser that returns organic matter to the land.'],
                ['item_type' => 'default', 'body' => 'These materials have valuable soil-enhancing properties. Application improves soil fertility, soil structure, moisture retention and soil permeability, and reduces soil erosion. The nutrients they carry meet part of the annual requirement of the agricultural industry and reduce reliance on synthetic fertiliser.'],
                ['item_type' => 'default', 'body' => 'Biosolids are ideal for corn, soybeans, canola and cereal crops, and application can markedly improve forage and pasture land. In forestry, they can help rehabilitate soil disturbed by mining and other intensive land exploitation. Recycling biosolids is a recognised alternative to landfill disposal and incineration.'],
                ['item_type' => 'default', 'body' => 'The practice has been carried out safely, without negative impact on human health, for many years, and the guidelines governing it are reviewed and strengthened continuously.'],
                ['item_type' => 'default', 'body' => 'ROHES currently manages nine membrane-lined storage cells, which give our clients storage through winter and during inclement weather, when land application is not feasible. Under Ontario\'s Nutrient Management Act, wastewater treatment facilities are required to provide storage for all of the non-agricultural source material they generate across a 240-day period.'],
                ['item_type' => 'default', 'body' => 'With consumer sensitivity, the odour and appearance of sludge on local farms has become a real issue — particularly on properties in close proximity to residential development.'],
                ['item_type' => 'default', 'body' => 'As surface application becomes less popular, demand for sub-surface injection continues to grow. This, along with farmer demand, led ROHES to purchase a second AerWay SSD injection unit.'],
                ['item_type' => 'default', 'body' => 'Sub-surface injection significantly reduces odour, ensures a uniform application rate across the entire width of the implement, and aerates the soil — which can improve crop establishment.'],
            ],
        );

        $this->makeSection($page, [
            'title' => 'What land application does for your soil',
            'slug' => 'soil-benefits',
            'template_key' => 'checklist',
            'eyebrow' => 'Agronomic benefit',
            'intro' => 'Land application of biosolids is an economical alternative to chemical fertilisers, and the effect on the land is cumulative.',
        ], [
            ['item_type' => 'checklist', 'title' => 'An economical alternative to chemical fertilisers'],
            ['item_type' => 'checklist', 'title' => 'Improves soil quality'],
            ['item_type' => 'checklist', 'title' => 'Enhances soil structure'],
            ['item_type' => 'checklist', 'title' => 'Adds much-needed organic matter'],
            ['item_type' => 'checklist', 'title' => 'Reduces synthetic fertiliser requirements'],
            ['item_type' => 'checklist', 'title' => 'Supports forage, pasture and cereal production'],
            ['item_type' => 'checklist', 'title' => 'Aids the rehabilitation of disturbed and mined soils'],
        ]);

        // Note on ordering: every section here is seeded with sort_order 0, so
        // the page renders by section id. Sections that already exist keep the
        // id they were first given, which means newly added sections are
        // written after them. The order below therefore matches the order the
        // page actually renders in.
        $this->makeSection($page, [
            'title' => 'Regulation and nutrient management',
            'slug' => 'regulation',
            'template_key' => 'card_grid',
            'eyebrow' => 'Compliance',
            'intro' => 'Land application in Ontario is closely regulated. ROHES works to the current provincial requirements.',
        ], [
            ['item_type' => 'default', 'title' => 'O. Reg. 306/04', 'link_url' => 'https://www.ontario.ca/laws/regulation/040306', 'link_text' => 'Read the regulation',
             'body' => 'The provincial regulation governing the reuse and land application of sewage sludge and other treatment residuals in Ontario.'],
            ['item_type' => 'default', 'title' => 'Certificate of Approval',
             'body' => 'Biosolids may only be applied to agricultural land under a Certificate of Approval issued by the Ministry. Soil testing and local topography establish whether a given site is suitable.'],
            ['item_type' => 'default', 'title' => 'Ministry oversight', 'link_url' => 'https://www.ontario.ca/page/ministry-environment-conservation-parks', 'link_text' => 'Ministry information',
             'body' => 'Biosolid use is approved and overseen by the Ministry of the Environment, Conservation and Parks, and provides a safe alternative to other fertilisation methods.'],
            ['item_type' => 'default', 'title' => 'Nutrient management', 'link_url' => 'https://agriculture.canada.ca/', 'link_text' => 'Agriculture and Agri-Food Canada',
             'body' => 'Application rates are planned against the nutrient requirements of the receiving land rather than applied by convenience.'],
        ]);

        $this->makeSection($page, [
            'title' => 'In practice',
            'slug' => 'in-practice',
            'template_key' => 'image_tiles',
            'eyebrow' => 'Our equipment',
        ], [
            ['item_type' => 'feature', 'title' => 'Injection unit in transit', 'image_path' => '/media/legacy/news-aerway-truck.jpg', 'image_alt' => 'AerWay sub-surface injection unit loaded for transport'],
            ['item_type' => 'feature', 'title' => 'Working the field', 'image_path' => '/media/legacy/news-case-tractor.jpg', 'image_alt' => 'Tractor pulling the injection implement across a field'],
            ['item_type' => 'feature', 'title' => 'Storage cells', 'image_path' => '/media/legacy/news-storage-lagoon.jpg', 'image_alt' => 'One of ROHES nine membrane-lined storage cells'],
            ['item_type' => 'feature', 'title' => 'Drainage bag', 'image_path' => '/media/legacy/news-drainage-bag.jpg', 'image_alt' => 'Drainage bag ready for collection'],
            ['item_type' => 'feature', 'title' => 'Roll-on-field system', 'image_path' => '/media/legacy/news-roll-on-field.jpg', 'image_alt' => 'Roll-on-field drainage system'],
        ]);

        $this->makeSection($page, [
            'title' => 'What land application does for your soil',
            'slug' => 'soil-benefits',
            'template_key' => 'checklist',
            'eyebrow' => 'Agronomic benefit',
            'intro' => 'Land application of biosolids is an economical alternative to chemical fertilisers, and the effect on the land is cumulative.',
        ], [
            ['item_type' => 'checklist', 'title' => 'An economical alternative to chemical fertilisers'],
            ['item_type' => 'checklist', 'title' => 'Improves soil quality'],
            ['item_type' => 'checklist', 'title' => 'Enhances soil structure'],
            ['item_type' => 'checklist', 'title' => 'Adds much-needed organic matter'],
            ['item_type' => 'checklist', 'title' => 'Reduces synthetic fertiliser requirements'],
            ['item_type' => 'checklist', 'title' => 'Supports forage, pasture and cereal production'],
            ['item_type' => 'checklist', 'title' => 'Aids the rehabilitation of disturbed and mined soils'],
        ]);

        // Kept last so the call to action closes the page. The legacy site put
        // this paragraph at the foot of its biosolids page; it is a genuine
        // route to new work and had no equivalent on the new site.
        $this->makeSection($page, [
            'title' => 'Landowners: we are looking for agricultural land',
            'slug' => 'landowners',
            'template_key' => 'cta_banner',
            'eyebrow' => 'Free organic soil amendment',
            'intro' => 'ROHES is actively seeking agricultural land to accept biosolids from local municipalities. If you would like to receive this free, 100% natural fertiliser and organic soil amendment, contact our office and we will assess your land.',
            'settings' => [
                'cta_label' => 'Contact ROHES',
                'cta_url' => '/contact',
                'secondary_label' => '1 (800) 265-0046',
                'secondary_url' => 'tel:18002650046',
            ],
        ]);
    }

    protected function seedNews(): void
    {
        $page = $this->makePage([
            'title' => 'News', 'slug' => 'news', 'sort_order' => 4,
            'meta_description' => 'Industry news and updates from ROHES on biosolids management, land application and wastewater solids in Ontario.',
        ]);

        $this->makeSection($page, [
            'title' => 'News and updates',
            'slug' => 'items',
            'template_key' => 'news_list',
            'eyebrow' => 'Latest',
            'intro' => 'Company news and industry background on biosolids management in Ontario.',
        ], [
            ['item_type' => 'news', 'title' => 'Sub-surface injection significantly reduces odour',
             'data' => ['date' => 'Industry background', 'is_archived' => true],
             'link_url' => 'https://agriculture.canada.ca/', 'link_text' => 'Agriculture and Agri-Food Canada',
             'body' => 'Sub-surface injection of biosolids places material below the soil surface rather than on top of it. In practice this cuts odour markedly, applies material at a uniform rate across the full width of the implement, and aerates the soil as the tines pass through. Together these effects have made injection the method of choice for farms close to residential development.'],
            ['item_type' => 'news', 'title' => 'Costs of conventional fertiliser versus biosolids',
             'data' => ['date' => 'Industry background', 'is_archived' => true],
             'link_url' => 'https://www.ontariosoilcrop.org/', 'link_text' => 'Ontario Soil and Crop Improvement Association',
             'body' => 'An analysis comparing the delivered cost of conventional fertiliser against that of land-applied biosolids. The conclusion drawn was that where biosolids are readily available, the lower delivered cost of biosolids can offset yield gains from commercial fertiliser — savings partly eaten up by lower yields. Reported by the Ontario Farmer, 8 March 2005.'],
            ['item_type' => 'news', 'title' => 'Facility audit and safety rating',
             'data' => ['date' => 'Company news'],
             'body' => 'ROHES underwent a facility audit by the Ontario Ministry of Transportation, receiving an overall rating of 95.8%. The Registrar of Motor Vehicles also awarded the company an “Excellent” safety rating under Section 17.1(1) of the Highway Traffic Act.'],
            ['item_type' => 'news', 'title' => 'Nine membrane-lined storage cells in operation',
             'data' => ['date' => 'Company news'],
             'body' => 'ROHES manages nine membrane-lined storage cells, giving clients capacity through winter and inclement weather when land application is not feasible — and meeting the storage requirements that came with Ontario\'s Nutrient Management Act.'],
            ['item_type' => 'news', 'title' => 'Second AerWay SSD injection unit added to the fleet',
             'data' => ['date' => 'Company news'],
             'body' => 'In response to falling demand for surface application and rising farmer interest in sub-surface injection, ROHES added a second AerWay SSD injection unit to its equipment.'],
            ['item_type' => 'news', 'title' => 'Drag-hose system breaks the 60,000 m³ mark',
             'data' => ['date' => 'Company news'],
             'body' => 'The drag-hose system has proved highly effective, applying more than 60,000 m³ in a single spreading season. The hose is dragged across the soil surface rather than driven over, which avoids the compaction that repeated passes would cause, and it couples to an AerWay SSD injector fed from a bladder storage unit. It is suited to larger sites where the application has to be completed in a timely manner.'],
        ]);
    }

    protected function seedResources(): void
    {
        // Consolidates the legacy "Links" page and the "Brochure" page.
        $page = $this->makePage([
            'title' => 'Resources', 'slug' => 'resources', 'sort_order' => 5,
            'nav_label' => 'Resources',
            'meta_description' => 'Documents, regulations and industry links relating to biosolids management and land application in Ontario.',
        ]);

        $this->makeSection($page, [
            'title' => 'Documents',
            'slug' => 'downloads',
            'template_key' => 'download_list',
            'eyebrow' => 'Brochures',
            'intro' => 'Product and equipment literature.',
        ], [
            ['item_type' => 'document', 'title' => 'AerWay SSD rack card',
             'body' => 'Manufacturer literature for the AerWay sub-surface injection equipment used in our land application services.',
             'link_url' => '/media/docs/aerway-rack-card.pdf', 'link_text' => 'Download PDF'],
            ['item_type' => 'document', 'title' => 'ROHES construction services card',
             'body' => 'Company brochure listed on the legacy ROHES website. Replace this external placeholder with a current approved document when available.',
             'link_url' => 'http://www.regionofhuronia.com/images/4462%20ROHES%20construct%20card1.pdf', 'link_text' => 'View legacy brochure'],
        ]);

        $this->makeSection($page, [
            'title' => 'Regulations and industry links',
            'slug' => 'links',
            'template_key' => 'card_grid',
            'eyebrow' => 'Reference',
            'intro' => 'Regulations, government departments and industry organisations relevant to biosolids management in Ontario.',
        ], [
            ['item_type' => 'link', 'title' => 'O. Reg. 306/04 — Reuse and land application',
             'link_url' => 'https://www.ontario.ca/laws/regulation/040306', 'link_text' => 'Open ontario.ca',
             'body' => 'The provincial regulation governing the reuse of sewage sludge and other treatment residuals.'],
            ['item_type' => 'link', 'title' => 'Ministry of the Environment, Conservation and Parks',
             'link_url' => 'https://www.ontario.ca/page/ministry-environment-conservation-parks', 'link_text' => 'Open ontario.ca',
             'body' => 'The provincial ministry responsible for oversight of biosolids reuse.'],
            ['item_type' => 'link', 'title' => 'Agriculture and Agri-Food Canada',
             'link_url' => 'https://agriculture.canada.ca/', 'link_text' => 'Open canada.ca',
             'body' => 'Federal agriculture department, including research on soil nutrient management.'],
            ['item_type' => 'link', 'title' => 'Ontario Soil and Crop Improvement Association',
             'link_url' => 'https://www.ontariosoilcrop.org/', 'link_text' => 'Open OSCIA',
             'body' => 'Ontario\'s regenerative agriculture association, with research on soil health.'],
            ['item_type' => 'link', 'title' => 'Ontario Federation of Agriculture',
             'link_url' => 'https://ofa.on.ca/', 'link_text' => 'Open OFA',
             'body' => 'Provincial farm organisation.'],
            ['item_type' => 'link', 'title' => 'Ontario Clean Water Agency',
             'link_url' => 'https://www.ocwa.com', 'link_text' => 'Open OCWA',
             'body' => 'Provincial agency for clean water and wastewater expertise.'],
            ['item_type' => 'link', 'title' => 'Salford Group (AerWay)',
             'link_url' => 'https://salfordgroup.com/', 'link_text' => 'Open salfordgroup.com',
             'body' => 'Current manufacturer of the AerWay aeration and injection equipment.'],
        ]);
    }

    protected function seedContact(): void
    {
        $page = $this->makePage([
            'title' => 'Contact ROHES', 'slug' => 'contact', 'sort_order' => 6,
            'nav_label' => 'Contact',
            'meta_description' => 'Contact Region of Huronia Environmental Services about biosolids management, land application and environmental services.',
        ]);

        $this->makeSection($page, [
            'title' => 'Contact us',
            'slug' => 'contact-form',
            'template_key' => 'contact_form',
            'eyebrow' => 'Get in touch',
            'intro' => 'Tell us about your project and we will get back to you. For the fastest response, include your municipality and the type of material involved.',
            'settings' => [
                'heading_level' => 'h1',
                'interests' => "Send service literature\nSend company literature\nHave a salesperson contact me",
            ],
        ]);
    }

    // ------------------------------------------------------------------ users

    protected function seedUsers(): void
    {
        // Only created if no admin exists, so re-running the seeder can never
        // duplicate or reset a real account. Change the password before any
        // real deployment — see docs/CHANGELOG.md.
        if (\App\Models\User::query()->where('is_admin', true)->exists()) {
            return;
        }

        User::create([
            'name' => 'Site Administrator',
            'email' => 'admin@rohes.ca',
            'password' => 'CHANGE-ME-IMMEDIATELY',
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $this->command->warn('Created default admin: admin@rohes.ca / CHANGE-ME-IMMEDIATELY — change this before going live.');
    }
}
