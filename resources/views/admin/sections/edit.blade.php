@extends('admin.layout')

@section('title', 'Edit ' . $section->title)

@section('content')
<div class="admin-head">
    <div>
        <h1>Edit section</h1>
        <p>{{ $section->title }} &middot; on <a href="{{ route('admin.pages.sections.index', $page) }}">{{ $page->title }}</a></p>
    </div>
    <div class="admin-head__actions">
        <a href="{{ route('admin.sections.items.index', $section) }}" class="abtn abtn--primary">Content items</a>
    </div>
</div>

<form method="POST" action="{{ route('admin.sections.update', $section) }}" class="admin-card">
    @csrf
    @method('PUT')
    <div class="admin-card__body">
        @include('admin.sections._form', ['section' => $section])
    </div>
    <div class="form-actions">
        <a href="{{ route('admin.pages.sections.index', $page) }}" class="abtn abtn--ghost">Cancel</a>
        <button type="submit" class="abtn abtn--primary">Save changes</button>
    </div>
</form>
@endsection
