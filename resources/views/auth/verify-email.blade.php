@extends('layouts.guest')

@section('title', 'Confirm your email address')

@section('content')
<h1>Confirm your email address</h1>
<p class="auth-lede">
    Thanks for signing up. Please confirm your address by clicking the link we just emailed you.
    If it did not arrive, we can send it again.
</p>

@if (session('status') === 'verification-link-sent')
    <div class="flash flash--success" role="status">
        A new confirmation link has been sent to your email address.
    </div>
@endif

<form method="POST" action="{{ route('verification.send') }}">
    @csrf
    <div class="auth-actions">
        <span class="auth-link"><a href="{{ route('admin.dashboard') }}">Skip for now</a></span>
        <button type="submit" class="btn btn--primary">Resend confirmation email</button>
    </div>
</form>
@endsection
