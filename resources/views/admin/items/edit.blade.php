@extends('admin.layout')

@section('title', 'Edit item')

@section('content')
<div class="admin-head">
    <div>
        <h1>Edit content item</h1>
        <p>{{ $item->title ?: 'Untitled' }} &middot; in <a href="{{ route('admin.sections.items.index', $section) }}">{{ $section->title }}</a></p>
    </div>
    <div class="admin-head__actions">
        <a href="{{ route('admin.sections.items.index', $section) }}" class="abtn abtn--ghost">Back to items</a>
    </div>
</div>

<form method="POST" action="{{ route('admin.sections.items.update', [$section, $item]) }}" class="admin-card">
    @csrf
    @method('PUT')
    <div class="admin-card__body">
        @include('admin.items._form', ['item' => $item])
    </div>
    <div class="form-actions">
        <a href="{{ route('admin.sections.items.index', $section) }}" class="abtn abtn--ghost">Cancel</a>
        <button type="submit" class="abtn abtn--primary">Save changes</button>
    </div>
</form>
@endsection
