@extends('admin.layout')

@section('title', 'Add item')

@section('content')
<div class="admin-head">
    <div>
        <h1>Add a content item</h1>
        <p>{{ $section->title }} &middot; on <a href="{{ route('admin.pages.sections.index', $page) }}">{{ $page->title }}</a></p>
    </div>
    <div class="admin-head__actions">
        <a href="{{ route('admin.sections.items.index', $section) }}" class="abtn abtn--ghost">Cancel</a>
    </div>
</div>

<form method="POST" action="{{ route('admin.sections.items.store', $section) }}" class="admin-card">
    @csrf
    <div class="admin-card__body">
        @include('admin.items._form', ['item' => $item])
    </div>
    <div class="form-actions">
        <a href="{{ route('admin.sections.items.index', $section) }}" class="abtn abtn--ghost">Cancel</a>
        <button type="submit" class="abtn abtn--primary">Add item</button>
    </div>
</form>
@endsection
