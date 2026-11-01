@extends('layouts.guest')

@section('title', 'Reset your password')

@section('content')
<h1>Forgotten password</h1>
<p class="auth-lede">
    Enter the email address for your account and we will send you a link to choose a new password.
</p>

@if (session('status'))
    <div class="flash flash--success" role="status">{{ session('status') }}</div>
@endif

<form method="POST" action="{{ route('password.email') }}">
    @csrf

    <div class="field @error('email') field--error @enderror">
        <label for="email">Email address</label>
        <input class="input" type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
        @error('email')<p class="error-text">{{ $message }}</p>@enderror
    </div>

    <div class="auth-actions">
        <button type="submit" class="btn btn--primary">Send reset link</button>
        <span class="auth-link"><a href="{{ route('login') }}">Back to sign in</a></span>
    </div>
</form>

@if (config('mail.default') === 'log')
    <div class="callout callout--warn" style="margin-top:1.5rem">
        <b>Email is not configured on this server yet.</b>
        The reset link is written to the application log instead of being sent.
        Ask an administrator to set a new password for you.
    </div>
@endif
@endsection
