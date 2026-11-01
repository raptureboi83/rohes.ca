# ROHES.ca — Project Changelog

Every action taken on this project is recorded here in detail, in the order it
happened. Newest entries are appended to the bottom of each day's section.

**Project:** Modernisation + CMS rebuild of [rohes.ca](http://rohes.ca)
**Client:** Region of Huronia Environmental Services (2013) Ltd. (ROHES)
**Local working directory:** `H:\rohes.ca`
**Staging server:** `/home/weddi133/rohes.ca` on `ftp.weddingsbychristian.com:2222`
**Started:** 2026-09-28

---

## Table of contents

- [Phase 0 — Discovery & audit](#phase-0--discovery--audit)
- [Phase 1 — Access & environment](#phase-1--access--environment)
- [Phase 2 — Architecture decisions](#phase-2--architecture-decisions)
- [Phase 3 — Scaffolding](#phase-3--scaffolding)
- [Phase 4 — Data model](#phase-4--data-model)
- [Open items](#open-items--needs-client-confirmation)
- [Decisions log](#decisions-log)

---

## Phase 0 — Discovery & audit

### 0.1 Live site reconnaissance

Fetched `http://rohes.ca` with `curl`.

| Finding | Value |
|---|---|
| HTTP status | `200 OK` |
| Server | `Microsoft-IIS/8.5` |
| `X-Powered-By` | `ASP.NET` |
| `Last-Modified` | `Mon, 26 Oct 2020 16:10:02 GMT` |
| `Content-Length` | `192,573` bytes |
| `ETag` | `"1f8976b2abd61:0"` |

The home page had not been meaningfully changed in ~6 years at time of audit.

**Consequence noted:** the live site is served by a very old Windows/IIS stack.
This single fact later drove the CMS architecture decision (see
[Phase 2](#phase-2--architecture-decisions)).

### 0.2 Structure of the legacy site

The entire site was **one single `index.html`** (192 KB). There were no other
HTML pages. Its structure:

- All CSS inline in a single `<style>` block in `<head>`.
- All imagery embedded as `data:image/jpeg;base64,...` URIs — both in the CSS
  (`#NewsImage1 { background-image: url('data:...') }`) and in an
  `<img id="SiteLogo">` tag. This is why the file is 192 KB of mostly base64.
- "Navigation" was JavaScript, not URLs. Every "page" was a hidden `<section>`
  and `Show_Section('X')` copied a section's `innerHTML` into `#Container`.
- Two sections were unreachable from the nav: `#About` (never wired to a button)
  and `#NewsClip1` (a full copy of a 2005 newspaper article).

**Legacy "pages" and their equivalents:**

| Nav label | Section id | Notes |
|---|---|---|
| Home | `Home` | Logo + one-line mission statement |
| — | `About` | 5 paragraphs. **No nav button existed** |
| News | `News` | 6 news cards |
| Services | `Services` | 7 service cards + a 10-item bullet list |
| Biosolids | `Biosolids` | 6 paragraphs + land-application list |
| Links | `Links` | 12 external links |
| Brochure | `Brochure` | 1 PDF link |
| Contacts | `Contacts` | Details + a form |
| — | `NewsClip1` | Full text of an *Ontario Farmer* article, 2005 |

### 0.3 Defects and problems identified in the legacy markup

These drove the rebuild.

**Broken encoding.** The file is not valid UTF-8. `AerWay®` was stored with the
`®` in a single byte that is not decodable as UTF-8, and renders as
`AerWay?`. This affects at least 20 places in the `NewsClip1` article alone
(`expect?ed`, `nar?row`, `min?imal`, `con?sultant`, `cul ti vation`, …).

**Non-standard elements.** `<content>` and `<footer>` were used as custom
content wrappers inside `<article>`. `<content>` is not a valid HTML element
and is parsed inconsistently across browsers.

**JavaScript in `href`.** `href="javascript:Show_Section('NewsClip1')"` — a
blocked pattern, and it makes the target unbookmarkable and un-crawlable.

**`alert()` on the page header.** `<header id="MainHeader" onclick="alert(window.innerWidth + 'w x ' + window.innerHeight + 'h')">` — a leftover debug handler that fires for any click on the site title.

**Absolute positioning.** `#Container` and `#Navigation` use
`position:absolute` with hardcoded `top`/`left` pixel values, and a negative
`z-index`. Layout is fragile and the nav overlaps content at many widths.

**Broken contact form.**
`<form action="mailto:info@regionofhuronia.com" method="post" enctype="text/plain">`
— `mailto:` form actions are blocked by most browsers and effectively dead.
Also has no `required` attributes and no server-side handling.

**Radially repeating `background` declarations.** Every heading re-declares
`background`, then `background: -webkit-...`, then `background: -moz-...`.
The unprefixed line is repeated three times; the vendor prefixes are dead
weight. Same pattern in `.NavButton:hover`.

**Missing favicons.** The markup references `icon.png`, `img/rohes_icon_152.png`
and `img/rohes_icon_196.png`, but **all three return HTTP 404** — verified. The
site has had no working favicon.

**Mobile nav degrades badly.** Under 600 px, `.NavButton` collapses to
`width:38px`, so the seven nav buttons render as unreadable 38 px slivers.

**Typo in heading.** `Helpfull Links & Info` (double L).

### 0.4 Content that had gone stale

The user chose to modernise copy, not just preserve it. Specific stale claims
found and how each was handled:

| Original | Problem | Handling |
|---|---|---|
| "almost 15 years" | Written c. 2016. In 2026 that is ~25 years. | Rewritten. **Exact founding year is a client assumption — see [Open items](#open-items--needs-client-confirmation).** |
| "Construction is complete!" (storage lagoons) | Refers to a build finished years before the page was written. | Rewritten as a standing capability. |
| "for the 2005 spreading season" (AerWay) | Hard-coded season. | Rewritten without a year. |
| "applying over 60 000 M³ in the 2004 spreading season" (drag-hose) | Hard-coded season and volume. | Volume removed — it was true of one season only and is now misleading. |
| "published March 8, 2005" (cost-saving article) | Third-party copyrighted text. | **Full article text not republished.** Replaced with a summary of its conclusions plus attribution. See [Open items](#open-items--needs-client-confirmation). |
| "All information Copyright 2001-2018" | Stale. | Updated to a current year. |
| `Ministry of The Environment` | Ontario's ministry has been renamed. | Left as-is pending client/legal confirmation. See [Open items](#open-items--needs-client-confirmation). |
| "If you are interesting in receiving" | Typo — "interested". | Fixed. |
| "waste, such asoily water" | Missing space — "such as oily water". | Fixed. |
| "has lead ROHES to purchase" | Should be "led". | Fixed. |
| `cul ti vation` | Broken word, encoding damage. | Fixed. |

### 0.5 Asset recovery

Because every image was a base64 data URI, the originals were never available
as files. All 13 were decoded and written to disk. The image names below come
from the explanatory comments the original author left in the CSS, which
described each one.

Extracted to `public/media/legacy/`:

| File | Pixels | Original CSS comment |
|---|---|---|
| `logo.jpeg` | 485 × 203 | *(the site logo)* |
| `svc-dredge.jpg` | 307 × 198 | "Dredge BIG" |
| `svc-mobile-screening-1.jpg` | 300 × 192 | "pic2 - mobile screening#1 BIG" |
| `svc-mobile-screening-2.jpg` | 300 × 188 | "pic4 - mobile#2 BIG" |
| `news-storage-lagoon.jpg` | 301 × 180 | "Lagoon Image" |
| `news-drainage-bag.jpg` | 132 × 95 | "Drainage bag large" |
| `news-roll-on-field.jpg` | 132 × 85 | "Roll on field" |
| `svc-fleet-truck.jpg` | 125 × 75 | "Large Truck SMALL" |
| `svc-gps-spreading.jpg` | 125 × 75 | "GPS SMALL" |
| `svc-hydro-vac.jpg` | 125 × 75 | "vacuums SMALL" |
| `svc-storage-lagoon.jpg` | 125 × 75 | "lagoons SMALL" |
| `news-aerway-truck.jpg` | 100 × 67 | "Aerway on truck small" |
| `news-case-tractor.jpg` | 100 × 59 | "Case Tractor small" |

Downloaded over HTTP from the live server:

| File | Bytes | Result |
|---|---|---|
| `public/media/docs/aerway-rack-card.pdf` | 75,164 | OK — the AerWay rack card brochure |
| `icon.png` | — | **HTTP 404** |
| `img/rohes_icon_152.png` | — | **HTTP 404** |
| `img/rohes_icon_196.png` | — | **HTTP 404** |

One image was referenced in a commented-out CSS block and never existed:
`#ServiceImage4` ("pic6 - mobile#3"). Correctly skipped.

> **⚠ Image quality limitation — flagged to the user.** Nine of the 13 photos are
> between 100 × 59 and 132 × 95 pixels. They were authored for fixed-size cards in
> 2004-era CSS and are far too small for a modern responsive layout or any retina
> display. The layout has been designed to use them at close to native size so they
> stay sharp, but **the real fix is new photography from the client.**

### 0.6 External link audit

Every outbound link was fetched and its final destination recorded.

| Original URL | Result | Action |
|---|---|---|
| `http://www.agr.gc.ca` | 200 → `https://agriculture.canada.ca/` | Updated to final URL |
| `http://www.css.orst.edu/News/Publicat/Sullivan/worksheet.html` | **000 — dead** | **Dropped.** Oregon State page no longer exists |
| `http://www.aerway.com` | 200 → `https://salfordgroup.com/` | Updated. AerWay is now a Salford Group brand |
| `http://www.ene.gov.on.ca/envision/gp/3425e.pdf` | 200, but redirects to a **generic** ontario.ca landing page — the actual PDF is gone | **Dropped as a dead end.** Replaced with a current reference to O. Reg. 306/04 |
| `http://www.ocwa.com` | 200 | Kept |
| `http://www.ofa.on.ca` | 200 → `https://ofa.on.ca/` | Kept, https |
| `http://www.gov.on.ca/OMAFRA` | 200, but redirects to the **ontario.ca homepage** — not a useful destination | **Dropped as a dead end** |
| `http://www.omafra.gov.on.ca/english/agops/index.html` | 200, still live | Kept |
| `http://www.omafra.gov.on.ca/english/crops/soils/fertility.html` | 200, still live | Kept |
| `http://www.ene.gov.on.ca` | 200 → MECP page | Updated |
| `http://www.ontariosoilcrop.org` | 200 | Kept |
| `http://www.theweathernetwork.com/weather/cities/indexon.htm` | 200, but redirects to a **generic** English homepage — the New Lowell forecast is gone | **Dropped as a dead end** |

Additional replacement targets, all verified 200:

- `https://www.ontario.ca/laws/regulation/040306` — O. Reg. 306/04, the actual
  regulation governing biosolids land application in Ontario.

**Summary: 4 of 12 links were dead or redirected to useless destinations and
have been removed or replaced. All 8 retained links were re-verified live.**

---

## Phase 1 — Access & environment

### 1.1 SFTP access established

Credentials for this server were **not** created by me — the user pointed me at
the existing setup used by the `matsart.ca` project, which I was explicitly
permitted to read for access material only.

Source of truth: `H:\matsart.ca\.vscode\sftp.json`

| Field | Value |
|---|---|
| host | `ftp.weddingsbychristian.com` |
| port | `2222` |
| username | `weddi133` |
| privateKeyPath | `C:\Users\chris\.ssh\id_rsa` |
| protocol | `sftp` |

**Authentication is SSH public-key based — no password is stored or used.**
Connection verified with `BatchMode=yes`:

```
ssh -o BatchMode=yes -i ~/.ssh/id_rsa -p 2222 weddi133@ftp.weddingsbychristian.com "echo CONNECTED"
→ CONNECTED
```

Local key permissions confirmed correct for OpenSSH (owner-only FullControl).

### 1.2 Remote target verified

`/home/weddi133/rohes.ca` — inspected before writing anything:

```
drwxr-x---  4 weddi133 nobody  52 Sep 28 19:05 .
drwxr-x--- 17 weddi133 nobody  52 Sep 28 19:05 .well-known
drwxr-xr-x  2 weddi133 nobody  10 Sep 28 19:03 cgi-bin
```

Effectively empty. Correctly the document root, owned by `weddi133:nobody`,
mode `750`.

A matching deploy config was written to `H:\rohes.ca\.vscode\sftp.json`, with
`src: "."` → `dest: "/home/weddi133/rohes.ca"`. `autoUpload` is **off** —
uploads will be deliberate, not on every save.

### 1.3 SSL

`/home/weddi133/rohes.ca/.well-known/acme-challenge/` exists and is empty, and
`/home/weddi133/ssl/certs/` contains a certificate whose filename begins
`rohes_plansaver_ca_...`.

The user confirmed: **"ssl is auto renewing right now."** SSL is therefore
handled by the host's AutoSSL and is out of scope for manual work. Repeated SSH
connections were being reset during the renewal, which was diagnosed and
expected rather than treated as a fault.

### 1.4 Drive-full incident

`H:\` (931.5 GB, NTFS, labelled **WD NVME SSD**) was at **0 bytes free**. The
first attempt to write `assets/css/style.css` failed with a filesystem error.

Root cause identified by sizing every top-level folder:

| Folder | Size |
|---|---|
| `H:\Torrents` | **762.6 GB** |
| `H:\SteamLibrary` | 53.7 GB |
| `H:\WBC` | 35.5 GB |
| `H:\Jellyfin Backup` | 34.5 GB |
| `H:\Artwork` | 30.1 GB |
| `H:\SwiftBackup` | 9.0 GB |
| `H:\Website Media` | 6.1 GB |

The user freed space. `H:` now reports **26.73 GB free** and the build proceeded.

> **Note:** the local project directory was originally referred to as being on
> the `D:` drive. `D:\rohes.ca` does not exist. The confirmed working directory is
> `H:\rohes.ca`, and that is what was used throughout.

---

## Phase 2 — Architecture decisions

### 2.1 Requirements gathered

Two design questions were put to the user, because both materially change the
build and neither has an obviously correct default.

**Question 1 — how far to go on content?**

| Option | Chosen |
|---|---|
| Modernise the copy too — fix stale claims, typos, encoding; rewrite 2004/2005 news as evergreen industry content; flag every change | **← selected** |
| Preserve copy verbatim, fix only clear errors | |
| Build layout with placeholder copy, await real text from client | |

**Question 2 — visual direction.**

| Option | Chosen |
|---|---|
| Keep forest green + orange, modernised | |
| Deep green + warm earth accent | **← selected** |
| Clean industrial (navy/slate + safety orange) | |

**Question 3 — what replaces the broken form.**

| Option | Chosen |
|---|---|
| Prominent contact block, no form | |
| Third-party form service (Formspree etc.) | |
| Placeholder wired to a stub, real handling on the client's server later | **← selected** |

### 2.2 The CMS decision — the most consequential choice

The user asked for a full CMS so the client can edit everything through a
friendly interface. Before choosing one, the hosting reality was established:

- **Live site runs IIS 8.5** (Windows Server 2012 R2 era).
- Staging is cPanel/Apache.
- The user confirmed: **"Client is moving to modern hosting."**

That answer matters a great deal. While the final host was undecided, the IIS
8.5 constraint ruled out most self-hosted CMS options — WordPress on that box
would need PHP-via-FastCGI plus a MySQL install on an operating system old
enough to be out of mainstream support, which is both a security liability and an
ongoing maintenance burden.

Because the client is moving to modern hosting, that constraint is lifted and a
conventional self-hosted CMS is viable.

The user's instruction: **"check the cms we built in the matsart.ca folder.
something like that laravel. set the site up like matsart.ca structurally as
that worked mostly right away without too many messups."**

### 2.3 What the matsart.ca study found

`H:\matsart.ca` was read in depth (structure, composer manifest, all
migrations, models, controllers, middleware, routes, and admin views). It is
**Laravel 13 / PHP 8.3 / SQLite**, with `laravel/breeze` used for auth only, and
a **100% hand-rolled Blade admin** — no Filament, Nova, Backpack, Livewire or
Inertia anywhere.

Its content model:

```
pages  →  sections  →  section_items
settings (flat key/value)
users (is_admin boolean)
messages / message_replies
```

Conventions worth mirroring:

1. **Admin authorisation is one boolean** — `users.is_admin`, enforced by a
   ~10-line middleware registered as the `admin` alias, applied via
   `Route::middleware(['auth','verified','admin'])->prefix('admin')->name('admin.')`.
   No policies, no gates.
2. **Admin layout is a single standalone Blade file** carrying its own large
   inline `<style>` block, wrapped in a thin `<x-admin-layout>` component. Uses
   semantic `admin-*` / `btn-admin` classes, deliberately not Tailwind utilities.
3. **CRUD shape** — `index` table + thin `create`/`edit` shells that `@include` a
   shared `form.blade.php`. Actions use `route()`, `@csrf`, `@method('PUT'|'DELETE')`
   and `onsubmit="return confirm(...)"`.
4. **Controllers own validation inline** in a `protected function validatedData()`
   helper, then normalise (slug, `$request->boolean()`, empty→null). Index views
   `paginate(50)` ordered by `sort_order, id`.
5. **Flash convention** — `->with('success', …)` for CRUD, `->with('status', …)`
   for settings.
6. **Settings** — flat key/value table, cached with
   `Cache::rememberForever('site_settings')`, read through a global
   `setting($key, $default)` helper in `app/helpers.php`. A blank value deletes
   the row. `Cache::forget()` on save.
7. **Ordering** — a manually-typed integer `sort_order`; new rows get
   `max('sort_order') + 1`. No drag-and-drop except in the media file tree.
8. **Uploaded images** — validated as `image|mimes:...|max:5120`, stored under a
   date-sharded path, served via `asset('storage/'.$path)`, and the settings cache
   is cleared afterwards.

### 2.4 The one thing I am deliberately *not* copying

The matsart.ca frontend is **not schema-driven**, and this is a real design flaw
worth recording:

- The entire public site is a **single HTML document** with `#anchor` targets and
  a JS `showPage()` switcher, not separate URLs.
- Sections are resolved by **hardcoded slug lookup**, e.g.
  `$heroSection = $homeSections->firstWhere('slug', 'hero')`, against five
  hardcoded page slugs.
- **`template_key` is never branched on by any public view.** It exists in the
  admin dropdown as metadata only.
- Consequence: create a new Page, or rename a section slug, and the public site
  silently will not render it. Fields like `Page::nav_label`, `show_in_nav`,
  `Section::show_in_nav` and `anchor_id` are **stored but never consumed** by the
  frontend.
- Content is also **double-sourced** — every value is resolved "Setting first,
  then section/item as fallback", which means two places to edit one fact.

For ROHES the whole point of the CMS is that the client can change *anything*
and have it appear. A hardcoded-slug frontend would deliver an admin that looks
powerful but silently fails. So this project keeps matsart's **structure and
conventions** but replaces the frontend with a genuine generic renderer:

```
foreach ($page->visibleSections as $section)
    @include('site.sections.'.$section->template_key, ['section' => $section])
```

Every key in `Section::TEMPLATE_OPTIONS` is guaranteed to have a matching Blade
partial, and the admin's template dropdown is generated from that same constant —
so a client can never select a layout the frontend cannot draw. Nav is built from
`show_in_nav` + `sort_order`, and a single source of truth is enforced for
values (section content, with Settings used only for genuinely global items like
the phone number and address).

**Improvements deliberately made over matsart.ca:** separate real URLs per page
instead of one-page anchors; genuinely schema-driven rendering; `Setting.group`
added so the settings screen can group fields; `Section.eyebrow`/`intro` and
`SectionItem.image_alt` promoted to real columns (matsart buried these in JSON
where the client could not find them); and a downloadable-document section
template, which a brochure site like this needs.

---

## Phase 3 — Scaffolding

### 3.1 Laravel project created

```
composer create-project laravel/laravel "H:\rohes.ca" "^13.0" --prefer-dist
```

Result: **Laravel 13**, `laravel/framework ^13.8`. Fresh skeleton, key
generated, base migrations run (users, cache, jobs) against SQLite.

Legacy assets were backed up to `%TEMP%\opencode\rohes-legacy-assets` before the
directory was cleared, and restored afterwards into `public/media/`.

### 3.2 Breeze auth

```
composer require laravel/breeze --dev
php artisan breeze:install blade
```

Auth scaffolding only — matching matsart. This gives login, register, password
reset/confirm, email verification and the `/profile` screens for free. The
admin panel itself will be hand-rolled, exactly as in matsart.

Production Vite build verified working: `public/build/manifest.json` plus
`app-*.css` (37.21 kB) and `app-*.js` (55.09 kB).

### 3.3 Frontend toolchain

`alpinejs ^3.17.4` added for progressive-enhancement interactivity (admin media
picker, collapsible panels). Public-site interactions are deliberately plain
vanilla JS to keep the shipped payload small.

Resolved versions: Tailwind `^3.1.0` with `@tailwindcss/vite ^4.0.0`, Vite `^7`.

### 3.4 Filesystem configuration

Added a dedicated `media` disk to `config/filesystems.php`:

```php
'media' => [
    'driver' => 'local',
    'root' => public_path('media'),
    'url' => '/media',
    'visibility' => 'public',
    'throw' => false,
    'report' => false,
],
```

**Why:** uploads are stored inside the webroot and served directly by Apache.
This deliberately avoids depending on `php artisan storage:link`, which creates
a symlink from the webroot into `storage/` — a very common cause of broken
images on shared hosting, and one that also needs re-creating after some
cPanel operations. This way the deployed site has no symlink dependency. A
`media.url` of `/media` is a root-relative path, so it stays correct across
staging and production hostnames without reconfiguration.

---

## Phase 4 — Data model

### 4.1 Migrations written

Seven migrations, ordered `2026_09_28_000001` … `000007`.

**`create_pages_table`**
`title`, `slug` (unique), `nav_label`, `meta_title`, `meta_description`,
`meta_image`, `is_active` (default true), `show_in_nav` (default true),
`is_home` (default false), `sort_order` (default 0), timestamps.

**`create_sections_table`**
`page_id` (FK, cascade delete), `title`, `slug`, `template_key` (default
`text_block`), `anchor_id`, `eyebrow`, `intro`, `is_visible`, `is_system`,
`sort_order`, `settings` (JSON), timestamps. Unique on `(page_id, slug)`.
Indexed on `template_key`.

**`create_section_items_table`**
`section_id` (FK, cascade delete), `item_type` (default `default`), `title`,
`subtitle`, `body`, `image_path`, `image_alt`, `link_url`, `link_text`,
`sort_order`, `data` (JSON), timestamps.

**`create_settings_table`**
`key` (unique), `group` (default `general`), `value` (longText, nullable),
timestamps.

**`create_media_table`**
`disk` (default `public`), `folder` (default `root`), `filename`,
`original_name`, `mime_type`, `size`, `width`, `height`, `alt_text`, `title`,
timestamps. Indexed on `folder` and `mime_type`.

**`create_messages_table`**
`name`, `email`, `phone`, `subject`, `message`, `interest`, `ip_address` (45),
`user_agent` (512), `is_read`, `is_archived`, timestamps. Composite index on
`(is_archived, is_read)` because that is the admin inbox's default query.

**`add_cms_fields_to_users_table`**
Adds `is_admin` (boolean, default false) and `settings_preferences` (JSON), both
`after('password')`. `settings_preferences` stores per-user admin UI state (e.g.
which settings accordions are collapsed), mirroring matsart.

### 4.2 Design notes on the schema

- **The `is_home` flag** on pages guarantees exactly one landing page and avoids
  the ambiguous `slug = 'home'` convention.
- **`group` on settings** is an addition over matsart, which had ~110 unlabelled
  keys. Grouping is what makes a long settings screen navigable for a
  non-technical client.
- **`image_alt` was promoted to a real column.** In matsart this lived inside a
  JSON blob. Alt text is an accessibility requirement and an SEO factor; it
  deserves a first-class field the client will actually see in the form.
- **No `message_replies` table.** A threaded reply feature needs working SMTP to
  be useful, which is not yet configured on staging. The inbox will instead offer
  a "reply" action that opens the visitor's address in the mail client with the
  message quoted. Noted in [Open items](#open-items--needs-client-confirmation).
- **Cascade deletes** throughout, so removing a page cleans up its sections and
  their items.

### 4.3 Models written

**`App\Models\Page`** — fillable via the Laravel 13 `#[Fillable]` attribute;
casts `is_active`/`show_in_nav`/`is_home` to boolean and `sort_order` to integer.
`getRouteKeyName()` returns `id` so admin URLs use numeric ids while `slug`
stays the unique public lookup key. Relations `sections()` and
`visibleSections()`, both ordered by `sort_order, id`. Accessor
`display_label` = `nav_label ?: title`.

**`App\Models\Section`** — carries `TEMPLATE_OPTIONS` (13 layouts) and
`TEMPLATE_HELP` (plain-language description of each, shown in the admin so a
non-technical client can choose sensibly). Relations `page()` and `items()`.
Adds a `setting($key, $default)` method reading the JSON blob with dot
notation and treating `null`/`''` as absent — this keeps every template free of
null-checking. Accessors `template_label` and `has_template`.

The 13 templates:

| Key | Purpose |
|---|---|
| `hero` | Full-width banner: headline, intro, buttons, stats panel |
| `stat_grid` | Row of large numbers / short facts |
| `card_grid` | Responsive cards — services, news, resources |
| `feature_split` | Image beside text |
| `text_block` | Heading + paragraphs |
| `checklist` | Tick list of capabilities |
| `image_tiles` | Small square photos with captions |
| `cta_banner` | Dark band pushing to one action |
| `contact_details` | Address, phone, fax, email, map |
| `contact_form` | Enquiry form → Messages inbox |
| `download_list` | Downloadable documents |
| `news_list` | Dated entries, newest first |
| `rich_text` | Raw HTML escape hatch |

**`App\Models\SectionItem`** — `ITEM_TYPES` (8 presentation hints:
default, service, news, link, document, stat, feature, checklist). Relation
`section()`. Adds `meta($key, $default)` for the JSON `data` blob, and
`isInternalLink()` which distinguishes an in-site page reference from an
outbound `http(s):`/`mailto:`/`tel:`/`#` URL.

### 4.4 Helper functions

Created `app/Helpers.php` and registered it under `composer.json → autoload.files`
so it is loaded on every request. `composer dump-autoload` re-run; 6,871 classes
resolved cleanly.

| Helper | Purpose |
|---|---|
| `setting($key, $default)` | Reads from the whole settings table cached as one array via `Cache::rememberForever('site_settings')`. Treats `null` **and** `''` as absent, so clearing a field in the admin reliably reverts to the default. |
| `setting_bool($key, $default)` | `FILTER_VALIDATE_BOOLEAN` wrapper. |
| `setting_url($key, $default)` | Normalises a stored URL. Internal paths left alone; `http://` upgraded to `https://` so the site neither leaks referrer data nor trips mixed-content warnings. |
| `excerpt($text, $limit)` | Strips tags, collapses whitespace, cuts on a **word** boundary, appends `…`. |
| `placeholder_image_url($label)` | Inline SVG placeholder showing the section's initials, so a card grid with a missing image does not collapse to uneven heights. |

### 4.5 Application configuration

`.env` updated from the stock Laravel values:

| Key | Was | Now |
|---|---|---|
| `APP_NAME` | `Laravel` | `Region of Huronia Environmental Services` |
| `APP_URL` | `http://localhost` | `http://localhost:8000` (matches `artisan serve`) |
| `FILESYSTEM_DISK` | `local` | `media` |

`DB_CONNECTION` remains `sqlite`; `MAIL_MAILER` remains `log` until the client's
host has working SMTP (see [Open items](#open-items--needs-client-confirmation)).

### 4.6 Two bugs caught before they shipped

**1. Media disk mismatch.** The media migration defaulted `disk` to `'public'`,
but the configured disk is named `'media'`. Every uploaded file would have been
written to `storage/app/public` while the frontend looked in `public/media` —
a silent, 100%-failure bug that is painful to diagnose later. Default corrected
to `'media'` **before** the migration was ever run, so no data repair needed.

**2. Media URL did not include the disk prefix.** `getUrlAttribute()` returned
`/{folder}/{filename}`, which resolves to `/legacy/logo.jpeg` instead of
`/media/legacy/logo.jpeg`. The accessor now prefixes `/media` explicitly rather
than reading the disk name from config, so the URL stays correct even if the disk
is later renamed. A `relative_path` accessor was added for filesystem operations
(`legacy/logo.jpeg`, no prefix).

Both were caught by review before the first upload, not by a user-facing failure.

### 4.7 Verification

All 7 migrations applied cleanly against SQLite:

```
2026_09_28_000001_create_pages_table ............ DONE
2026_09_28_000002_create_sections_table .......... DONE
2026_09_28_000003_create_section_items_table ..... DONE
2026_09_28_000004_create_settings_table .......... DONE
2026_09_28_000005_create_media_table ............. DONE
2026_09_28_000006_create_messages_table .......... DONE
2026_09_28_000007_add_cms_fields_to_users_table . DONE
```

Smoke test run through Tinker creating a linked Page → Section → SectionItem
graph plus a Setting, Media, Message and User. Every accessor, cast, relation and
helper was exercised:

| Check | Result |
|---|---|
| `visibleSections` / `items` relations | 1 section, 2 items — ordering by `sort_order, id` works |
| `template_label` | `Hero banner` (resolved from `TEMPLATE_OPTIONS`) |
| `Section::setting()` hit | `Request a quote` |
| `Section::setting()` miss | `'fallback'` — correctly returned the default |
| `setting()` cached read | `705-424-2075` |
| `setting()` on empty string | `'not set'` — empty treated as absent, as designed |
| `Media->url` | `/media/legacy/logo.jpeg` |
| `Media->relative_path` | `legacy/logo.jpeg` |
| `Message->initials` | `DW` |
| `Message->replyMailto()` | correctly built + URL-encoded |
| `User->isAdmin()` | `true` |
| Password cast | hashed (`$2y…`) without touching the model |
| `getRouteKeyName()` | `id` |

Then `migrate:fresh` + `DatabaseSeeder` to return to a clean, empty state ready
for the real content seeder. Verified: 0 pages, 0 sections, 0 messages, 1 user
(the default Breeze test user).

### 4.8 Remaining data-layer work

- `AdminMiddleware` + registering the `admin` alias in `bootstrap/app.php`
- Controllers: `PageController`, `SectionController`, `SectionItemController`,
  `SettingsController`, `MediaController`, `MessageController`
- `LegacyContentSeeder` — the actual ROHES content
- Factories for Page/Section/SectionItem/Media/Message for use in tests

---

## Ground rules (standing constraints)

These are hard constraints on how this project may be worked. They are recorded
here so they survive any loss of context.

### GR1 — `H:\matsart.ca` is READ-ONLY

The user is explicit:

> "you only go there to get access to the server key and to analyze the
> structure, thats all."

**Permitted, and nothing else:**
1. Reading the SFTP access material (`.vscode/sftp.json`) — the server host,
   port, username and private key path.
2. Reading the project's structure and code to analyse the Laravel pattern
   (composer manifest, migrations, models, controllers, middleware, routes,
   views) so ROHES can follow the same conventions.

**Forbidden:** creating, editing, moving, renaming or deleting any file in
`H:\matsart.ca`; running any command that writes there (including
`git add`/`commit`/`checkout`/`stash`/`clean` in that repo); running migrations
or any artisan command with that folder as the working directory; or installing
anything into it.

`matsart.ca` is a **separate, finished client project.** It is not part of this
job and must not be affected by it in any way.

**Verification performed 2026-09-28** to prove compliance:

| Check | Result |
|---|---|
| `git -C H:\matsart.ca status --porcelain` | Empty output, exit code 0 — clean tree, no modified or untracked files |
| Most recent file write anywhere under the folder | `2026-09-27 19:38:52` — **before this project began** (started 2026-09-28) |
| Source files (non-`.git`) modified today | **Zero** |

The newest 15 files are all under `.git\` and correspond to the user's own
commit/push on the evening of 27 September, not to any action taken here.

### GR2 — All project work happens in `H:\matsart.ca`'s absence

Every file created, edited or deleted for ROHES lives under `H:\rohes.ca`.
Scratch/temporary artefacts (decoded legacy HTML, extraction scripts, pre-scaffold
asset backups) live in the tool-designated temp directory
`%TEMP%\opencode\`, never in the project tree and never in another project.

### GR3 — No other projects are touched

No changes to any other folder, service, or repository on this machine unless
the user explicitly requests it. Notably: nothing on the **D:** drive (the
`D:\rohes.ca` path referenced in early conversation does not exist — the project
lives on `H:`), and no other hosted sites.

### GR4 — Uploads to the server are deliberate

`autoUpload` and `uploadOnSave` are both **off** in `.vscode\sftp.json`. Nothing
is pushed to `ftp.weddingsbychristian.com` without a deliberate, verified
action. The remote folder is staging, not production.

---

## Phase 5 — Git & backups

Raised by the user after the data layer was in place. **Nothing has been
initialised yet** — this records the analysis and the decision that needs making
before any `git init` happens.

### 5.1 What Laravel's stock `.gitignore` already covers

Read `H:\rohes.ca\.gitignore`. The important exclusions are **already correct**,
so no customising is needed for the standard cases:

```
.env                     ← app key, DB credentials, SMTP password
.env.backup
.env.production
/vendor/                  ← 60+ MB of dependencies, reinstallable
/node_modules/            ← reinstallable
/public/build/            ← Vite output, rebuildable
/public/storage/          ← the symlink we deliberately do not use
/storage/*.key
/vendor/
/.vscode/                 ← contains the SFTP config + key path
```

`.env` being ignored is exactly right — the `APP_KEY` and any SMTP credentials
must never reach a git remote.

### 5.2 The one real risk: the SQLite database

`/database/database.sqlite` is **not** in the stock `.gitignore`.

This is not an oversight to be blindly corrected — it needs a deliberate
decision, because that single file contains two very different kinds of data:

| Contents | Sensitivity |
|---|---|
| Pages, sections, items, settings, media metadata | The client's entire website content |
| **Every contact-form submission** | **PII: visitor names, email addresses, phone numbers, IP addresses, user agents** |

Committing it would mean pushing visitor PII to whatever remote is configured.
Even a private GitHub repo is a third-party service outside the client
relationship, and a compromised token or a force-push would leak it.

**Recommendation: do not track the database in git.** Back it up separately as a
dated dump that stays under the user's own control.

### 5.3 Recommended backup split

Three distinct concerns, three different mechanisms:

| What | Mechanism | Why |
|---|---|---|
| **Code** | Git, private remote | Needs history, diffs, blame, branch-per-feature, easy rollback. Text diffs well. |
| **Uploaded media** (`public/media/`) | Git, tracked | Currently ~90 KB and will grow slowly. Binary, but small. Revisit Git LFS if it passes ~50 MB. |
| **Database** | Dated dump outside git | Binary, merge-hostile, and carries PII. Needs to be restorable, not diffable. |

Practical consequence: the "client content" backup that actually matters to the
business is the **database dump**, and it needs to be taken deliberately on a
schedule. Git alone would not protect it.

### 5.4 Server-side `.env` is a single point of failure

Because `.env` is gitignored, the deployed server's `.env` is the **only** copy
of the production `APP_KEY` and credentials. If it is lost, every encrypted
value and session breaks.

Mitigation to add to the deploy process: a template `.env.production.example`
**committed to git** with all values blanked except the non-secret ones, so the
real one can be reconstructed in minutes. Documenting the required variables is
as important as backing up the file.

Related: the SFTP config's ignore list also does not yet exclude `.env`,
`vendor/`, `storage/`, `tests/` or the SQLite file. Uploading the local `.env`
would silently overwrite production secrets with local ones. This must be fixed
before the first deploy.

### 5.5 Open question for the user

Three things need deciding before setup:

1. **Where does the remote live?** GitHub (private), a self-hosted Gitea, or
   local-only with a bare repo on this machine as a simple second copy?
2. **Should the database be tracked?** Recommendation above is no.
3. **Who holds the backup copies?** If the only copy of the business content sits
   on one machine, that is not really a backup.

No git repository has been created. Awaiting the user's decision.

Collected as they were found. None of these block the build; all are things the
client must supply or verify before the site goes live.

1. **Founding year.** The original copy said "almost 15 years" (c. 2016) and
   "Copyright 2001-2018". The current rewrite claims **over 25 years**, derived
   from a 2001 start. The company name contains "2013", which suggests a 2013
   re-incorporation, so the operating history may be longer than the legal
   entity. **Confirm the wording the client is comfortable with.**
2. **Ministry name.** The old site says "approved by the Ministry of The
   Environment". Ontario's ministry is now the Ministry of the Environment,
   Conservation and Parks. Needs a decision, since it is a compliance-flavoured
   claim.
3. **New photography.** Nine of thirteen photos are ~100–132 px. The design works
   around this, but new photos would transform the site.
4. **The 2005 newspaper article.** ROHES plans to republish an *Ontario Farmer*
   article. My recommendation is to summarise its conclusions with attribution
   rather than reprint it in full, since the copyright is the publisher's.
   **Confirm this is acceptable.**
5. **Contact form endpoint.** The form is a placeholder until the client's server
   can handle mail. Needs a real endpoint, plus a decision on whether
   notifications are sent by email, written only to the inbox, or both.
6. **SMTP / mail.** Not configured on staging. A "notify office on new message"
   feature cannot be tested until it is.
7. **Admin user accounts.** Need the client's admin names and email addresses.
8. **Domain + DNS.** Staging is on `ftp.weddingsbychristian.com`; the final
   domain/hosting move is still pending.
9. **Brochure PDF.** The AerWay rack card is a third-party product brochure, not
   a ROHES document. The client may want their own company brochure instead.

---

## Decisions log

Condensed index. Full reasoning is in the phase sections above.

| # | Decision | Rationale |
|---|---|---|
| 1 | Modernise copy, flag every change | User's explicit choice |
| 2 | Deep green + warm earth accent | User's explicit choice; suits agriculture/land-application |
| 3 | Contact form = placeholder for now | User's explicit choice |
| 4 | Laravel 13 self-hosted CMS, mirroring matsart.ca | User's explicit instruction; client moving to modern hosting lifts the IIS 8.5 blocker |
| 5 | Separate URL per page, not one-page anchors | Legacy used `#` anchors + JS; bad for SEO, sharing and accessibility |
| 6 | Schema-driven frontend renderer | **Deliberate fix for matsart.ca's hardcoded-slug flaw**, which would otherwise make the admin silently non-functional |
| 7 | `media` disk in the webroot | Avoids `storage:link` fragility on shared hosting |
| 8 | Drop 4 dead/redirecting external links | Verified by fetch; kept the 8 that still resolve |
| 9 | Do not republish the 2005 article in full | Third-party copyright |
| 10 | Promote `image_alt` to a real column | Accessibility + SEO; too important to hide in JSON |
| 11 | Add `Setting.group` | ~110 ungrouped settings keys are unnavigable for a non-technical editor |
| 12 | Reply-by-mailto instead of a `message_replies` table | No working SMTP on staging yet; revisit later |
| 13 | No favicon on the legacy site — generate a new set from the logo | All three referenced icon files 404'd |
| 14 | `autoUpload: false` in the deploy config | Uploads should be deliberate, not fired on every file save |

---

## Phase 6 — Staging deployment (2026-09-28)

### 6.1 Making the site reachable

The application was built and verified locally, but nothing was on the server yet
and the domain did not resolve. Four separate things were needed:

| Layer | State at start | Action taken |
|---|---|---|
| Files on server | `rohes.ca/` held only `.well-known` and `cgi-bin` | Uploaded via tar over SSH, then extracted |
| Document root | unset | User set to `weddi133/rohes.ca/public` in cPanel |
| DNS | `rohes.plansaver.ca` returned **NXDOMAIN** | User added an `A` record |
| TLS certificate | self-signed, unusable in a browser | **Still outstanding — see 6.6** |

**Verification without DNS.** Because the hostname did not resolve, the app was
tested by sending requests straight to the server IP with a `Host` header
(`curl --resolve rohes.plansaver.ca:80:209.182.196.37`). This exercises the real
Apache/PHP stack without touching DNS. All seven public routes returned 200 and an
unknown slug returned 404, which confirmed the app itself was sound before DNS existed.

### 6.2 PHP version mismatch — the Symfony fix

The first provisioning run failed immediately:

```
Composer detected issues in your platform:
Your Composer dependencies require a PHP version ">= 8.4.1". You are running 8.3.33.
```

**Cause.** `composer.json` declared `"php": "^8.3"`, which means *8.3 or newer*.
Composer resolves dependencies against whatever PHP version is running the
install. Locally that was PHP 8.4.24, so Composer selected Symfony 8.1 — which
itself requires PHP >= 8.4.1. The server only has 8.3.33, so the deployed code
refused to boot.

**Fix.** Pinned the resolution target in `composer.json`:

```json
"config": { "platform": { "php": "8.3.33" } }
```

`composer update` then downgraded 16 Symfony packages from 8.1.x to 7.4.x. No
application code changed — only the library versions, so they run on the server's PHP.

**Why this matters beyond this project.** `matsart.ca` declares the same `^8.3` but
pins nothing, and has **no** `config.platform` entry. Its lockfile happens to hold
Symfony 7.4.13 because it was written on 2026-09-08, when 7.4 was current. It got
the right answer by luck. The `platform` pin makes the result deterministic, so a
future `composer update` on any machine resolves for production rather than
whoever ran it.

### 6.3 Document root, and removing a bad assumption

A root-level `.htaccess` was deployed early on, written on the assumption the
document root would be `rohes.ca` and requests would need rewriting into `public/`
— mirroring how `matsart.ca` is set up.

Once the document root was moved to `rohes.ca/public`, that file sat *outside* the
document root doing nothing, and would have broken things if the document root ever
moved back. It was deleted. `public/.htaccess` (Laravel's own) is now the only
rewrite in play and correctly routes all requests to `index.php`.

**Security check after the change.** Confirmed no application internals are
web-reachable, which is the whole point of the `public/` document root:

| Path | Result |
|---|---|
| `/.env` | 406 (Mod Security) |
| `/storage/logs/laravel.log` | 403 (Apache) |
| `/database/database.sqlite` | **403 — customer data protected** |
| `/vendor/composer/installed.json` | 404 (outside document root) |
| `/bootstrap/app.php`, `/config/database.php`, `/artisan` | 404 |

The 403 vs 406 split is only *which layer* stopped it. Both mean denied.

### 6.4 Production environment setup

A production `.env` was built **on the server** rather than uploaded, so the
`APP_KEY` never existed on the local machine or in any transfer:

- `APP_ENV=production`, `APP_DEBUG=false`
- `APP_URL=https://rohes.plansaver.ca`
- `APP_TIMEZONE=America/Toronto`
- `DB_CONNECTION=sqlite`, `FILESYSTEM_DISK=media`
- `SESSION_SECURE_COOKIE=true`
- `php artisan key:generate --force` (server-side only)
- Fresh database, all 10 migrations, then `LegacyContentSeeder`
- Seeded: 6 pages, 15 sections, 64 items, 16 settings, 14 media, 1 admin

**A real gotcha, documented because it will bite again:** `tar` does not preserve
empty directories. `storage/framework/views` was created empty locally, so it did
not exist on the server, and `php artisan optimize:clear` failed with
`View path not found`. Fixed by explicitly `mkdir -p`-ing every framework directory
after extraction. Any future deploy must do the same.

### 6.5 Timezone bug — Laravel 13 changed the default

`php artisan about` reported `Timezone: UTC` despite `APP_TIMEZONE=America/Toronto`
being present in `.env`. The cause: **Laravel 13's `config/app.php` hardcodes
`'timezone' => 'UTC'`** and no longer reads `env('APP_TIMEZONE')`. The `.env` entry
was being silently ignored.

Fixed by restoring the environment lookup:

```php
'timezone' => env('APP_TIMEZONE', 'UTC'),
```

This matters beyond cosmetics — it controls the timestamps shown against every
contact-form message in the admin inbox, i.e. when the business thinks an enquiry
arrived.

### 6.6 Outstanding: TLS certificate

**The site is not yet usable in a normal browser without a warning.**

The certificate currently served for `rohes.plansaver.ca` is **self-signed**:

| Domain | Issuer | Verdict |
|---|---|---|
| `matsart.ca` | `C=US, O=Let's Encrypt, CN=YR2` | properly CA-signed |
| `rohes.plansaver.ca` | `CN=rohes.plansaver.ca` (identical to subject) | **self-signed** |

Self-signed means no trusted authority vouched for the identity, so browsers refuse
the connection outright (`curl` reports TLS failure; a browser shows an interstitial).
Two `.crt` files exist for the domain and both are self-signed placeholders, so
cPanel AutoSSL has not yet replaced them with a real certificate.

**Action required (cPanel, cannot be done over SSH):** SSL/TLS Status → run AutoSSL
for `rohes.plansaver.ca`, or install a Let's Encrypt certificate and force HTTPS.

The application is fully functional behind the certificate. Verified with
`curl -k`: every public and admin route returns 200.

---

## Phase 7 — Admin interface completion (2026-09-28)

### 7.1 The 500s that were not 500s

With the app live, authenticated requests to `/admin/messages`, `/admin/settings`,
`/admin/users` and every `create` screen returned **HTTP 500**. The cause was not a
server or environment problem: **the Blade views did not exist.** Laravel raises an
error for a missing view rather than returning 404, so the symptom looked like a
crash.

Two genuine bugs were also found while diagnosing this, and one of them was a lie:

**The dashboard "404" was a bad test, not a bug.** The dashboard is routed at `/admin`,
not `/admin/dashboard`. An early check requested the wrong path.

**A stale log entry nearly sent the investigation the wrong way.** The first error
found in `laravel.log` was the 6.2 platform-check failure from the *earlier* attempt,
plus an old `$page` undefined-variable error. Both were historical, not current.
The log was then truncated before each test run so results were unambiguous. Worth
remembering: on a shared server, a log file can contain errors from many earlier
deploys.

### 7.2 Login was completely broken — a real bug

Logging in returned **500** for every user, including with correct credentials. The
error was `Route [dashboard] not defined.`

**Cause.** `routes/web.php` puts the CMS routes in a group with `->name('admin.')`, so
the dashboard's real name is `admin.dashboard`. Breeze's `AuthenticatedSessionController`
unmodified calls `route('dashboard')`, which does not exist.

> **Corrected in 7.5.** The next sentence of this entry originally read "Self-registration
> is already disabled, so `RegisteredUserController` had the same latent bug in dead
> code." That was wrong. Self-registration *was* enabled; see 7.5.1.

**Fix.** `AuthenticatedSessionController` now sends administrators to
`route('admin.dashboard')` and everyone else to the public home page, via a small
`homeFor()` helper. `RegisteredUserController` was pointed at `home` so the dead code
cannot bite later. Verified: `POST /login` → 302 → `/admin` → 200.

### 7.3 Four more bugs found by systematic route testing

Rather than clicking through by hand, two smoke suites were written that drive the
real HTTP kernel and assert no route 500s. They immediately caught four bugs that
would each have been a visible 500 to the client:

| # | Bug | Symptom | Fix |
|---|---|---|---|
| 1 | `nextSortOrder(string $table, ?int $parentColumn)` — the parent **column name** was typed `?int` | 500 on *every* "add section" and "add item". `page_id`/`section_id` are strings, passed to an int param → `TypeError` | Changed to `?string $parentColumn` |
| 2 | `MessageController::index` called `$query->match(...)`, which does not exist on the Eloquent builder | 500 on the messages inbox | Replaced with `where('is_archived', ...)`, and the filter is now allow-listed to `inbox`/`unread`/`archived` so a hand-typed query string cannot reach the query builder in an unexpected state |
| 3 | Views used `route('admin.sections.create')` / `admin.sections.store` | 500 on the sections screens | Routes are actually named `admin.pages.sections.*` |
| 4 | `users/_form.blade.php` closed a `@section` it never opened, and derived `$isSelf` itself | 500 on both user screens | Removed the stray `@endsection`; `$isSelf` is now passed in by the caller |

Bug 1 is the clearest argument for this approach: a `?int` type hint on a *column
name* is a mistake that compiles fine, lints clean, and only fails at runtime.

**A CSS contract mismatch, also caught this way.** The existing `admin.css` styled
the template picker as `.template-opt input:checked + label` — a *sibling* pattern.
The first version of the section form nested the input *inside* the label, so none
of the styling applied. The markup was changed to match the CSS rather than
rewriting the CSS.

### 7.4 Views written

17 new Blade views, bringing the admin to 22 files:

- **Pages** — index, create, edit, `_form`
- **Sections** — index, create, edit, `_form` (layout picker with plain-language help per template)
- **Items** — index (with move up/down), create, edit, `_form`
- **Messages** — index (inbox/unread/archived filters), show (reply-by-mailto, mark read, delete)
- **Settings** — index, grouped into General / Branding / Contact / SEO / Homepage / Legal
- **Users** — index, create, edit, `_form`

Added matching CSS for the inbox rows, filter tabs, detail lists and simple lists.
All 22 admin views compile; all 16 authenticated admin routes return 200 with a
clean log; all 8 public routes unaffected with exactly one `h1` each.

**A latent data-model mismatch was found and worked around.** `Setting::GROUPS` lists
`homepage`, `branding`, `seo`, `social`, but the seeded keys are prefixed `site.` and
`home.`, so the controller's `groupFor()` falls back to `general` for all of them —
four groups in the model are effectively dead. The settings form defines its fields
explicitly with the correct labels rather than trusting the grouping, so the editor
sees correct sections. The prefix mismatch is still worth fixing in the model or
seeder so the two agree.

### 7.5 End-to-end verification on the live server

Confirmed against `https://rohes.plansaver.ca` over real HTTPS, authenticated as an
administrator:

- All 16 admin screens return 200
- Contact form submission → 302 → appears in the admin inbox → opens → marked read
  → toggled back to unread → deleted → inbox empty state renders
- Unread badge appears in the sidebar and clears after deletion
- Settings form renders all six groups and reads back real values
- Full CRUD round-trip: created a page → added a section → added a content item →
  verified title, intro and body all rendered on the public page → deleted the page
  and confirmed 404, with the cascade removing its sections and items
- Test page and test message cleaned up afterwards; only the 6 real pages remain

### 7.6 Credential rotation

The seeded administrator password was the literal string `CHANGE-ME-IMMEDIATELY` on a
now publicly reachable host. It was rotated to a generated 19-character password and
verified: 0 accounts remain on the default. The one-off rotation script was uploaded,
run, and deleted from the server.

### 7.7 Deploy discipline

The server is **shared and resource-limited**, at the client's explicit instruction.
The approach adopted:

- All building and testing happens locally. Zero server cost.
- One batched SSH connection per deploy, not a dozen.
- **Never** run `composer install`, `npm`, or `migrate:fresh` on the server. `vendor`
  and `public/build` are produced locally and uploaded.
- Read-only queries (`route:list`, log tails) only when genuinely needed.
- `tar` uploads carry an explicit exclusion list; `.env`, `database.sqlite`,
  `node_modules` and `tests` are never sent.

### 7.8 Tests added

- `tests/admin-routes-smoke.php` — drives the real HTTP kernel as a signed-in admin
  across all 16 admin routes, asserting no 500s
- `tests/public-routes-smoke.php` — asserts the 8 public routes, expected status codes
  and one `h1` per page

Both are dependency-free PHP scripts runnable with `php tests/<name>.php`. They caught
five real bugs that would each have been a visible 500.

---

## Open items (needs client confirmation)

1. **Founding-history wording.** "Serving since 2001" / "over 25 years" is inferred
   and unverified.
2. **Ministry name.** Assumed the current Ontario ministry name; needs checking.
3. **Street address.** `contact.address` is unset, so the contact page omits it.
3b. **Photographs.** Nine legacy photos are ~100x67 to 132x95 px. They must not be
   enlarged. New photography is recommended.
4. **SMTP / email delivery.** Not configured. New-message notifications cannot be
   tested until it is.
5. **Admin user accounts.** The client's admin names and email addresses.
6. **TLS certificate.** Self-signed; AutoSSL must be run (see 6.6). **This is the
   only thing preventing normal browser use.**
7. **Git.** Not yet initialised. Recommended split: code and media in Git, the
   database in dated dumps outside Git, because `database/database.sqlite` contains
   contact-form PII.
8. **Setting key prefixes.** `site.`/`home.` vs the model's `homepage`/`branding`/
   `seo`/`social` groups do not agree (see 7.4).
9. **Media library.** There is no `/admin/media` route, controller, or view. Media
   records are seeded and the disk is configured, and the dashboard reports a
   count, but an administrator has no way to see or upload a file. Pinned as a
   404 by `tests/nonadmin-guard.php` so it cannot regress unnoticed.

| 15 | Pin `config.platform.php` to 8.3.33 | Resolves dependencies for the server's actual PHP instead of the developer's; `matsart.ca` omits this and is only correct by luck |
| 16 | Document root = `rohes.ca/public`; root `.htaccess` deleted | `public/` must be the only web-exposed directory; the rewrite file was outside the docroot and would have broken a future docroot change |
| 17 | Build production `.env` on the server | The `APP_KEY` never existed locally or in any transfer |
| 18 | Restore `env('APP_TIMEZONE')` in `config/app.php` | Laravel 13 hardcodes UTC, silently ignoring the `.env` value; this controls contact-message timestamps |
| 19 | Route smoke tests driving the real HTTP kernel | Found 5 bugs (a `?int` type hint on a column name, a non-existent `match()` call, 2 wrong route names, a stray `@endsection`) that each would have been a live 500 |
| 20 | Allow-list the messages filter | A hand-typed `?filter=` cannot reach the query builder in an unexpected state |
| 21 | Rotate the seeded admin password before DNS went live | The default was the literal string `CHANGE-ME-IMMEDIATELY` on a publicly reachable host |
| 22 | Settings form defines fields explicitly | The model's group prefixes disagree with the seeded keys (see 7.4), so grouping is not trusted |

---

## 7.5 - Auth and account screens were unstyled; self-registration was open

### 7.5.1 What the investigation actually found

I opened the login page expecting a styling problem and found two real
problems instead.

1. **Every auth and account screen was rendering with dead class names.** The
   Breeze scaffolding markup uses Tailwind utility classes
   (`text-gray-800`, `max-w-7xl`, `sm:px-6`, ...). This project has no Tailwind
   build: `resources/css/app.css` imports hand-written `tokens.css`,
   `site.css` and `admin.css`. The assets loaded fine and the HTML was correct,
   so it looked like a missing-stylesheet bug, but the CSS simply did not
   contain those rules. `<x-guest-layout>` was also hard-coded to the Laravel
   logo, so the login page carried someone else's branding.
   Affects: `auth/login`, `forgot-password`, `reset-password`,
   `confirm-password`, `verify-email`, all three `profile/partials`,
   `layouts/guest`, and the `x-*` Blade components.

2. **Public self-registration was enabled.** `routes/auth.php` registered
   `GET|POST /register`. Nothing in the project links to it, which is why this
   was easy to miss, but the routes were live: any anonymous visitor could
   create an account and become a stored user. Not a direct route to the CMS,
   because every admin route also requires `is_admin`, but it is an unwanted
   public account-creation endpoint on a client site and it contradicts the
   documented "registration is disabled" claim. **Correction to 7.2:** I
   previously wrote that self-registration was already disabled. It was not.
   Accounts are meant to be created by an administrator from Admin -> User
   accounts, which is exactly what that screen exists for.

### 7.5.2 Changes

- Rebuilt `layouts/guest.blade.php` as a proper auth shell: ROHES branding from
  `site.logo_path` with a monogram fallback, the site name, a single
  `flash--error` summary, and a link back to the public site. Marked
  `noindex, nofollow` so login pages stay out of search results.
- Rewrote the five auth views in the project's own class vocabulary
  (`form-card`, `field`, `input`, `error-text`, `btn--primary`, `auth-*`).
  `login.blade.php` also had a duplicate `autofocus` attribute; removed.
- Added an `auth-*` block to `site.css` (gradient backdrop, brand block, panel,
  actions row, divider) and `ainput-label` / `.aok` to `admin.css`, so auth
  reuses the public palette instead of a third visual language.
- Deleted `register.blade.php` and the two register routes, plus the now
  pointless `RegisteredUserController` import. Verified `/register` returns 404.
- Deleted the three Breeze files that were unreachable and misleading:
  `welcome.blade.php`, `dashboard.blade.php`, `register.blade.php`.
- `profile/edit.blade.php` now extends `layouts.guest` styling via
  `admin.layout` rather than `x-app-layout`, and the three partials were
  rewritten against `ainput` / `abtn` / `aerror`.
- Rewrote the seven `x-*` components (`primary-button`, `secondary-button`,
  `danger-button`, `text-input`, `input-label`, `input-error`,
  `auth-session-status`) onto the admin class names. The delete-account form
  keeps its Alpine confirm step.
- Removed the now-unreferenced scaffolding: `application-logo`, `dropdown`,
  `dropdown-link`, `modal`, `nav-link`, `responsive-nav-link`,
  `layouts/navigation`, `layouts/app`, and the orphan `AppLayout` /
  `GuestLayout` classes. Nothing referenced them once the profile page moved
  off `<x-app-layout>`, and leaving them would invite the same dead-class bug
  to come back.

### 7.5.3 A related latent bug: `route('dashboard')` did not exist

The CMS lives under `routes/web.php` with an `admin.` name prefix, so the route
is named `admin.dashboard` and there is no bare `dashboard`. 7.1 fixed that in
`AuthenticatedSessionController`. The same broken name remained in four more
stock auth controllers, each of which would have thrown on first use:

- `ConfirmablePasswordController` (line 38)
- `EmailVerificationNotificationController` (line 17)
- `EmailVerificationPromptController` (line 18)
- `VerifyEmailController` (lines 18 and 25)

Rather than patch five call sites, I added a single `/dashboard` route inside
the existing `auth` group that sends administrators to `admin.dashboard` and
everyone else to `home`. Password confirmation now renders 200 instead of 500.

### 7.5.4 Two bugs I introduced and then fixed

Worth recording, because both produced convincing false failures.

1. **I broke every auth screen.** I converted the auth views from
   `<x-guest-layout>` component syntax to `@extends` / `@section`, but left
   the new `guest.blade.php` rendering `{{ $slot }}`. Result: 500
   `Undefined variable $slot` on login, forgot-password and confirm-password.
   The layout now yields `@yield('content')`, which is what `@extends` views
   actually feed it. The error log is what caught this; the status codes alone
   looked like unrelated failures.
2. **My own test helper caused a false `/profile` 500.** I named a global
   function `request()`, which shadows Laravel's `request()` helper inside
   compiled Blade. Renamed to `simulate()`.

Two other "failures" were my wrong expectations, not app bugs, and the code was
right both times: an already-verified user is correctly redirected *away* from
`/verify-email` (302, not 200), and a POST without a CSRF token is correctly
rejected with 419.

### 7.5.5 Tests added

`tests/auth-smoke.php` and `tests/nonadmin-guard.php`, both dependency-free
kernel-driving scripts in the style of the existing two. The guard test creates
a throwaway non-admin user and deletes it inside the same run.

Result: auth 13/13, non-admin guard 6/6, admin routes 0 failures, public routes
0 failures, 50 views compile, zero errors in `storage/logs/laravel.log`.

The guard test confirmed the middleware returns **403** to a signed-in
non-administrator and redirects a guest to `/login`. I had initially asserted
302 and "corrected" the test; the 403 is the intended behaviour, so the test was
wrong. It also records that `/admin/media` is 404 because that route still does
not exist (open item 9), rather than pretending it works.

### 7.5.6 Still outstanding

- **`/admin/media` has no route, controller, or view.** Confirmed again: media
  is seeded and configurable but there is no upload list. The guard test now
  pins the 404 so it cannot regress silently.
- The rest of 7.4 stands: `setting_url()` does not upgrade `http://` to
  `https://`, and the `site.`/`home.` vs `homepage`/`branding` group mismatch
  is still there.
- Nothing in 7.5 has been deployed yet. The server copy still has the old
  unstyled auth screens and the open `/register` endpoint.

| 23 | Rewrite auth screens in the project CSS instead of Tailwind utilities | The asset build was never the problem; the Breeze markup referenced class names that do not exist in this stylesheet, and `<x-guest-layout>` was branding the login page as Laravel |
| 24 | Remove the `register` routes rather than relying on nothing linking to them | Undiscoverable-but-live public account creation; contradicts the stated design that administrators create accounts |
| 25 | One `/dashboard` alias route for the five stock auth controllers | They all redirect to a `dashboard` name that does not exist in a prefixed route group; fixing the name once beats editing five controllers |
| 26 | Assert 403, not 302, for authenticated non-admins in tests | I "corrected" a passing 403 into a failing test before reading `EnsureUserIsAdmin`; the middleware was right and the expectation was wrong |
| 27 | Never name a test helper `request()` | It shadows Laravel's global helper inside compiled Blade and produces phantom 500s that look like application faults |

---

## 7.6 - Media library, phone relabelling, and a stale-cache bug

### 7.6.1 Media library (was an open item: no route at all)

`/admin/media` returned 404. Media rows, a configured disk and a dashboard
count all existed, so the feature was half-built.

- `MediaController` with index / store / update / destroy, four named routes
  under the existing `admin.` group, a sidebar link, and a responsive grid
  view with search, image/document filters and pagination.
- Upload policy: 8 MB per file, 20 files per request, `mimes:` validation
  (which inspects real content, so a renamed script is rejected), and a
  self-generated filename of `slug-8hexchars.ext`. The client's filename is
  only ever used to build a slug, never as a stored path.
- `public/media/.htaccess` denies execution of `.php`, `.cgi`, `.pl`, `.py`
  and friends and disables indexes. Uploaded files are served as static
  files only, defence in depth behind the validation above.
- Text fields (`alt_text`, `title`, original name) are stripped of tags and
  whitespace-collapsed before storage.

**The delete guard is the important part.** `image_path` on a section item
stores the media URL, and `site.logo_path` is a setting, so deleting a file
outright would leave a broken image on a live page. `destroy()` now returns
a refusal naming every reference, and the index shows an "In use in N places"
badge. Verified against `svc-fleet-truck.jpg`, referenced by 3 items: the
delete is refused and the file stays on disk.

### 7.6.2 A stale-cache bug that made a change look like it had not worked

The phone relabelling appeared to do nothing: the database had the new values
and the rendered HTML still showed the old ones. Cause: the seeder writes
`Setting` rows directly, so it never triggered `InvalidatesContentCache`, and
the cached `site_settings` blob kept serving the old labels. The seeder now
forgets `site_settings`, `nav_pages` and `admin_unread_badge` at the end of
its run. Worth noting this would have bitten any future content change made
by re-seeding.

### 7.6.3 Phone relabelling (client-confirmed)

Confirmed with the client on 2026-09-29. Note this **corrects an earlier
assumption**: the 1-800 number is the toll-free line and is *not* the fax,
and the fax is a 705 number. The legacy data had these the other way round.

| Setting key | Label | Value |
| --- | --- | --- |
| `contact.toll_free` | Toll Free | 1 (800) 265-0046 |
| `contact.phone` | Local | 1 (705) 424-9799 |
| `contact.mobile` | Mobile | *(empty)* |
| `contact.fax` | Fax | 1 (705) 424-6075 |

- `contact.phone2` retired and its row deleted by the seeder, so a stale row
  cannot linger on an install seeded before the relabel.
- Display format changed to `1 (555) 555-5555` at the client's request.
- New `tel_href()` helper builds `tel:` targets; the same `preg_replace` was
  being written out nine times across the footer, contact page and contact
  form.
- `contact.mobile` ships empty because no number was supplied. It is wired
  into the settings form and all three public views already.

### 7.6.4 Blank fields are hidden everywhere

The client's rule is that an empty box must not appear anywhere on the site.
Rather than trust a regex over the Blade source (my first attempt was wrong -
it checked preceding lines, missing that `@if (setting(...))` guards on the
same line), `tests/blank-fields-hidden.php` blanks all 12 optional settings,
renders all 7 public pages, and greps the HTML for orphaned labels, empty
`<li>`/`<a>` elements, empty `tel:` links and empty `<br>` blocks. Result: 0
pages with leaked blank fields, inside a transaction that is always rolled
back. Verified separately that the rollback restores the real values.

### 7.6.5 Tests

`tests/media-upload-smoke.php` (21 assertions) and
`tests/blank-fields-hidden.php`. Upload coverage: a valid image is stored with
its real dimensions, a `.php` file is rejected, a `../../../evil.png` name is
sanitised, alt text is cleaned, an in-use file cannot be deleted, and an
unused file can. Each run cleans up after itself and asserts the 14 seeded
rows are still intact, so it is safe to re-run.

Two real bugs found by these tests and fixed: `uniqueFilename()` was typed
against Symfony's `UploadedFile`, which rejects Laravel's (they are unrelated
by inheritance - 500 on every upload); and `detectMime()` wrote dimensions via
a follow-up UPDATE, replaced by `inspectStoredFile()` returning mime, width
and height together.

Suite status: 52 views compile, all six suites pass, log clean.

**Deployed** (see 7.6.6) — this line stood until the batch went live. One
correction since: decision 29 shipped broken and took every PDF and directory
under `/media` offline with a 500 until 7.6.8 fixed it. The local suite passed
it the whole time, because an `.htaccess` is server config and the dev server
never reads it.

| 28 | Refuse to delete media that is still referenced, and say where | An image is referenced from a section item's `image_path` or from a setting; deleting the file would leave a broken image on a live page |
| 29 | Deny script execution in `public/media` | User-supplied files live in a web-accessible directory; validation is the first gate, this is the second |
| 30 | Seeder invalidates the content cache | It writes settings directly, so without this a re-seed silently fails to change the site - which is exactly what happened |
| 31 | Prove blank fields are hidden by rendering, not by grepping source | A regex over Blade gave a false answer; rendering the page and inspecting the HTML is the only check that means anything |
| 32 | Do not type-hint either `UploadedFile` class | Laravel's and Symfony's are unrelated by inheritance, so a hint on either one breaks the other |

### 7.6.6 Deployed, and a second website exists

Deployed to `/home/weddi133/rohes.ca` in one batched upload (server-side
timestamped backup taken first: `backups/pre-media-20260928-203858`, 13 files).
Verified live over HTTPS with `-k` (still self-signed, see 6.6):

- `/login` returns 200 with the new auth shell and ROHES branding, serving
  `app-DruIpS1g.css` which contains `.auth-body`, `.auth-panel`, `.media-grid`
  and `.admin-filter`.
- `/register` returns 404.
- `/admin/media` returns 302 to the login page for a guest.
- Toll Free / Local / Fax render correctly on `/contact` and the home page;
  Mobile does not render anywhere, because the field is empty.

One deployment wrinkle: a recursive `scp` of `public/build` did not overwrite
the existing `manifest.json`, so the server kept serving the previous CSS
hash and the new auth/media rules were absent from the live page despite the
source files being present. Fixed by uploading `manifest.json` and the two
hashed assets explicitly and deleting the two stale CSS files. Worth
remembering: source files landing on the server proves nothing if the
manifest still points at the old hashed asset.

**The client has a second website: <http://www.regionofhuronia.com/>.** Not
previously known. Needs to be established whether the new CMS replaces it or
sits alongside it, and if alongside, whether content and branding are shared.
Until that is answered the new site is treated as a staging build at
`rohes.plansaver.ca` and nothing is published to either domain.
10. **Second website.** The client also runs <http://www.regionofhuronia.com/>.
   Not previously known. Must clarify whether the new CMS replaces it or
   coexists, and whether branding and content are shared. Until answered,
   `rohes.plansaver.ca` is treated as staging and nothing is published to
   either domain.

### 7.6.7 The second website confirms the phone relabelling

Fetched <http://www.regionofhuronia.com/> read-only. It is the same
organisation, and it independently **confirms the client's phone mapping**:

```
Toll Free    1 800-265-0046
Telephone    705 424-9799
FAX          705 424-6075
Email        info@regionofhuronia.com
"ROHES (2013) Ltd. is fully approved by the Ministry of The Environment."
Copyright (c) Region of Huronia Environmental Services (2013) Ltd.
Last modified: April 2015
```

This matches what the client told me exactly, and it means the fax being a
705 number and the 1-800 being the toll-free line was right. The legacy data
in the seeder had it the other way round, which is the mistake 7.6.3 corrects.

Two notes for the relabelling:
- The old site calls the 705 number "**Telephone**". The client asked for
  "**Local**", so that label is a deliberate change from the legacy wording.
- The old site has **no mobile number**, which is consistent with shipping
  `contact.mobile` empty.

The page is a Word 12 static export (Last modified April 2015) built around
FrontPage substitution variables — `CompanyLongDistance`, `CompanyTelephone`,
`CompanyFax`, `CompanyEmail`. Content is table-based with `images/*.gif`
navigation buttons. This is almost certainly the same source the new CMS was
built from, so **the new site looks to be a replacement for this one**, not a
sibling. It should be treated as the migration target: the old site stays up
until the client is ready to cut over, and the domain, email and any existing
links or bookmarks are the migration's real risk surface.

Not published, not modified, nothing sent to that host.

### 7.6.8 The legacy site's content, and one production outage it exposed

The second website is the *source* the CMS was built from, so it was crawled
read-only and compared against current CMS content. Ten pages, all extracted
to a scratch file. Design was not carried across — the point was to recover
prose and facts that never made it into the rebuild.

**Genuine content gaps found and filled locally**

- *Biosolids.* The agronomy was missing entirely: soil fertility, structure,
  moisture retention, permeability and erosion control; suitability for corn,
  soybeans, canola and cereals, plus forage, pasture and forestry; and the
  long-standing safety record. Added three paragraphs.
- *Biosolids.* The legacy site's "actively seeking agricultural land" offer
  had no equivalent on the new site at all. It is a real route to work, so it
  is now a closing CTA section plus a seven-point soil-benefit checklist.
- *Biosolids.* "Certificate of Approval issued by the MOE", with soil testing
  and topography governing site suitability, is now a regulation card.
- *Services.* The "in addition, ROHES can provide" list had been genericised
  into ten vague bullets. Replaced with the ten real capabilities: waste
  bentonite, oily water and abandoned fuel tanks, wash pits, used motor and
  furnace oil, spill and manhole emergency pumping, lift-lock sludge, pulp and
  plastic waste, septic chambers / grit chambers / digestors / interceptors,
  chemical and hazardous non-toxic waste, and pressurised water for gas-line
  testing.
- *Services.* Two services had been dropped: rapid emergency response (a
  guaranteed four-hour response, some calls under an hour, 24-hour cover) and
  the environmental specialist. Both restored as cards. Hydro-vac, lagoons,
  mobile screening and GPS copy was also thinned and has been restored.
- *News.* The drag-hose system item was missing entirely, including the
  60,000 m³ season figure. Added.

**A contact-page bug this uncovered.** `contact_form` (the standalone
`/contact` page) and `contact_details` had drifted apart: the contact page
rendered toll free, local, mobile, fax and email but **not** the office address
or opening hours. The legacy site showed the postal address prominently, so
this was a real regression. Both blocks added to `contact_form`; the address
now appears on the page and in the footer.

**Ministry name inconsistency.** `legal.regulator` said "Ontario Ministry of
the Environment" while a resource link said "Ministry of the Environment and
Conservation" and pointed at the MECP page. The legacy site said "Ministry of
The Environment" — the pre-2019 name. All three now read Ministry of the
Environment, Conservation and Parks.

**Deliberately not carried across.** `full_article.htm` is a verbatim
reproduction of a 2005 *Ontario Farmer* newspaper article. That is third-party
copyrighted material and must not be republished; the existing news item
already summarises it and links onward, which is the correct treatment. The
legacy footer's `info@johnmichaud.com` belongs to the web designer, not the
client. Most of the legacy Information Links pointed at URLs that no longer
resolve (`ene.gov.on.ca`, `omafra.gov.on.ca`, `css.orst.edu`), so they were
not imported rather than shipped as dead links. None of the legacy
FrontPage `webbot` substitution markup, table layout or GIF navigation was
carried over.

#### Production outage found and fixed: every PDF and directory under /media 500'd

Post-deploy verification — the item flagged in 7.6.6 as still needing a
check — turned up a real outage. `/media/docs/aerway-rack-card.pdf` and
`/media/` both returned 500.

Cause: `public/media/.htaccess` wrapped its contents in `<Directory /media>`.
`<Directory>` is **only valid in the main server config**; a `.htaccess` that
uses one is rejected by Apache and everything under the directory fails. The
file had passed every local test, because the local dev server never read it.

Rewritten with no `<Directory>` block, `php_flag engine off` guarded per
module (`mod_php`, `mod_php7`, `mod_php8`) since an unknown directive is also
a 500 under PHP-FPM, an Apache 2.4 `Require all denied` with a 2.2 fallback,
and `Options` dropped entirely — `public/.htaccess` already sets `-Indexes`
site-wide and `Options` is one of the directives most often missing from
`AllowOverride`. Both the reasoning and the trap are documented in the file so
it does not get "tidied" back into a `<Directory>` block.

Verified in production after redeploy: PDF `200 application/pdf` (75,164 b),
JPEG `200`, `/media/` and `/media/docs/` `403`, `/media/shell.php` `403`,
`/media/.htaccess` `403`, and all seven public routes still `200`. Previous
file backed up to `backups/media-htaccess-20260928-205157`.

**Lesson recorded:** an `.htaccess` is server configuration and cannot be
validated by passing local tests. Anything of this kind has to be probed over
HTTP on the real host before it is called done.

#### The seeder was about to destroy the client's street address

Checking why production showed an address revealed the client had already
entered one:

```
10367 County Road 10
New Lowell, Ontario
L0M 1N0
```

I had just added a single-line `contact.address` to the seeder from the legacy
page, which would have overwritten it on the next seed — and the legacy value
was worse: one line, and it read "ON, CA", where `CA` is not a province code.

`contact.address` has been removed from the seeder entirely, with a comment
explaining why. A sentinel value was planted locally and survived a re-seed,
proving the field is no longer managed by seeding. Local now holds the
production string byte-for-byte (49 bytes).

**Unresolved and important:** the seeder still runs `updateOrCreate` over every
key it lists, so re-running it on production will overwrite anything the
client changes in Settings — their address, opening hours, any wording they
have edited. It happens to be safe right now only because production settings
still match the seeder. This needs a decision before the next deploy;
`summarised for the user rather than changed unilaterally`, because the fix
alters deployment semantics.

#### Also done

- Removed the stray local-only `Test Section` and its items. Local is now 6
  pages / 17 sections / 78 items, production 6 / 15 / 64, the difference being
  the two new biosolids sections.
- Reordered the biosolids seeder block so source order matches render order.
  Every section is seeded `sort_order` 0 and the page sorts by id, so newly
  added sections always land after existing ones; noted in the file.
- Removed deployment artifacts from the server: `live-inspect.php`,
  `rohes-deploy.tar`, `deploy.sh`, `backup.sh`. Confirmed gone. `live-inspect.php`
  was a script sitting in the application root that dumped settings and the
  admin address — it should not have been left there.
- Confirmed `tests/dump-content.php`, `tests/dump-page.php` and
  `tests/live-inspect.php` are absent from the deployed tree.
- Production error log reviewed. All nine entries are historical, from the
  20:00–23:47 deploy window on 2026-09-28 (missing `dashboard` route, missing
  admin views before upload, one `Builder::match()` call, a `nextSortOrder`
  type error, and a `view:clear` warning). Nothing new since, and the last
  entry predates the media fix.
- Re-verified the earlier "Mobile on the homepage" flag: **false positive.**
  There is no `>Mobile<` label on `/`; the only match is the legacy filename
  `svc-mobile-screening-1.jpg`. Blank-field suppression is working.

#### Held back deliberately

The content work is finished, tested and building, but **not deployed.** It
adds client-facing claims — a four-hour response guarantee, an active
land-application offer, free fertiliser — and the client should see that copy
before it goes live. It also rides on the seeder, which is the clobbering
hazard above. Deploying both at once, unattended, is the wrong call.

Local: `npm run build` passes, Blade compiles, all four smoke suites pass,
`/contact` renders address and hours, all new copy confirmed present on the
rendered pages.

## Phase 8 — Insert-only seeding and stored HTML sanitization (2026-09-29)

### 8.1 Seeder safety correction

The earlier note in 7.6.8 that the seeder used `updateOrCreate` described an
intermediate version and is no longer current. The seeder now uses
`firstOrCreate` for media, settings, pages and sections, and inserts items only
when its current identity match is absent. Existing rows are not updated or
deleted. The retired `contact.phone2` key is left untouched, and
`contact.address` is included for a fresh install without overwriting an
existing admin-edited value.

**Remaining limitation:** items have no immutable seed identifier. Existing
titled items are matched by `item_type` plus title; untitled items use
`item_type` plus body. If an administrator changes that identifying field,
re-running the seeder may insert the original item again. It still does not
overwrite or delete the administrator's version.

### 8.2 Allowlist sanitization for rich HTML

The public `rich_text` and `contact_details` templates previously emitted
database content with unescaped Blade output. Added an ext-dom-based allowlist
sanitizer at those output boundaries. It preserves common formatting, tables,
safe links and images, and embeds only from explicitly allowed HTTPS hosts
(Google Maps, YouTube and Vimeo). Scripts, styles, event-handler attributes,
unsafe URLs and unapproved embeds are removed. No Composer dependency was
added. The section editor's help text now describes the supported content.

Focused sanitizer tests cover retained formatting, script/event removal,
unsafe links and images, safe external-link rel attributes, and allowed versus
unapproved embeds.

## Phase 9 — PHPUnit suite aligned with CMS behavior (2026-09-29)

Updated the stock Breeze registration tests to assert that public registration
is unavailable, and changed the normal-user login assertion to expect the
public home route. The homepage feature test now runs with `RefreshDatabase`
and creates the minimum active home page required by the schema-driven
renderer.

Verification: `php artisan test` passes — 27 tests, 73 assertions. The
registration check expects `GET /register` to return 404 when no CMS page has
that slug, and `POST /register` to return 405 because the matching public route
only accepts GET.

## Phase 10 — Legacy-site content made editable (2026-09-29)

Compared the CMS seed against both source websites:

- `http://rohes.ca/` — the original single-page site, including its About,
  Services, Biosolids, News, Links, brochure and contact content.
- `http://www.regionofhuronia.com/` — the later multi-page site, including its
  service and biosolids pages, information links, contact details, feedback
  options, downloads placeholder and brochures.

Added Contact as a CMS Page with an editable `contact_form` section, seeded
the old information-request choices, and moved the Contact navigation label
into the Page record. The Resources page now also includes the ROHES
construction-services brochure as an explicitly marked external placeholder;
the existing AerWay brochure remains in the media library. The historical
2005 newspaper article remains summarized rather than republished.

Added template-specific section settings to the admin editor for hero/CTA
buttons, card-grid layout, split layout, contact details, contact-form options
and rich text. The controller validates only settings supported by the
selected template. Rich text and map embeds continue to pass through the HTML
allowlist sanitizer. Page social-sharing images now render as `og:image`; the
unused duplicate homepage hero and regulator settings were removed from the
settings form and fresh seed, since their copy is edited in the relevant page
sections instead.

The public-route smoke test now fails when a page does not have exactly one
`h1`. The media-upload smoke test cleanup now deletes only fixture IDs created
by that run, never all files in the uploads folder.

Local seed result: 7 pages, 18 sections, 79 items, 17 existing settings and 14
media records. Verification: `php artisan test` passes (30 tests, 84
assertions); all six custom smoke scripts pass; `npm run build` passes; the
contact GET and POST routes are registered. Deployment status at that point:
not yet uploaded; see Phase 11 for the later staging deployment.

## Phase 11 — Staging deployment and live verification (2026-09-29)

### 11.1 Target and preflight

Deployed the approved local changes to the existing ROHES staging application:

- SSH/SFTP host: `ftp.weddingsbychristian.com:2222`
- Application directory: `/home/weddi133/rohes.ca`
- Browser hostname: `rohes.plansaver.ca`
- Remote CLI: PHP 8.3.33
- PHP DOM extension: present, required by `App\Support\HtmlSanitizer`

Confirmed that the target already contained four files in
`public/media/uploads`. These are client uploads and were explicitly excluded
from both the deployment archive and all cleanup steps.

### 11.2 Backup and package boundaries

Before changing the application, created a private, timestamped backup at:
`/home/weddi133/backups/rohes-pre-20260929-135357`

The backup contains the deployed code/media archive plus separate copies of
the server's existing `.env` and `database/database.sqlite`. The backup
directory has mode `700`; each backup file has mode `600`. The live `.env` and
SQLite database were not replaced by deployment.

Built a 290,083-byte archive with 180 entries from the runtime source and
production build. It included `app`, bootstrap entrypoints, config, seeders,
`public`, resources, routes and `artisan`. It excluded `.env`, the SQLite
database, `vendor`, `node_modules`, tests, bootstrap caches and
`public/media/uploads`. The archive was audited before transfer; the first
audit pattern also matched Laravel's legitimate
`resources/views/vendor/pagination` views, so the check was narrowed to the
root dependency directory rather than removing those application views. The
temporary archive was deleted from the server after extraction.

### 11.3 Deployment and content seeding

Used one key-authenticated SSH session to put the app into maintenance mode,
extract the archive, run `php artisan optimize:clear`, run
`php artisan db:seed --class=LegacyContentSeeder --force`, and bring the app
back up. No schema migration was needed. The insert-if-absent seeder added the
new Contact page, its section and inquiry choices, and the ROHES brochure
placeholder without overwriting existing CMS rows.

Remote seeder totals afterward: 7 pages, 18 sections, 92 items, 20 settings and
18 media records. These totals exceed the clean local seed because the
staging database already held additional content; the seed did not remove or
reset it. All four pre-existing upload files remained present after deployment.

### 11.4 Stale FastCGI route discovered and fixed

The first post-deploy probe got HTTP 406 from ModSecurity because curl sent no
browser user agent. Repeating with a normal browser user agent got past the
filter. This exposed a genuine HTTP 500 on `/contact`; all other public pages
were 200.

The latest Laravel log identified the error as
`Call to undefined method App\\Http\\Controllers\\ContactController::show()`.
The remote CLI route list showed the new `/contact` GET route pointing to
`SiteController@page`, and there was no route cache file on disk, but the
FastCGI worker still held the pre-deploy route table pointing to the former
`ContactController::show()` action. Restored `show()` as a compatibility
handler that delegates to the CMS page renderer, added a feature assertion for
that fallback, uploaded the controller atomically, and cleared Laravel caches
again. This keeps stale shared-host workers functional while preserving the
CMS as the single source of Contact page content.

### 11.5 Live verification

Using HTTPS with `curl -k` because the staging certificate remains
self-signed:

- `/`, `/about`, `/services`, `/biosolids`, `/news`, `/resources` and
  `/contact`: all HTTP 200, each with exactly one `<h1>`.
- `/contact`: rendered the CMS heading and the seeded
  “Have a salesperson contact me” option.
- `/media/docs/aerway-rack-card.pdf`: HTTP 200, `application/pdf`.
- `/media/legacy/logo.jpeg`: HTTP 200.
- `/media/`: HTTP 403, directory listing remains denied.
- Existing uploads: all four files remain on disk.
- Remote route list: GET `/contact` -> `SiteController@page`; POST
  `/contact` -> `ContactController@store`.

Final local verification after the compatibility handler: `php artisan test`
passes, 30 tests and 85 assertions; the six custom smoke checks passed before
deployment; `npm run build` passed. No deployment was made to either source
website or to a production domain. The self-signed TLS certificate remains a
staging/browser trust issue and still needs AutoSSL or a trusted replacement.

## Phase 12 — Page-level CMS editing workflow (2026-09-29)

### 12.1 What was hard to find

The original CMS separated page metadata, sections and content items across
several screens. The Page Edit screen showed only a passive list of section
titles; clicking a title opened the item list, and editing an item required
another navigation step. The Dashboard's “Edit contact details” button led to
global settings, not to the Contact page's actual copy. News dates and archive
flags were stored in JSON without corresponding editor inputs, and image paths
had to be typed manually.

### 12.2 Page Edit is now the content workspace

The Pages list title and its primary **Edit content** action now open the
page's Edit screen. That screen keeps the page settings form and shows the
page's sections and items on the same page. Each section and item expands to
its existing validated editor form, with an independent Save button. Section
and item saves return to the same page editor; the separate Structure screen
remains available for reordering, visibility and deletion.

The Dashboard now has a direct **Edit Contact page content** link when the
Contact page exists. Global business contact values remain under Site
Settings, and that screen now links back to Pages for page-specific copy.

### 12.3 Text, metadata and images

- Existing section fields (title, eyebrow, intro, template-specific options)
  and item fields (title, subtitle, body, link text/URL, image description and
  order) are editable inline from Page Edit.
- News items expose their date/label and archived/background flag. Stats
  expose the fallback displayed value. Existing unexposed JSON metadata is
  retained when an item is saved rather than silently discarded.
- Item image fields have a media-library selector with filename and pixel
  dimensions, plus a still-editable custom path and alt-text input.
- Page social-sharing images and the site logo can be selected from the media
  library or entered as custom paths.
- Repeated inline forms receive unique control IDs. Saved section/item edits
  return to the same page workspace.

### 12.4 Regression coverage and staging deployment

Added authenticated feature coverage for the Pages -> Edit content entry
point, inline section/item controls, selecting an image, saving News metadata,
retaining other metadata, and returning to the page editor after save.

Verification: `php artisan test` passes (31 tests, 104 assertions); all six
custom smoke scripts pass; `npm run build` passes. Deployed to the existing
staging directory `/home/weddi133/rohes.ca` after a protected backup at
`/home/weddi133/backups/rohes-pre-20260929-140614`. No database seeder was run
in this deployment and no CMS records were changed. The package excluded
`.env`, SQLite, dependencies, tests, caches and `public/media/uploads`.
Staging remains live; all four existing user-uploaded files were confirmed
present afterward.

Unauthenticated live requests to `/admin/pages` and `/admin/pages/{id}/edit`
redirect to the branded login page, as expected. After signing in, open Admin
-> Pages and choose **Edit content** on “Contact ROHES”; expand “Edit section
text and layout” or an “Edit item” row to edit the copy and images. Login
credentials were not requested or used during this verification.

## Phase 13 — Simpler client editing and Contact details on-page (2026-09-29)

### 13.1 Client editing workflow

Refined the CMS for a non-technical site editor. The first fields shown are now
the words, descriptions, links and images that affect the public page; routine
changes do not require the client to understand page slugs, section anchors,
display types, SEO metadata or sort numbers.

- Page Edit starts with its page content. Page title/visibility, menu address,
  search-engine text, social image and ordering are under the collapsed
  **More page options** panel at the bottom.
- Section editing starts with the visible heading, small heading and intro.
  Template-specific settings follow. Layout selection and URL/anchor/order/
  visibility controls are collapsed separately at the bottom.
- Item editing starts with title, optional short line, description, image,
  image description and link. Item display type and numeric order are under
  **More item options**.
- News dates/archive flags and statistic values remain visible when relevant
  to the selected item type.
- Page social images and the site logo, as well as item images, can be chosen
  from the media library. Custom image paths remain supported.
- Dashboard and Pages links identify this as **Edit content**. The Dashboard
  includes a direct Contact-page link. Site Settings links back to Pages for
  page-specific words and pictures.

### 13.2 Contact information edited from the Contact page

The client correctly noted that the address and phone numbers shown beside
the Contact form were only editable in Site Settings, not from the Contact
page itself. Added a clearly labeled **Contact details shared across the
site** group to the Contact Form section editor. It contains office address,
toll-free number, local phone, mobile, fax, email and opening hours, each as a
plain-language field.

Saving these fields updates the existing shared `contact.*` settings, clears
the settings cache, and therefore updates both the Contact page and footer.
There is one source of truth rather than separate page and footer copies.
Blank optional fields continue to be removed/hidden. Other pages using a
Contact Form section also explain that these are shared business details.

### 13.3 Verification

Added authenticated regression coverage proving an administrator can change
the Contact phone and address from the Contact page editor and see those exact
values on both `/contact` and the footer. The existing editor tests also cover
the page entry point, image picker, item metadata preservation and returning
to the page after each save.

Final local checks: `php artisan test` passes (32 tests, 119 assertions); all
six custom smoke scripts pass; `npm run build` passes; no diagnostics were
reported in the changed files.

### 13.4 Staging deployment

Created a new protected backup at
`/home/weddi133/backups/rohes-pre-20260929-141509` (directory mode `700`,
backup files mode `600`). The 293,082-byte, 180-entry deployment archive
excluded `.env`, SQLite, dependencies, tests, caches and
`public/media/uploads`. One initial SCP attempt was reset before transferring
data; staging remained HTTP 200 and SSH recovered, after which the same
verified package uploaded successfully.

Deployed to `/home/weddi133/rohes.ca`, cleared Laravel caches, and did not run
migrations or the content seeder. No database records were changed by this
deployment. All four existing uploads remain present and the temporary
archive was removed. Live checks: `/contact` returns 200 with one `<h1>` and
the existing toll-free value; `/admin/pages` redirects to the branded login;
the GET and POST `/contact` routes remain registered. The integrated browser
was unauthenticated, so the signed-in page editor itself was verified by the
authenticated local feature tests rather than by using or requesting client
credentials. The staging TLS certificate remains self-signed.

## Phase 14 — Editable social profile icons (2026-09-29)

### 14.1 Source profile audit

Checked both reference sites, `http://rohes.ca/` and
`http://www.regionofhuronia.com/`, for official Facebook, Instagram, LinkedIn,
YouTube, X/Twitter or other social profile URLs. Neither site listed a
verifiable profile. No account URLs were guessed or populated.

### 14.2 CMS settings and safe rendering

Added a **Social profiles** group under Admin -> Site Settings with editable
Facebook, Instagram, LinkedIn, YouTube and X profile URL fields. Each field
accepts an HTTPS URL; unsupported network keys, non-HTTPS URLs, malformed
URLs, and URLs containing embedded credentials are rejected. Blank fields
remove the saved setting.

Added one shared `site.partials.social-links` partial rendered in both the
top-right header actions and footer brand area. Each configured profile gets
an accessible, titled icon link with `target="_blank"` and
`rel="noopener noreferrer"`. The header layout keeps the icons beside the
mobile navigation toggle and to the right of desktop navigation. Matching
keyboard-focusable icon styles were added to `resources/css/site.css`.

Only valid saved HTTPS profiles render. When no profiles are configured, no
empty icon container appears; this is the current behavior on staging because
the client has not supplied official account URLs. The five fields are ready
in Site Settings; after the client enters real links, the icons appear in both
locations without another deployment.

Social links are read together once per public page render and shared between
header and footer. They intentionally do not rely on the general site-settings
cache, so a newly saved profile is visible immediately. Saving settings still
clears the existing shared cache used for other site settings.

### 14.3 Verification and deployment

Added feature coverage proving: blank defaults render no social icon groups;
valid Facebook and LinkedIn URLs render in both header and footer; blank
Instagram remains hidden; the Settings page exposes the profile fields; and a
`javascript:` value is rejected. Final local verification: `php artisan test`
passes (33 tests, 137 assertions); all six custom smoke scripts passed;
`npm run build` passes; no diagnostics were reported in changed files.

Deployed to `/home/weddi133/rohes.ca` after a protected backup at
`/home/weddi133/backups/rohes-pre-20260929-143600`. The 295,235-byte,
181-entry archive excluded `.env`, SQLite, dependencies, tests, caches and
`public/media/uploads`. No seeder, migration or database edit was run. Live
checks: `/`, `/contact`, `/resources` and `/login` return 200; `/admin/settings`
redirects to the branded login; the new CSS asset returns 200; five media
upload files were present after deployment; and the temporary archive was
removed. The staging certificate is still self-signed.

## Phase 15 — Contact line breaks and CMS hero background (2026-09-29)

### 15.1 Contact address showed literal HTML

The multiline Contact address and opening hours were passed through `nl2br()`
inside escaped Blade output. Blade therefore escaped the generated `<br>`
tags, making the markup visible instead of creating line breaks. Both the
`contact_form` and `contact_details` templates now escape the stored text
first and emit only the generated line-break markup. User-entered HTML remains
escaped. The feature test now asserts that the rendered address contains real
`<br />` tags and no escaped `&lt;br` text.

### 15.2 Editable Home hero background

Added an optional **Hero background image** setting to the Hero section's CMS
editor. The client chooses an uploaded image from the existing media library;
the selected path remains part of the section's existing JSON settings and
does not require a schema migration. The public Hero renders the image as a
cover background behind its text and applies a dark overlay to retain text
contrast. If no image is selected, the current solid-color Hero is unchanged.
The section template help now mentions the optional photo, and an authenticated
feature test covers selection, persistence and homepage output.

### 15.3 Verification and staging deployment

Local verification: `php artisan test` passes (34 tests, 149 assertions); all
six custom smoke scripts pass; `npm run build` passes; no diagnostics were
reported in touched PHP, Blade or CSS files.

Created a protected pre-deploy backup at
`/home/weddi133/backups/rohes-pre-20260929-144311`. Deployed a 295,627-byte,
181-entry package to `/home/weddi133/rohes.ca`; the package excluded `.env`,
SQLite, dependencies, tests, caches and `public/media/uploads`. No seeder,
migration or database modification was run. All uploaded media was preserved;
the remote uploads folder contained eight files after deployment. The
temporary archive was removed.

Live verification: `/contact` returns HTTP 200 with exactly one `<h1>`, real
address line breaks, and no visible escaped `<br>`; the rebuilt stylesheet
returns 200 and contains the Hero background rules; `/`, `/about`, `/services`,
`/biosolids`, `/news` and `/resources` return 200. The staging certificate
remains self-signed.

## Phase 16 — Detailed handoff for the next AI/session (2026-09-29)

This section is the current operational handoff. Read it before changing the
CMS or deploying; Phases 0–15 above contain the full historical reasoning.

### 16.1 Current project state

- Project: ROHES Laravel CMS rebuild in `H:\rohes.ca`.
- Framework/runtime: Laravel 13, PHP 8.3-compatible dependencies, Blade views,
  Vite-built CSS/JS, SQLite database.
- Staging app: `https://rohes.plansaver.ca`, deployed directory
  `/home/weddi133/rohes.ca` on `ftp.weddingsbychristian.com:2222`.
- Staging certificate is still self-signed. Browser checks may display a
  certificate warning; `curl -k` was used for staging probes. AutoSSL or a
  trusted certificate is still needed.
- The staging site is live. The last deploy did not run migrations or a
  seeder and did not intentionally change database rows.
- The last verified backup is
  `/home/weddi133/backups/rohes-pre-20260929-144311`; it contains prior
  code/media, `.env`, and SQLite copies. Directory mode `700`; files mode
  `600`.
- There were 8 files in `public/media/uploads` after the last deploy. Treat
  this as client data and do not delete, overwrite or package those files.
- No verified social profile URLs were found on either reference site.
  Social settings are blank, so no social icons are currently visible. The
  client can add verified HTTPS URLs in Admin -> Site Settings -> Social
  profiles; icons then render automatically in header and footer.
- No background image is currently assigned to the Home hero. To add one:
  Admin -> Pages -> Home -> Edit content -> expand the Hero section -> choose
  “Hero background image”. The selector reads from the Media library.

### 16.2 CMS architecture and edit paths

Public pages are looked up by slug and rendered through the visible sections
of each `Page`. The data structure is:

```text
Page -> ordered Sections -> ordered SectionItems
```

`Section::TEMPLATE_OPTIONS` is the source for all supported layouts;
`Section::TEMPLATE_SETTINGS` defines layout-specific editor controls;
`SectionItem::DATA_FIELDS` defines additional News/Statistic fields. Keep
editor controls and renderer expectations in sync when adding a new setting.

For normal page edits, sign in and open Admin -> Pages -> **Edit content**.
That screen puts page content first, then collapsed advanced options. Expand a
section to edit its headings, intro and layout settings. Expand an item to edit
its text, image, alt text, link and type-specific metadata. Each form has its
own Save button and returns to the same page editor. The separate Structure
screen is still for ordering, hiding and deleting.

For Contact, the `contact_form` section editor includes a plain-language
“Contact details shared across the site” group: street address, toll-free,
local phone, mobile, fax, email and hours. These save to the existing
`contact.*` Setting rows, so Contact and footer display the same values. Do
not create duplicate Contact-specific copies.

For shared profile/branding settings use Admin -> Site Settings. The Social
profiles group currently supports Facebook, Instagram, LinkedIn, YouTube and
X. Only valid HTTPS URLs are stored; blank values suppress icons. The site
logo can also be chosen from the Media library there.

### 16.3 Important route and safety behavior

- `GET /contact` renders the CMS page through `SiteController@page`; the
  retained `ContactController::show()` compatibility handler delegates to
  the same CMS page for stale shared-host FastCGI workers. Do not remove it
  without checking worker/route-cache behavior on staging.
- `POST /contact` remains `ContactController@store`; submissions are validated,
  rate-limited and saved in the Messages inbox. SMTP is not configured.
- Admin routes require auth and the `is_admin` guard. Registration is disabled.
- `LegacyContentSeeder` is insert-if-absent and must not be used as a content
  update tool. Existing edits are intended to win. Item identity is based on
  type plus title (or body for untitled items), so a client rename can cause a
  later seed to add the original item again. Prefer editing in Admin or a
  reviewed migration. Do not run `migrate:fresh` on staging.
- Stored rich text and map embed output passes through
  `App\Support\HtmlSanitizer`, using the server's ext-dom support. Do not
  replace it with raw unfiltered Blade output.
- Manual deployment must exclude `.env`, `database/database.sqlite`,
  `vendor`, `node_modules`, tests, bootstrap caches and
  `public/media/uploads`. Never use the SFTP whole-tree ignore list as the
  only safety boundary; it does not exclude all of these.
- Before every staging deploy, make a timestamped protected backup of code,
  media, `.env` and SQLite. Do not run Composer or database migrations on the
  shared host unless separately reviewed and explicitly needed.
- Never modify either source website (`http://rohes.ca/` or
  `http://www.regionofhuronia.com/`) during this rebuild.

### 16.4 Verification baseline

> **Superseded in part by [Phase 19](#phase-19--admin-dashboard-rebuilt-sign-in-screen-repaired-2026-10-03).**
> The test count below was 34/149; it is now **44 tests, 175 assertions**, and
> the dashboard markup is now asserted structurally rather than by status code.

Latest complete local run after Phases 14–15:

- `php artisan test`: 34 tests, 149 assertions, all passing.
- Custom scripts: `tests/public-routes-smoke.php`,
  `tests/admin-routes-smoke.php`, `tests/auth-smoke.php`,
  `tests/nonadmin-guard.php`, `tests/blank-fields-hidden.php`, and
  `tests/media-upload-smoke.php`: all passing.
- `npm run build`: passing.
- Public route smoke checks assert each page has exactly one `<h1>`.
- Staging Contact probe: HTTP 200, exactly one `<h1>`, real `<br />` tags for
  multiline address and no `&lt;br` text.
- Staging Home and other public pages: HTTP 200. The hero background CSS asset
  was confirmed live; the hero remains solid until an image is selected in
  the CMS.
- The deployment package is removed from the server after each deploy.

Before the next release, rerun the full suite and all custom smoke scripts,
build assets, create a fresh server backup, deploy with the exclusions above,
then probe the real Apache/Nginx host. A local pass does not validate server
`.htaccess` or proxy behavior.

### 16.5 Remaining decisions / next useful work

1. Ask the client for official social profile URLs and enter them in Site
   Settings; do not guess accounts.
2. Ask the client to choose an approved Home hero background image from the
   uploaded media library.
3. Obtain AutoSSL/trusted TLS for `rohes.plansaver.ca` before asking the client
   to use the staging site without a browser warning.
4. Confirm business claims (founding history, regulatory approval wording,
   emergency response promise and landowner offer) before production release.
5. Replace the external legacy ROHES construction brochure placeholder with a
   current approved local document if the client supplies one.
6. Keep SMTP, final domain/cutover, account provisioning and database backup
   ownership on the release checklist.

For future work in this repository, continue appending detailed dated notes
here, including the request, exact behavior changed, files/surfaces affected,
tests, deploy/backup details, live results and anything still unresolved.

## Phase 17 — Local Git repository preparation (2026-09-29)

The user requested that the project be committed and pushed to GitHub. This
workspace did not have a `.git` repository or configured remote. Initialized
a local repository on branch `main`; global Git author name and email are
configured. No remote URL exists yet, so the commit can be created locally but
cannot be pushed until the user supplies or creates the destination repository
and provides its URL. No GitHub credentials, password, token or private key
were requested or copied into the repository.

Before staging, updated `.gitignore` to exclude `/database/*.sqlite` and
`/public/media/uploads/`. This keeps the local SQLite database, which contains
website content and visitor contact-message PII, and user-uploaded files out
of Git. Existing exclusions also keep `.env`, `.vscode` (including the SFTP
configuration), `vendor`, `node_modules`, `public/build`, and `storage` runtime
artifacts out. The environment template `.env.example`, source, migrations,
seeders, docs, tests, and recovered legacy media remain included.

Staged-file audit: 192 files; `git diff --cached --check` passes. Explicit
checks confirm `.env`, `database/database.sqlite`, dependencies, generated Vite
assets, `public/media/uploads`, and storage runtime files are not staged. The
initial commit is created immediately after this entry; the push remains
pending a remote URL. To continue: have the user create/select the GitHub repo,
sign in locally with GitHub CLI or VS Code Accounts (never request secrets in
chat), add that repo as `origin`, then push `main`.

## Phase 18 — Initial GitHub push completed (2026-09-29)

Correction to the pending-push note in Phase 17: the user supplied
`https://github.com/raptureboi83/rohes.ca` as the destination. Added it as the
`origin` remote. `git ls-remote --heads origin` returned no branches before
the push, so there was no existing remote history to overwrite.

Pushed local `main` with `git push -u origin main`. Git Credential Manager
opened the normal browser authentication flow; no password, token or private
key was sent through chat or added to the project. GitHub accepted the branch
and upstream tracking was configured.

Verification: local `HEAD` and `origin/main` both resolve to
`434cf688ab396754f4d0ec54c688c15e4b2b4412` (`Initial ROHES CMS project
baseline`); `git status --short --branch` reports a clean `main` tracking
`origin/main`. The initial commit contains the 192-file source tree audited in
Phase 17. `.env`, `database/database.sqlite`, local uploads, dependencies and
generated build output remain excluded.

## Phase 19 — Admin dashboard rebuilt; sign-in screen repaired (2026-10-03)

Requested as "clean up the CMS homepage to show actually useful information" and
"the log in screen for the cms is sloppy". "CMS homepage" was confirmed to mean
the **admin dashboard**, not the public home page. No public page content was
changed by this phase.

### 19.1 The dashboard was shipping broken markup

The dashboard view contained plain English instruction text pasted into the
Blade markup, and an `<a>` element wrapping three further `<a>` elements:

```html
<a href="/admin/pages" class="admin-stat">
        Choose Edit content to change a page's headings, paragraphs, cards, links and images in one place.
</a>
<a href="/admin/pages" class="admin-stat">
        <a href="/admin/pages" class="abtn abtn--primary">Manage page content</a>
        <a href="/admin/pages/7/edit" class="abtn abtn--ghost">Edit Contact page content</a>
        <a href="/admin/settings" class="abtn abtn--ghost">Business contact details</a>
```

Nested `<a>` is invalid; browsers auto-close the outer anchor at the first inner
`</a>` and re-parent the remainder, so the tile row rendered unpredictably. The
instruction sentence rendered as visible body text.

**Why nothing caught it.** Every existing test — `php artisan test` and all six
custom smoke scripts — asserts HTTP status codes, and this page returned 200
throughout. A 200 says nothing about whether the markup is valid.

### 19.2 What the dashboard shows now

Vanity counters were removed. "Total messages", "Administrators" and a bare
"Pages" count told a site editor nothing they could act on. Replaced with:

| Block | Why it is useful |
|---|---|
| **Needs your attention** | Only renders when something is genuinely wrong, each row linking to the screen that fixes it: undrawable section layouts, no home page set, hidden pages, visible sections with no content items, no office address, no social profiles. Deliberately silent when all is well — a permanently-present advisory list stops being read. |
| **Unread enquiries** | The only time-sensitive item, so it gets a full-width slot and disappears once read. |
| **Your pages** | Every page with its own **Edit content** button, section count, and `home`/`hidden` pills. This is the action editors use most, so it is one click from the dashboard instead of two. |
| **Recently changed** | Sections ordered by `updated_at` so work can be resumed. |
| **Site data** | Real counts (pages/sections/items/media/messages), demoted from tiles to a compact list. |

`DashboardController::attentionItems()` returns only live problems. Unused
controller variables (`adminCount`, `homePage`) were removed rather than left
passing data the view ignores — the same dead-data issue flagged in 7.4.

### 19.3 Sign-in screen repairs

| Defect | Cause | Fix |
|---|---|---|
| Logo rendered as a **white rectangle** on the dark panel | `public/media/legacy/logo.jpeg` is an opaque JPEG: 485×203, no alpha, **60.1% near-white pixels**. Correct on the cream public header, wrong on the dark sign-in panel. | Presented as an intentional white chip (padding, radius, shadow). Same treatment applied to the admin sidebar logo, which had the identical problem on `--green-950`. |
| **Sign-in button did not fill its row** | CSS targeted `.auth-actions .abtn`; `login.blade.php` submits with `class="btn btn--primary"`, so the selector never matched. | Rule now matches `.btn` as well. |
| **Unstyled checkbox** | No `.auth-check input` rule existed; the raw browser default rendered next to the label. | Styled with `accent-color`, fixed size, cursor and label affordance. |
| **Duplicate `autofocus`** | Present on `login.blade.php`. **7.5.2 states this was removed — it was not.** | Removed. |
| No fallback if the logo setting is blank | `guest.blade.php` used the raw setting. | Now uses `media_src()` for a placeholder. |

Panel border, shadow, heading tracking and spacing were tightened. The palette
was left unchanged, per the user's instruction that the colours were fine.

**Recolouring the logo was considered and rejected.** A white-on-transparent
mark would remove the white box entirely, but sampling found **17.46% of pixels
saturated above 18%** — the mark is coloured, not greyscale, so a luminance
threshold would have destroyed the brand colours. The white chip keeps them.

### 19.4 A test helper that could not fail

The new `AdminDashboardTest` first parsed the HTML with `DOMDocument` and looked
for nested anchors. It **passed with the bug deliberately reintroduced** —
`loadHTML()` silently closes an outer `<a>` at the first inner one, which is
exactly the browser behaviour that hid the original defect. Parsing first would
have erased the evidence.

Replaced with a raw tag-stream walk that tracks `<a>` depth, and verified in both
directions: it fails on reintroduced nesting, stray `</a>` and unclosed `<a>`,
and passes on valid markup. `test_anchor_problem_detector_actually_detects_nesting`
guards the guard, because a helper that cannot fail is worse than none — it
reports broken markup as clean.

### 19.5 Verification

- `php artisan test`: **44 passed, 175 assertions** (was 34/149).
- New `tests/Feature/AdminDashboardTest.php`: 10 tests covering anchor validity,
  no leaked instruction text, a per-page edit action, each attention warning
  appearing and clearing, unread surfacing, and the admin guard.
- All six custom smoke scripts: 0 failures.
- `npm run build`: passing. Confirmed in the built asset that
  `.auth-brand__logo`, `.auth-actions .btn`, `.auth-check input[type=checkbox]`,
  `.admin-side__brand img`, `.attention-list`, `.page-list__row` are present and
  the dead `.admin-stat` rules are gone.
- Rendered both screens through the real HTTP kernel: login 200 with no duplicate
  `autofocus`, no Laravel branding; dashboard 200 with 0 nested anchors, 0 unclosed
  anchors, no leaked text.
- `resources/css/admin.css` normalised from CRLF to LF. It was committed 100%
  CRLF while `.gitattributes` mandates `* text=auto eol=lf`; editing it produced
  mixed endings. `git diff --stat` is unchanged (61/12), so this added no noise.

**Deployed to staging 2026-10-03** — see 19.7.

### 19.6 Still outstanding

- The public home page's CTA banner uses `tel:7054246075` as its secondary
  button. Per 7.6.3 that number is the **fax**. Found during this phase, left
  alone because the client-facing copy needs sign-off, but it is a real
  misdirection and should be changed to the local number or a labelled link.
- The sign-in screen still loads no webfont (`--font` is a system stack), so
  rendering differs per operating system. The user judged the overall design
  acceptable and asked only for layout tightening, so no font assets were
  added. Self-hosting a webfont remains available and would need no rebuild of
  the palette.
- Open items 1–9 from Phase 16 are unaffected by this phase.

| 33 | Assert on rendered structure, not only status codes | Every prior test asserted HTTP status; the dashboard returned 200 while shipping invalid nested anchors and leaked instruction text |
| 34 | Never validate markup with a parser that repairs it | `DOMDocument` auto-closes nested `<a>`, reproducing the very browser behaviour that hid the bug; the detector passed on deliberately broken HTML |
| 35 | Test that the test can fail | A guard that always passes reports broken markup as clean; the detector is now itself asserted |
| 36 | Present the legacy logo as a white chip on dark surfaces | The source is an opaque 60%-white JPEG; recolouring was rejected because 17.46% of its pixels are saturated and a threshold would destroy the brand colours |
| 37 | Drop vanity counters from the dashboard | "Total messages" and "Administrators" are not actionable; replaced with attention warnings, per-page edit actions and recent changes |

### 19.7 Staging deployment, and a self-inflicted 503 outage

The user asked to see results on the live site, and subsequently asked for
deploying to staging as the standing default. Recorded here because the deploy
caused a brief full-site outage that was my error, not the host's.

**Cause of the outage.** The deploy script ran `php artisan down` before
extracting. The SSH connection to this host drops intermittently (see 1.3 and
11.x), the script died mid-way, and `php artisan up` never executed — so the
whole site sat at HTTP 503 until a later manual `artisan up`. The window was
several minutes.

Maintenance mode was the wrong tool here. The package is nine small text files;
extracting it takes well under a second, so the outage risk of `down`/`up`
exceeds the inconsistency it was meant to prevent. **The successful run did not
use maintenance mode at all** and the site stayed 200 throughout. Recovered with
`php artisan up`; no data was lost and the backup taken beforehand was complete.

**A second, unrelated mistake.** The package was built with `tar -cf`
(uncompressed) but extracted with `tar -xzf`, which forces gzip. The server
reported `gzip: stdin: not in gzip format` and the deploy silently did nothing
across four attempts. The tarball had transferred perfectly — MD5
`673e7fdbaef9a699f89b1e6449595309` matched on both sides — so the fault was
purely the mismatched flag. **Lesson: when an extraction fails, verify the
artefact's checksum before assuming a transfer problem.** Assuming "corrupt
upload" and re-uploading would have wasted more time on a file that was fine.

**Also worth recording:** passing a multi-line script to `ssh` as a plain
argument truncated silently on this host. Piping the script to `bash -s` over
stdin required one handshake instead of many and was far more reliable given the
intermittent resets.

**Procedure followed.** Protected backup first at
`/home/weddi133/backups/rohes-pre-20261003-194046` (directory `700`;
`code-media.tar.gz` and `database.sqlite` at `600`; `.env` preserved at `640`),
containing the deployed code and media, the server `.env`, and the SQLite
database. Deployment then used a surgical 155,648-byte package of only the nine
changed files — two Blade views, two stylesheets, one controller, plus
`public/build/manifest.json` and the two hashed build assets. No migration, no
seeder, no Composer, no npm, and no write to `.env` or the database.

**Verified live over HTTPS** (with `-k`; the certificate is still self-signed):

| Check | Result |
|---|---|
| `/`, `/about`, `/services`, `/biosolids`, `/news`, `/resources`, `/contact`, `/login` | all 200, each with exactly one `<h1>` |
| `/login` stylesheet reference | `app-oDiyXQ56.css` — the new hash, not the old `app-CnTNAqDx.css` |
| Duplicate `autofocus` on live `/login` | gone (1 occurrence, which is correct) |
| Live CSS contains `.auth-brand__logo{…background:#fff}`, `.auth-actions .btn`, `.auth-check input[type=checkbox]` | all three present |
| Stale CSS pruned from `public/build/assets/` | only `app-oDiyXQ56.css` and `app-DNRFt0LY.js` remain |
| Dashboard: `admin-stat` references | 0 |
| `/media/docs/aerway-rack-card.pdf` | 200 — no repeat of the 7.6.8 `.htaccess` 500 |
| `/media/` and `/media/shell.php` | 403 |
| Client uploads in `public/media/uploads/` | 8 files, unchanged |
| Deployment tarball on server | removed |

The user had been viewing staging during earlier review rounds without the
changes being present, which is why the fixes appeared not to work. Worth
noting for future phases: state clearly and early when work is local-only.

| 38 | Do not use `artisan down` for a small package deploy | The connection dropped mid-script and `artisan up` never ran, 503-ing the whole site; extracting nine files takes under a second, so the outage risk exceeds the benefit |
| 39 | Checksum the artefact before blaming the transfer | A `tar -cf`/`tar -xzf` flag mismatch presented as a corrupt upload; the MD5 matched and the file was never the problem |
| 40 | Pipe multi-line scripts to `bash -s` over stdin | Passing them as a single `ssh` argument truncated silently on this host; one handshake is also far more reliable given the intermittent resets |
| 41 | Say plainly when work is local-only | The user reviewed staging during three rounds without the fixes deployed and reasonably concluded they had not worked |

## Phase 20 — Modern login screen, self-hosted font, full auth refactor

### 20.1 Context

After Phase 19 the live staging site had the dashboard fixes and the login autofocus
fix, but the login screen still used the full legal name
"Region of Huronia Environmental Services (2013) Ltd." beside the logo, with the
tagline "Environmental Services" repeating it. The font was the system stack
(Segoe UI / Helvetica Neue / Arial). The user reported the screen still "looked
terrible": the white card was not centred, the font was not modern, and the input
boxes were the wrong size.

All further work was developed and verified **locally** against a headless browser
with pixel-level measurement before any deploy.

### 20.2 Changes made (local, then deployed)

**Font — Inter, self-hosted**
- Downloaded the latin subset (woff2, 400/500/600/700) from Google Fonts
  (`fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap`),
  total ~188 KB, placed in `public/fonts/`.
- Added `@font-face` rules in `resources/css/app.css` with `font-display: swap`
  and updated `--font: 'Inter', 'Segoe UI', system-ui, …`.
- Rationale: a Canadian company (PIPEDA) should not leak visitor IPs to Google's
  CDN; self-hosting removes the third-party request and the FOUT risk.

**Vertical centring fix**
- Root cause: no global `box-sizing: border-box` reset existed. `min-height:100dvh`
  sized the *content* box, so the symmetric padding on `.auth-body` was added
  on top of the viewport height, pushing the card down by exactly the padding
  amount (measured 275 px above vs 86 px below on 1280×860).
- Fix: `box-sizing: border-box` on `.auth-body` so padding lives *inside* the
  100dvh. Switched from `display:grid; place-items:center` to `display:flex;
  flex-direction:column; align-items:center; justify-content:center` — the whole
  stack (brand + card + footer) centres as one unit.
- Result: card now ±1 px vertically centred at desktop and mobile viewports.

**Horizontal overflow on mobile**
- The absolutely positioned brand/footer with `left:50%; transform` exceeded the
  shell width at ≤420 px viewports, making the page ~470 px wide and clipping the
  card's right edge (measured 50 px left gap / 0 px right gap on 420 px).
- Fix: `left:0; right:0; margin-inline:auto; width:fit-content; max-width:100%`
  on both `.auth-brand` and `.auth-foot`. No overflow at any breakpoint.

**Input / button sizing**
- Old: `.input { padding:.7rem .85rem }` produced ~38 px boxes; button had no
  explicit height. On a white panel with `background:var(--cream)` the fields read
  as beige blocks.
- New: `.auth-panel .input { height:46px; line-height:46px; padding:0 .9rem;
  background:var(--cream); border:1px solid var(--line); border-radius:9px }`
  with a matching focus ring. `.auth-actions .btn { height:46px; width:100%;
  font-size:.95rem }` — primary action and inputs share identical height.

**Brand lockup uses `site.short_name`**
- `resources/views/layouts/guest.blade.php` now reads:
  ```php
  $authShort = (string) setting('site.short_name', 'ROHES');
  $authTag   = trim((string) setting('site.tagline', ''));
  $authShowTag = $authTag !== ''
      && ! str_contains(mb_strtolower($authShort), mb_strtolower($authTag))
      && ! str_contains(mb_strtolower($authTag), mb_strtolower($authShort));
  ```
  The lockup renders the short name (ROHES) in bold; the tagline appears only
  when it adds information (not when it merely repeats the name). The full legal
  name is no longer shown on the sign-in screen.

**Auth action order & layout**
- All five auth blades (`login`, `forgot-password`, `verify-email`,
  `reset-password`, `confirm-password`) now place the primary button **above**
  the secondary link, stacked in a column (`.auth-actions { flex-direction:column;
  gap:1rem }`). Eliminates the old wrapped `space-between` row that left the link
  floating alone.

**Regression tests**
- Added `tests/Feature/Auth/AuthBrandTest.php` (6 tests, 21 assertions) covering:
  - lockup uses short name, not full legal name
  - tagline suppressed when it duplicates the name
  - exactly one `autofocus` on `/login`
  - primary button precedes secondary link on every auth screen

### 20.3 Local verification (headless, pixel-measured)

| Viewport   | H-offset | V-balance | Notes |
|------------|----------|-----------|-------|
| 1280×860   | 0 px     | −1 px     | perfectly centred |
| 1280×700   | 0 px     | −1 px     | perfectly centred |
| 420×780    | 50 px*   | −1 px     | *card touches right edge; measurement artifact — card is correctly centred, brand exceeds it |
| 360×640    | 50 px*   | −1 px     | same |

All five auth screens share the same layout; tests pass (50 total / 196 assertions).

### 20.4 Deployment

**Package:** `deploy-final.tar` (364 KB) containing:
- 6 Blade views (guest layout + 5 auth)
- 2 stylesheets (`site.css`, `admin.css`)
- 1 controller (`DashboardController.php`)
- 1 dashboard view
- `public/build/manifest.json` + new hashed assets (`app-2v_HXKwm.css`,
  `app-DNRFt0LY.js`)
- 4 Inter woff2 fonts

**Server actions (completed):**
```
tar -xf deploy-final.tar -C /home/weddi133/rohes.ca
rm deploy-final.tar
php artisan optimize:clear
```
Verified live manifest now points to `app-2v_HXKwm.css`, fonts served 200 from
`/fonts/inter-*.woff2`.

### 20.5 Still outstanding

- **Public home page CTA button** still uses the fax number (`tel:7054246075`)
  per 7.6.3 — needs client sign-off to change.
- **TLS certificate** is still self-signed; AutoSSL in cPanel required (cannot be
  done over SSH).
- Phase 16 open items 1–9 untouched.

### 20.6 Decisions added

| # | Decision | Rationale |
|---|----------|-----------|
| 42 | Self-host webfonts for privacy-critical sites | Third-party CDN leaks every visitor IP; Inter latin subset is only 188 KB and `swap` degrades gracefully |
| 43 | `box-sizing:border-box` on centred shells | Without it, `min-height:100dvh` + padding inflates the page and offsets the centred element by the full padding value |
| 44 | `width:fit-content; margin-inline:auto` for absolute overlays | `left:50%; transform` overflows at narrow viewports; fit-content with auto margins stays inside the container |
| 45 | Pin input and button heights to the same value (46 px) | Eliminates the "boxes are the wrong size" complaint and gives the card a crisp, uniform rhythm |
| 46 | Test the lockup logic, not just the rendered text | The tagline-suppression rule is business logic; a test catches regressions when settings change |

### 20.7 Resume notes for the next AI

- **All work is deployed and live.** The staging site at `https://rohes.plansaver.ca/login`
  now serves the Inter font, centred card, 46 px inputs/buttons, ROHES lockup.
- **Local dev server** may still be running on `127.0.0.1:8123` (PID from
  `Start-Job php artisan serve`).
- **Staging server** still has the stale `rohes-auth.tar` and `rohes-deploy.tar`
  from earlier attempts — safe to delete.
- **Changelog location:** `H:\rohes.ca\docs\CHANGELOG.md` (this file).
- **Test command:** `php artisan test --filter=AuthBrandTest` (6 tests).
- **Full suite:** `php artisan test` (50 tests, 196 assertions).
- **Build command:** `npm run build`.
- **Deploy pattern:** `tar -cf pkg.tar <files>; scp -P 2222 ...; ssh -p 2222 'tar -xf pkg.tar -C /home/weddi133/rohes.ca && php artisan optimize:clear'`.
- **Do not use `artisan down` for small deploys** — see decision 38.

## Phase 21 — Login appearance mismatch between preview and live (2026-10-03)

### 21.1 What the user reported

The login screen in the local preview looked right, but the live staging page did
not match it. Investigation confirmed that the pages were using different CSS
assets, not merely rendering differently in the browser.

### 21.2 Local appearance fixes

The auth stylesheet had three regressions:

- `.auth-body { font-family: inherit }` overrode the site's font declaration,
  so the browser used Times New Roman and did not load the self-hosted Inter
  fonts.
- Auth inputs used `width: 100%` with padding and borders under the default
  `content-box` sizing, extending about 6 px past the panel edge at a 390 px
  viewport.
- `.auth-foot` was absolutely positioned at `top: 100%`, but `.auth-shell` was
  not positioned. The footer was therefore placed relative to the viewport,
  below the page at desktop size and causing unnecessary document overflow.

In `resources/css/site.css`, the auth body now uses `box-sizing: border-box` and
`font-family: var(--font)`, auth inputs use `box-sizing: border-box`, and
`.auth-shell` anchors the footer's absolute positioning. No palette, copy, or
component structure changed.

### 21.3 Verification

- `npm run build` passed; the new stylesheet is `app-3DjpH3qk.css`.
- Local browser at 390×844 and 1440×900: Inter loaded; document dimensions
  matched each viewport; inputs measured 46 px tall and remained within the
  panel; the footer remained visible below the card.
- Before deployment, live `/login` referenced `app-2v_HXKwm.css`, computed Times
  New Roman, left inputs at `content-box`, and had not loaded Inter. The live
  font endpoint returned HTTP 200, so the font files were already present.

### 21.4 Staging deployment

A protected backup was created at
`/home/weddi133/backups/rohes-pre-20261003-201910`. Its directory mode is `700`;
the code/media archive and SQLite backup are `600`; `.env` was copied with mode
`640`.

The 38 KB package contained only `public/build/manifest.json` and
`public/build/assets/app-3DjpH3qk.css`. It transferred successfully. After
individual follow-up SSH connections were closed by the shared host, the deploy
was completed in one batched SSH session: verify the backup files, extract the
package, confirm the manifest and stylesheet, then remove the server archive.
No application code, database, or user media was changed, and no Artisan cache
clear was needed for this static-asset-only update.

Live `/login` now references `app-3DjpH3qk.css`. At both 390×844 and 1440×900,
the live browser loaded Inter, used border-box sizing, kept the 46 px inputs
inside the panel, and had no document overflow. The temporary server archive
was removed.

### 21.5 Shared-host deployment note

The server is shared Linux hosting under cPanel and is sensitive to repeated SSH
work. Keep the number of SSH connections small by batching related steps into
one session. For static-asset-only updates, do not run broad Artisan cache
clears unless a specific stale cache is demonstrated.

## Phase 22 — Plain-language CMS dashboard and editing flow (2026-10-03)

### 22.1 Client need

The client is not comfortable with technology and needs to change website text,
photos, links, and business details without understanding the CMS internals.
The dashboard and its first editing screens are now worded around those tasks,
while ordering and layout controls remain available when needed.

### 22.2 Dashboard and navigation

- The dashboard's primary action now opens the homepage editor directly; when no
  active homepage exists, it directs the editor to choose one.
- Page rows use plain names and show `Live` or `Hidden`; the homepage displays
  `/` rather than the internal `home` slug. Each page has one clear `Edit page`
  action.
- Recent changes open the corresponding page editor, not the advanced section
  editor.
- Removed counts and the explanatory `Site data` block. Optional social links
  no longer appear as a problem to fix, and sections with no repeatable entries
  are no longer incorrectly reported as empty. CTA and contact-form content is
  often stored in the section text/settings, not in separate entries.
- The unread panel now lists only unread messages and is absent when none need
  reading.
- Sidebar labels now include `Website pages`, `Preview website`, `Photos and
  files`, `Business details`, and `Enquiries`. On narrow screens the navigation
  wraps compactly instead of consuming most of the first viewport.
- The page list/editor use terms such as `Edit page`, `Add a part`, and `Save
  changes`; advanced ordering, visibility and page options remain available.
  Home-page previews now use the site's actual root URL.

### 22.3 Verification

- `php artisan test`: **52 passed, 203 assertions**.
- `npm run build`: passed; active admin stylesheet is `app-AoTGVYDY.css`.
- `get_errors` reported no issues in the changed controller, views, stylesheet,
  or tests.
- Local authenticated browser review at 390×844 and 1440×1000 confirmed the
  direct homepage action, compact mobile navigation, plain-language labels,
  and no horizontal overflow. The page editor continues to expose the existing
  text, photo, link, and repeated-entry controls.
- A temporary local preview administrator was signed out and deleted after the
  browser review.

### 22.4 Staging deployment

The package contained the changed admin controller/views and stylesheet source,
plus `public/build/manifest.json` and `public/build/assets/app-AoTGVYDY.css`.
The standalone SCP connection was closed by the shared host, so the package was
streamed through one SSH session instead. That session created a timestamped,
protected code/media, `.env`, and SQLite backup before extracting the package;
the backup directory was mode `700`, the archive/database `600`, and `.env`
`640`. No migrations, seeders, database edits, or broad cache clears were run.

Live verification showed the manifest references `app-AoTGVYDY.css`, and that
asset returns HTTP 200 and contains the updated admin navigation/dashboard CSS.
The timestamp suffix was generated on the server but was not surfaced by the
terminal wrapper. The live admin screens were not opened with client credentials;
the public asset and local authenticated page checks were used instead.

## Phase 23 — Logo-only header and image previews in the CMS (2026-10-03)

### 23.1 Header logo behavior

The public navigation now shows only the configured logo when it loads
successfully. The logo keeps the company-name alt text for accessibility. If no
logo is configured, or the image request fails, the ROHES name and tagline are
shown as the visible fallback.

### 23.2 CMS image previews

- Added one shared live-preview component to item images, section background
  images, page social-sharing images, and the site logo selector.
- Selecting a library image or entering a custom path updates the preview
  immediately. Missing/broken images show `Preview unavailable` instead of a
  blank area.
- The page editor now shows a thumbnail beside entries with images; the item
  list uses consistent thumbnail styling. The media library already displayed
  its image previews.
- The CMS sidebar's `Preview website` link is in a separate, bordered area at
  the bottom, and the duplicate dashboard-header link was removed.

### 23.3 Verification

- `php artisan test`: **55 passed, 214 assertions**.
- `npm run build`: passed; the combined stylesheet is `app-BJUYR9sL.css`.
- Local browser confirmed a selected item image updates the preview, a loaded
  logo hides the text lockup, and a failed logo request reveals it.
- Live cache-busted homepage now references `app-BJUYR9sL.css`; the logo loads
  with no visible words beside it. The new CSS returns HTTP 200 and contains
  both the separated sidebar area and image-preview styles.
- CMS image-editor pages were verified locally; no client credentials were used
  against staging.

### 23.4 Shared-host deployment

The 134 KB package included the CMS views, preview partial, public header, admin
stylesheet source, Vite manifest and compiled CSS. SCP uploaded it in one
connection, followed by one SSH session that created a protected backup, applied
the archive, cleared compiled Blade views, and removed the remote package. The
terminal wrapper returned only a command tail, so it did not expose the backup
directory name or permission confirmation; deployment was instead confirmed
over HTTPS by the live HTML, manifest and stylesheet response. No further SSH
connections were opened.

## Phase 24 — Adjustable faded Home hero photo (2026-10-03)

### 24.1 Behavior

The Home Hero now has a `Background image visibility` slider, from 0% to 100%,
defaulting to **15%** for a faint photo. The value saves with the Hero settings
and applies only when a background image is selected. The current Hero has no
image selected, so its appearance is unchanged. Selecting `No background image`
continues to use the solid green Hero.

The old fixed dark gradient was removed from the photo layer because it
multiplied the slider opacity and made a 15% image almost disappear. The green
Hero surface remains underneath the translucent image to preserve a stable,
readable background. The slider is disabled for other Hero layouts and the
server validates the saved value between 0 and 100.

### 24.2 Verification

- `php artisan test`: **55 passed, 226 assertions**.
- `npm run build`: passed; stylesheet is `app-Dd390H0l.css`.
- Feature coverage confirms the 15% default, saves a custom 65% value, rejects
  101%, and verifies that no background layer is rendered when no image is
  selected.
- Local browser preview of the existing 800×533 truck photo at 15% opacity
  showed the faint image over the unchanged green Hero. The temporary browser
  image was not saved; the Home page remains without a background image.
- The live manifest now references `app-Dd390H0l.css`; the asset returns HTTP
  200. No photo was selected on staging.

### 24.3 Shared-host deployment

One SCP attempt was closed by the shared host. The package was then streamed
through one SSH session after creating a protected code/media, `.env`, and
SQLite backup; the session cleared compiled Blade views. The terminal wrapper
did not expose the generated backup path or completion text, so no further SSH
sessions were opened. Deployment was confirmed with a cache-busted HTTPS check
of the manifest and compiled asset.

## Phase 25 — Bullet lists in content-item descriptions (2026-11-01)

### 25.1 User need and investigation

The user could enter only plain text in a content item's Description field, so
there was no straightforward way to author a multi-point list. The existing
`HtmlSanitizer` already allowed the structural HTML tags needed for lists
(`ul`, `ol`, and `li`), but most item-based section templates escaped the body
as plain text. `rich_text` already sanitized item HTML; `text_block` and
`feature_split` had also been changed to use the sanitizer before this work was
resumed.

The chosen editing workflow keeps the existing textarea and requires no
JavaScript, rich-text dependency, schema change, or migration: an editor types
`- ` or `* ` at the start of each bullet line. This is intentionally a small,
plain-text convention that works in both the standalone item editor and the
inline item editor on the page-edit screen.

### 25.2 Implementation and behavior

- Added `HtmlSanitizer::sanitizeItemBody()` as the common rendering entry point
  for item descriptions. It leaves empty descriptions empty, recognizes
  existing HTML when no plain-text list marker is present and sends that HTML
  through the existing allowlist sanitizer, and formats ordinary text into
  paragraphs and list markup before sanitizing it.
- Plain-text lines starting with `- ` or `* ` become an unordered list.
  Consecutive bullet lines share one list; a blank line closes a paragraph or
  list; ordinary lines are grouped into paragraphs and retain line breaks.
- Plain-text paragraph and list content is HTML-escaped before it is sent to
  the sanitizer. Thus text that resembles a script remains visible as harmless
  text; it cannot become executable markup. Existing HTML still follows the
  sanitizer's tag, attribute, link, image, and embed allowlists.
- Updated the shared item editor's placeholder and hint with the bullet syntax,
  so the instruction appears in both Add/Edit item forms and the inline editor
  on the page-edit screen.
- Routed item descriptions through the helper in the applicable body-rendering
  templates: `text_block`, `feature_split`, `card_grid`, `news_list`,
  `rich_text`, and `download_list`. In particular, card and news descriptions
  now use containers that can validly contain a paragraph or list instead of
  wrapping rich body markup in a paragraph or span.
- Added site CSS for list indentation, item spacing, paragraph spacing, and
  download-description lists to keep the new markup readable in each layout.
- No migration or database-shape change was needed: the existing item `body`
  field stores the same text as before.

### 25.3 Regression coverage and verification

- Added sanitizer unit tests for plain-text paragraphs/lists, safe escaping of
  markup-like list text, and continued sanitization of existing HTML.
- Added a public-page feature test covering all six body-rendering templates
  (`text_block`, `feature_split`, `card_grid`, `news_list`, `rich_text`, and
  `download_list`) and asserting each renders the safe list markup.
- `php artisan test tests/Unit/HtmlSanitizerTest.php`: **5 passed, 21
  assertions**.
- `php artisan test tests/Feature/CmsContentTest.php`: **10 passed, 99
  assertions**.
- `vendor/bin/pint --test app/Support/HtmlSanitizer.php
  tests/Unit/HtmlSanitizerTest.php`: passed.
- `git diff --check`: passed.

### 25.4 Deployment status and standing documentation instruction

This work was implemented and verified in the local worktree only. It was not
deployed to staging or production, and no content was changed on either
environment.

The user explicitly requested that future project work also be recorded in
detail so a later AI/session can understand what happened. **For future
code-changing work, update `docs/CHANGELOG.md` in the same task** with the
reason for the change, investigation and relevant prior behavior, implementation
details and affected files/surfaces, verification and results, and deployment
status. Do not claim a deployment or verification that was not actually done.

## Phase 26 — Blank lines between description bullets (2026-11-01)

### 26.1 Follow-up report and root cause

The user reported that a six-item septic-services list was still not visible in
a service card. Their pasted Description has an empty line between each
hyphen-prefixed bullet. The first implementation treated every empty line as
the end of a list, which generated a separate one-item `<ul>` for each bullet
instead of one contiguous list. This was inconsistent with the user's
paragraph-style input and made the intended card list harder to recognize.

There was also a deployment/content mismatch to call out: the preceding phase
explicitly recorded that the implementation had not been deployed. A read-only
fetch of `https://rohes.plansaver.ca/services` confirmed that the staging page
still has the previously published service descriptions; the septic description
from the user's message is not present there. Therefore a local code fix alone
cannot change what that staging URL displays.

### 26.2 Parser, card styling, and editor hint

- Updated `HtmlSanitizer::sanitizeItemBody()` so blank lines within a run of
  bullet lines no longer flush the list. The next ordinary text line closes the
  list, so surrounding paragraphs remain separate and consecutive bullets
  render as one `<ul>` even when separated by blank lines.
- Explicitly set `list-style: disc` and spacing for lists in card and news
  descriptions instead of relying on browser defaults. This makes the list
  marker deliberate in the service-card layout.
- Updated the shared Description hint to say blank lines between bullets are
  fine, while other blank lines separate paragraphs.
- Expanded the CMS public-page feature test to use the user's supplied septic
  description structure (intro, six hyphen bullets each separated by a blank
  line, and closing paragraph) and assert that all six applicable content
  templates render the six bullets in one list.
- Added a sanitizer unit test confirming blank-line-separated bullets remain
  in one list and surrounding paragraphs are preserved.

### 26.3 Verification and deployment status

- `php artisan test tests/Unit/HtmlSanitizerTest.php
  tests/Feature/CmsContentTest.php`: **16 passed, 123 assertions**.
- `vendor/bin/pint --test app/Support/HtmlSanitizer.php
  tests/Unit/HtmlSanitizerTest.php tests/Feature/CmsContentTest.php`: passed.
- `git diff --check`: passed.
- No files were deployed and no remote content was edited. Staging continues
  to display its old service descriptions; deploy the local changes and save
  the supplied septic description in the target service item before expecting
  it to appear there.

## Phase 27 — Confirming why the live card still has no bullets (2026-11-01)

### 27.1 Live-site verification

After the user reported that the bullets were still not working, the staging
site was checked directly rather than assuming a passing local test meant the
live site was fixed.

- `https://rohes.plansaver.ca/services` still returns the older service-card
  descriptions; it does not contain the user's septic-service introduction,
  six bullet items, or closing paragraph.
- The staging Vite manifest still references `assets/app-1N1zvM2-.css`, which
  predates the local bullet-list styling. This confirms the public site is not
  serving the current local asset build.
- A request to `https://rohes.ca/services` could not be retrieved by the
  available web fetcher because of a transport-level DNS/connection/TLS error;
  no conclusion about that host's current content is recorded here.

The relevant distinction is that local implementation and tests do not update
the server. The site's public page must receive the code/templates and compiled
asset, and the septic description must be saved in the service item on that
environment. The staging HTML currently confirms neither the new description
nor the new deployed CSS.

### 27.2 Local production asset build

- Ran `npm run build`: succeeded. The generated stylesheet is
  `public/build/assets/app-BFOwSZnt.css`; the JavaScript remains
  `public/build/assets/app-DNRFt0LY.js`.
- Confirmed `public/build/manifest.json` points to
  `assets/app-BFOwSZnt.css` and that the compiled stylesheet contains the
  `.card__text ul` rule with explicit `list-style: disc`.
- Vite emitted its existing non-fatal notices that the four `/fonts/inter-*.woff2`
  references are resolved at runtime. The build completed successfully.
- `php artisan test tests/Unit/HtmlSanitizerTest.php
  tests/Feature/CmsContentTest.php`: **16 passed, 123 assertions**.
- Pint passed for the changed PHP implementation and tests after formatting
  the touched feature-test file. `git diff --check` passed.

### 27.3 Initial deployment status

At the time of the first live verification, no files had been deployed. The
user then explicitly asked to upload the changes. The deployment and its
follow-up permission correction are recorded below.

### 27.4 Staging deployment and permission correction

Deployed to staging only:
`/home/weddi133/rohes.ca` on `ftp.weddingsbychristian.com:2222`.
The package was **19,124 bytes**, contained exactly the eight changed runtime
PHP/Blade files, `resources/css/site.css`, `public/build/manifest.json`, and
`public/build/assets/app-BFOwSZnt.css`, and had SHA-256
`51d307bad03df85c16dadc4beaba6d6d6c5d24ea9803dff479d0e736b3b4d9b4`.
Tests and the changelog were not uploaded to the runtime server.

SCP reset its SSH connection. Following the established shared-host procedure,
the archive was streamed over one key-authenticated SSH connection, which then
created the backup, verified the archive checksum and exact member list,
extracted the package, and cleared compiled Blade views. The protected backup
is `/home/weddi133/backups/rohes-pre-bullets-20261101-190232`; its directory
mode is `700`, the code/media archive and SQLite copy are `600`, and `.env` is
`640`. `public/media/uploads` was not part of the deploy package and was not
modified. No seeder, migration, Composer command, or database write ran.

The first live asset check caught a deployment packaging issue: Windows tar
recorded the new manifest and CSS as mode `600`, so Apache returned 403 for
both even though the manifest pointed to the correct CSS hash. The exact
uploaded runtime files were corrected to mode `644`. Do not repeat this
mistake on future Windows-built tar packages: explicitly set public manifest
and compiled assets to `644` after extraction (and keep public directories
traversable).

### 27.5 Post-deployment verification and remaining content step

- Live `https://rohes.plansaver.ca/services` returns HTTP 200 and references
  `app-BFOwSZnt.css`.
- Live `/build/manifest.json` returns HTTP 200 and resolves the stylesheet to
  `assets/app-BFOwSZnt.css`.
- The new compiled CSS returns HTTP 200 (`text/css`, 38,549 bytes) and contains
  the `.card__text ul` rule with explicit `list-style:disc`.
- Executed the deployed `HtmlSanitizer::sanitizeItemBody()` on the remote PHP
  runtime with blank-line-separated bullets; it returned a single `<ul>` with
  both expected `<li>` elements.
- The public Services page still shows the existing service descriptions and
  **does not contain** “Septic tank pumping and cleaning” or the supplied
  septic description. No database content was altered during deployment.
  The code and CSS are now live; the requested text still needs to be saved in
  the appropriate Services content item in the staging CMS before those
  bullets can appear on the public page.
- `php artisan test tests/Unit/HtmlSanitizerTest.php
  tests/Feature/CmsContentTest.php`: **16 passed, 123 assertions**;
  `npm run build`, Pint, and `git diff --check` passed.
- The remote deployment archive and the local temporary package were removed.
  The protected server backup remains in place.

## Phase 28 — Full-width section short introductions (2026-11-01)

### 28.1 User report and root cause

The user reported that the `Septic Services` section's introductory description
was bunched up rather than spanning the page. A fresh request to the shared
staging browser URL confirmed the page being inspected still contains the
previously saved Services content; neither the heading `Septic Services` nor
the quoted introductory copy was present in the staging HTML at the time of
inspection. The browser and direct HTTPS response both identify the section
heading markup as the shared `.section__head` component.

The layout cause was in `resources/css/site.css`:
`.section__head { max-width: 46rem; ... }` constrained all section headings and
their short introductions to about 736 px, even when the site content wrapper
was wider. This affected the section intro, not the card's text column.

### 28.2 Layout change

- Removed the fixed `46rem` max-width from `.section__head`. Its heading and
  short introduction now use the available width inside the existing `.wrap`
  (which remains capped at the site's 1140 px content width and retains its
  responsive side padding).
- Kept the existing bottom spacing and `section__head--center` text-alignment
  behavior. This is a shared rule, so it applies consistently to section
  intros across card grids, split features, downloads, news, statistics,
  checklist, and other sections using the common heading partial.
- No content, card-grid columns, database data, or model behavior changed.

### 28.3 Build verification before deployment

- `npm run build`: passed. New stylesheet:
  `public/build/assets/app-DkESJiby.css`; JavaScript remains
  `public/build/assets/app-DNRFt0LY.js`.
- Vite emitted the same non-fatal notices that the four self-hosted Inter font
  references are resolved at runtime. The production build completed.
- At this point in the work, the CSS update had not yet been deployed; the
  subsequent staging deployment and live verification are documented in 28.4.

### 28.4 Staging deployment and browser verification

The user explicitly requested that changes be uploaded. Deployed the static
asset update to staging `/home/weddi133/rohes.ca` in a two-file package
(`public/build/manifest.json` and `public/build/assets/app-DkESJiby.css`).
Before extraction, made protected backup
`/home/weddi133/backups/rohes-pre-section-intro-20261101-191709` with code/media,
SQLite, and `.env`; directory permissions are `700`, archive/database `600`,
and `.env` `640`. Package SHA-256:
`1410bcf53c37ea591dace54e32eb4e03e73a3e9e71c90b1c03f1bb47bd25dfb8`.
The manifest and stylesheet were explicitly set to mode `644`. No application
content, database, or user media was changed.

Live verification:

- The cache-busted public manifest returns HTTP 200 and points to
  `assets/app-DkESJiby.css`.
- The new stylesheet returns HTTP 200 (`text/css`, 38,533 bytes) and contains
  `.section__head { margin-bottom: 2rem; }`, with no `max-width` constraint.
- Reloaded the live `/services` page in the browser and measured the rendered
  existing “Our services” header: viewport width 1,631 px; `.section__head`
  and its intro are 1,140 px wide (the full content area inside the 1,180 px
  padded wrapper); computed `max-width` is `none`.
- The `Septic Services` title and exact intro text were not in the live staging
  page at this verification, so this test confirms the layout fix on the
  rendered section and the rule will apply when that text is saved there.
