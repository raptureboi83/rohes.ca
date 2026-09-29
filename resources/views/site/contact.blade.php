{{--
    Standalone contact page. Reuses the `contact_form` section template
    verbatim, so a client can either dedicate a page to the form or drop a
    contact_form section onto any other page — one implementation, and it
    improves in both places at once.
--}}
@extends('site.layout')

@section('content')
    @include('site.sections.contact_form', ['section' => $section])
@endsection
