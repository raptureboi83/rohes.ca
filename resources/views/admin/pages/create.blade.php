@extends('admin.layout')

@section('title', 'New page')

@section('content')
<div class="admin-head">
    <div>
        <h1>New page</h1>
        <p>Give the page a title and an address. You will add sections to it on the next screen.</p>
    </div>
    <div class="admin-head__actions">
        <a href="{{ route('admin.pages.index') }}" class="abtn abtn--ghost">Back to pages</a>
    </div>
</div>

<form method="POST" action="{{ route('admin.pages.store') }}" class="admin-card">
    @csrf
    <div class="admin-card__body">
        @include('admin.pages._form', ['page' => $page])
    </div>
    <div class="form-actions">
        <a href="{{ route('admin.pages.index') }}" class="abtn abtn--ghost">Cancel</a>
        <button type="submit" class="abtn abtn--primary">Create page</button>
    </div>
</form>
@endsection
