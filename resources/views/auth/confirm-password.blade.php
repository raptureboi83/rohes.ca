@extends('layouts.guest')

@section('title', 'Confirm your password')

@section('content')
<h1>Confirm your password</h1>
<p class="auth-lede">This is a protected area. Please confirm your password before continuing.</p>

<form method="POST" action="{{ route('password.confirm') }}">
    @csrf

    <div class="field @error('password') field--error @enderror">
        <label for="password">Password</label>
        <input class="input" type="password" id="password" name="password" required autofocus autocomplete="current-password">
        @error('password')<p class="error-text">{{ $message }}</p>@enderror
    </div>

    <div class="auth-actions">
        <span class="auth-link"><a href="{{ route('admin.dashboard') }}">Cancel</a></span>
        <button type="submit" class="btn btn--primary">Confirm</button>
    </div>
</form>
@endsection
