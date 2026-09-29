@extends('layouts.guest')

@section('title', 'Choose a new password')

@section('content')
<h1>Choose a new password</h1>
<p class="auth-lede">Pick something you have not used on this site before.</p>

<form method="POST" action="{{ route('password.store') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $request->route('token') }}">

    <div class="field @error('email') field--error @enderror">
        <label for="email">Email address</label>
        <input class="input" type="email" id="email" name="email" value="{{ old('email', $request->email) }}" required autofocus>
        @error('email')<p class="error-text">{{ $message }}</p>@enderror
    </div>

    <div class="field @error('password') field--error @enderror">
        <label for="password">New password</label>
        <input class="input" type="password" id="password" name="password" required autocomplete="new-password">
        <p class="hint">At least 8 characters.</p>
        @error('password')<p class="error-text">{{ $message }}</p>@enderror
    </div>

    <div class="field">
        <label for="password_confirmation">Repeat new password</label>
        <input class="input" type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
    </div>

    <div class="auth-actions">
        <span class="auth-link"><a href="{{ route('login') }}">Back to sign in</a></span>
        <button type="submit" class="btn btn--primary">Save new password</button>
    </div>
</form>
@endsection
