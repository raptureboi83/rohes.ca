<h2 style="font-size:1.05rem;margin:0 0 .3rem">Your details</h2>
<p class="hint" style="margin:0 0 1.25rem">The name shown in the admin sidebar and on anything you edit.</p>

<form method="POST" action="{{ route('profile.update') }}">
    @csrf
    @method('patch')

    <div class="field">
        <label for="name">Full name</label>
        <input class="ainput" type="text" id="name" name="name" value="{{ old('name', $user->name) }}"
               required autocomplete="name">
        @error('name')<p class="aerror">{{ $message }}</p>@enderror
    </div>

    <div class="field">
        <label for="email">Email address</label>
        <input class="ainput" type="email" id="email" name="email" value="{{ old('email', $user->email) }}"
               required autocomplete="username">
        <p class="hint">You sign in with this address. There is no email delivery configured yet, so
            a typo here means an administrator has to fix it for you.</p>
        @error('email')<p class="aerror">{{ $message }}</p>@enderror
    </div>

    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
        <div class="callout callout--warn">
            <b>Your email address is not confirmed.</b>
            <form method="POST" action="{{ route('verification.send') }}" style="display:inline">
                @csrf
                <button class="abtn abtn--ghost abtn--sm" style="margin-top:.5rem">Send the confirmation again</button>
            </form>
        </div>
    @endif

    <div class="form-actions">
        <button type="submit" class="abtn abtn--primary">Save details</button>
    </div>

    @if (session('status') === 'profile-updated')
        <div class="flash flash--success" style="margin-top:1rem" role="status">Your details have been saved.</div>
    @endif
</form>
