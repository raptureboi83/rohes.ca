<h2 style="font-size:1.05rem;margin:0 0 .3rem">Change your password</h2>
<p class="hint" style="margin:0 0 1.25rem">Choose something you have not used here before.</p>

<form method="POST" action="{{ route('password.update') }}">
    @csrf
    @method('put')

    <div class="field">
        <label for="current_password">Current password</label>
        <input class="ainput" type="password" id="current_password" name="current_password"
               autocomplete="current-password">
        @error('current_password')<p class="aerror">{{ $message }}</p>@enderror
        <p class="hint">We ask for this to confirm it is really you.</p>
    </div>

    <div class="form-grid">
        <div class="field">
            <label for="password">New password</label>
            <input class="ainput" type="password" id="password" name="password"
                   autocomplete="new-password">
            @error('password')<p class="aerror">{{ $message }}</p>@enderror
            <p class="hint">At least 8 characters.</p>
        </div>

        <div class="field">
            <label for="password_confirmation">Repeat new password</label>
            <input class="ainput" type="password" id="password_confirmation" name="password_confirmation"
                   autocomplete="new-password">
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="abtn abtn--primary">Change password</button>
    </div>

    @if (session('status') === 'password-updated')
        <div class="flash flash--success" style="margin-top:1rem" role="status">Your password has been changed.</div>
    @endif
</form>
