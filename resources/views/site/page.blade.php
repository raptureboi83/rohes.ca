{{--
    The generic page renderer.

    This is the piece matsart.ca does not have. It loops over the page's
    visible sections and includes one Blade partial per `template_key`.
    Nothing about the page structure is hardcoded, so the client can add a
    page, add a section, or switch a section's layout in the admin and have
    it appear — with no code change and no deploy.

    SiteController already filters out any section whose template_key has no
    partial, so a bad key can never produce a 500.
--}}
@extends('site.layout')

@section('content')
    @php
        // A page must have exactly one h1. Hero sections and standalone
        // contact forms can supply their own page heading.
        $hasPageHeading = $sections->contains(fn ($section) =>
            $section->template_key === 'hero'
            || ($section->template_key === 'contact_form' && $section->setting('heading_level') === 'h1')
        );
    @endphp

    @unless ($hasPageHeading)
        <section class="section section--tight">
            <div class="wrap">
                <span class="eyebrow">{{ setting('site.short_name', 'ROHES') }}</span>
                <h1>{{ $page->title }}</h1>
                @if ($page->meta_description)
                    <p class="lede">{{ $page->meta_description }}</p>
                @endif
            </div>
        </section>
    @endunless

    @forelse ($sections as $section)
        @include('site.sections.'.$section->template_key, ['section' => $section])
    @empty
        <section class="section">
            <div class="wrap wrap--narrow">
                <p class="lede">This page has no content yet. Sign in to the admin to add sections.</p>
            </div>
        </section>
    @endforelse
@endsection
