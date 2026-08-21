<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

// User management — admin + manager, role rules enforced below
class UserController extends Controller
{
    // Default role for a freshly created account
    private const DEFAULT_ROLE = 'orderpicker';

    public function index()
    {
        $sortable = ['id', 'name', 'email'];
        $sort = request('sort');
        $direction = request('direction') === 'desc' ? 'desc' : 'asc';

        $query = User::with('roles');
        $query = in_array($sort, $sortable, true) ? $query->orderBy($sort, $direction) : $query->latest();

        $users = $query->get();

        return view('admin.users.index', compact('users'));
    }

    // Roles the current actor is allowed to assign — only admin can grant admin
    private function assignableRoles(): array
    {
        $allRoles = Role::orderBy('name')->pluck('name')->all();

        if (Auth::user()->hasRole('admin')) {
            return $allRoles;
        }

        return array_values(array_diff($allRoles, ['admin']));
    }

    // A manager can't touch an admin's or another manager's account
    private function blockedForManager(User $target): bool
    {
        return ! Auth::user()->hasRole('admin') && $target->hasAnyRole(['admin', 'manager']);
    }

    public function create()
    {
        $roles = $this->assignableRoles();

        return view('admin.users.create', ['roles' => $roles, 'defaultRole' => self::DEFAULT_ROLE]);
    }

    // Create a new account — defaults to orderpicker, role stays pickable
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => ['required', Rule::in($this->assignableRoles())],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        $user->assignRole($validated['role']);

        return redirect()->route('admin.users.index')
            ->with('success', "Account voor {$user->name} aangemaakt met rol \"{$validated['role']}\".");
    }

    public function edit(User $user)
    {
        abort_if($this->blockedForManager($user), 403);

        $roles = $this->assignableRoles();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    // Update account details
    public function update(Request $request, User $user)
    {
        abort_if($this->blockedForManager($user), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8',
            'role' => ['required', Rule::in($this->assignableRoles())],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => filled($validated['password'] ?? null)
                ? Hash::make($validated['password'])
                : $user->password,
        ]);

        $user->syncRoles([$validated['role']]);

        return redirect()->route('admin.users.index')
            ->with('success', "Account van {$user->name} bijgewerkt.");
    }

    // Delete an account
    public function destroy(Request $request, User $user)
    {
        if ($request->user()->is($user)) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Je kan je eigen account niet verwijderen.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Account verwijderd.');
    }
}
