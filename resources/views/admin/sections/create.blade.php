@extends('admin.layout')

@section('title', 'Add section')

@section('content')
<div class="admin-head">
    <div>
        <h1>Add a section</h1>
        <p>Page: <a href="{{ route('admin.pages.sections.index', $page) }}">{{ $page->title }}</a></p>
    </div>
    <div class="admin-head__actions">
        <a href="{{ route('admin.pages.sections.index', $page) }}" class="abtn abtn--ghost">Cancel</a>
    </div>
</div>

<form method="POST" action="{{ route('admin.pages.sections.store', $page) }}" class="admin-card">
    @csrf
    <div class="admin-card__body">
        @include('admin.sections._form', ['section' => $section])
    </div>
    <div class="form-actions">
        <a href="{{ route('admin.pages.sections.index', $page) }}" class="abtn abtn--ghost">Cancel</a>
        <button type="submit" class="abtn abtn--primary">Create section</button>
    </div>
</form>
@endsection
