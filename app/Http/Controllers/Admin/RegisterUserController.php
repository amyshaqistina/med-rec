<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterUserController extends Controller
{
    public function create(): View
    {
        Gate::authorize('register-users');

        return view('admin.users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('register-users');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        $user = new User([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);
        $user->email_verified_at = now();
        $user->save();

        return redirect()
            ->route('admin.users.create')
            ->with('status', $user->name.' has been registered and can now sign in.');
    }
}
