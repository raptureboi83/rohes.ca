<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::query()->orderBy('is_admin', 'desc')->orderBy('name')->paginate(50),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', ['user' => new User()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $data['password'] = Hash::make($data['password']);
        $data['email_verified_at'] = now();
        $data['is_admin'] = $request->boolean('is_admin');

        User::create($data);

        return redirect()->route('admin.users.index')->with('success', 'User added.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', ['user' => $user]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);

        $user->fill($data);

        // Blank password field means "leave the existing password alone",
        // so an editor can rename someone without resetting their login.
        if (! empty($request->input('password'))) {
            $user->password = Hash::make($request->input('password'));
        }

        $user->is_admin = $request->boolean('is_admin');

        $this->guardLastAdmin($user, $request->boolean('is_admin'));

        $user->save();

        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->is_admin && $this->adminCount() <= 1) {
            return back()->with('error', 'You cannot delete the only administrator.');
        }

        $user->delete();

        return back()->with('success', 'User deleted.');
    }

    protected function adminCount(): int
    {
        return User::query()->where('is_admin', true)->count();
    }

    /**
     * Never let the last administrator demote themselves — that would
     * permanently lock everyone out of the site.
     */
    protected function guardLastAdmin(User $user, bool $wantsAdmin): void
    {
        if ($user->is_admin && ! $wantsAdmin && $this->adminCount() <= 1) {
            $user->is_admin = true;

            session()->flash('error', 'You cannot remove your own administrator access.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?User $user = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'is_admin' => ['nullable', 'boolean'],
            'password' => $user
                ? ['nullable', 'confirmed', Password::defaults()]
                : ['required', 'confirmed', Password::defaults()],
        ];

        $data = $request->validate($rules);

        if ($user) {
            $data['email'] = $this->uniqueEmail($data['email'], $user->id);
        }

        unset($data['is_admin'], $data['password_confirmation']);

        return $data;
    }

    protected function uniqueEmail(string $email, ?int $ignoreId = null): string
    {
        $exists = User::query()
            ->where('email', $email)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        return $exists ? $email.'+'.substr(md5($email.microtime()), 0, 6) : $email;
    }
}
