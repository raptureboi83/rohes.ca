@extends('layouts.guest')

@section('title', 'Sign in')

@section('content')
<h1>Sign in</h1>
<p class="auth-lede">Use the email address and password for your administrator account.</p>

<form method="POST" action="{{ route('login') }}">
    @csrf

    <div class="field @error('email') field--error @enderror">
        <label for="email">Email address</label>
        <input class="input" type="email" id="email" name="email" value="{{ old('email') }}"
               required autofocus autocomplete="username" autofocus>
        @error('email')<p class="error-text">{{ $message }}</p>@enderror
    </div>

    <div class="field @error('password') field--error @enderror">
        <label for="password">Password</label>
        <input class="input" type="password" id="password" name="password"
               required autocomplete="current-password">
        @error('password')<p class="error-text">{{ $message }}</p>@enderror
    </div>

    <div class="auth-check">
        <input type="checkbox" id="remember_me" name="remember" value="1" @checked(old('remember'))>
        <label for="remember_me">Keep me signed in on this computer</label>
    </div>

    <div class="auth-actions">
        @if (Route::has('password.request'))
            <span class="auth-link"><a href="{{ route('password.request') }}">Forgotten your password?</a></span>
        @endif
        <button type="submit" class="btn btn--primary">Sign in</button>
    </div>
</form>
@endsection
