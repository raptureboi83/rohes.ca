@extends('admin.layout')

@section('title', 'Your account')

@section('content')
<div class="admin-head">
    <div>
        <h1>Your account</h1>
        <p>Signed in as {{ auth()->user()->name }} &middot; {{ auth()->user()->email }}</p>
    </div>
    <div class="admin-head__actions">
        <a href="{{ route('admin.dashboard') }}" class="abtn abtn--ghost">Back to dashboard</a>
    </div>
</div>

<div class="admin-card" style="margin-bottom:1.25rem">
    <div class="admin-card__body">
        @include('profile.partials.update-profile-information-form')
    </div>
</div>

<div class="admin-card" style="margin-bottom:1.25rem">
    <div class="admin-card__body">
        @include('profile.partials.update-password-form')
    </div>
</div>

<div class="admin-card">
    <div class="admin-card__body">
        @include('profile.partials.delete-user-form')
    </div>
</div>
@endsection
