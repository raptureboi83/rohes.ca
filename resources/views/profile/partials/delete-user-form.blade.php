<h2 style="font-size:1.05rem;margin:0 0 .3rem">Delete this account</h2>
<p class="hint" style="margin:0 0 1.25rem">
    Deleting your account signs you out immediately. Content you have written stays on the
    website. This cannot be undone.
</p>

<div x-data="{ open: false }">
    <button type="button" class="abtn abtn--danger" x-show="! open" x-on:click="open = true">
        Delete my account
    </button>

    <div x-show="open" x-cloak class="callout callout--warn">
        <b>Are you sure?</b> Type your password to confirm.

        <form method="POST" action="{{ route('profile.destroy') }}" style="margin-top:1rem">
            @csrf
            @method('delete')

            <div class="field">
                <label for="delete_password">Your password</label>
                <input class="ainput" type="password" id="delete_password" name="password" required>
                @error('password', 'delete_password')<p class="aerror">{{ $message }}</p>@enderror
            </div>

            <div class="form-actions">
                <button type="submit" class="abtn abtn--danger">Yes, delete my account</button>
                <button type="button" class="abtn abtn--ghost" x-on:click="open = false">Keep my account</button>
            </div>
        </form>
    </div>
</div>
