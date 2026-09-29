@php
    // Callers pass $isSelf explicitly: a partial that closes a Blade section
    // cannot also open one, and this file is included from inside a @section.
    $editing = isset($user) && $user->exists;
    $isSelf  = $isSelf ?? false;
@endphp

<div class="afield">
    <label for="name">Full name <span aria-hidden="true">*</span></label>
    <input class="ainput" type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required>
    @error('name')<p class="aerror">{{ $message }}</p>@enderror
</div>

<div class="afield">
    <label for="email">Email address <span aria-hidden="true">*</span></label>
    <input class="ainput" type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
           autocomplete="off">
    <p class="hint">Used to sign in. There is no email delivery set up yet, so this is also the recovery address.</p>
    @error('email')<p class="aerror">{{ $message }}</p>@enderror
</div>

<div class="form-grid">
    <div class="afield">
        <label for="password">Password @if ($editing)<span class="muted small">(leave blank to keep current)</span>@endif</label>
        <input class="ainput" type="password" id="password" name="password" autocomplete="new-password"
               @if (! $editing) required @endif>
        <p class="hint">At least 8 characters.</p>
        @error('password')<p class="aerror">{{ $message }}</p>@enderror
    </div>

    <div class="afield">
        <label for="password_confirmation">Repeat password</label>
        <input class="ainput" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
               @if (! $editing) required @endif>
    </div>
</div>

<div class="check">
    <input type="checkbox" id="is_admin" name="is_admin" value="1" @checked(old('is_admin', $user->is_admin))
           @if ($isSelf) disabled @endif>
    <label for="is_admin">
        This person is an administrator
        <span class="hint">Administrators can edit every page and read the messages inbox.</span>
    </label>
</div>

@if ($isSelf)
    {{-- The checkbox is disabled for your own account so the form cannot submit
         a demotion for the person currently signed in. --}}
    <input type="hidden" name="is_admin" value="1">
    <div class="callout">You cannot remove your own administrator access.</div>
@endif
