<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

/**
 * Admin-only account management: create, edit and remove user accounts
 * and assign them a role (admin / manager / receptionist / orderpicker).
 * Access is restricted by the "role:admin" middleware on the route group.
 */
class UserController extends Controller
{
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

    public function create()
    {
        $roles = Role::orderBy('name')->pluck('name');

        return view('admin.users.create', compact('roles'));
    }

    /**
     * Validate and create a new account with the selected role.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => ['required', Rule::in(Role::pluck('name'))],
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
        $roles = Role::orderBy('name')->pluck('name');

        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Update an existing account's name, email and role.
     * Password is only updated when a new one is provided.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8',
            'role' => ['required', Rule::in(Role::pluck('name'))],
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

    /**
     * Delete an account.
     * Admins cannot delete their own account (avoids accidental lockout).
     */
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
