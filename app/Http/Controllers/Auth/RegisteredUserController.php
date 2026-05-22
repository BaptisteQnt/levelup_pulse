<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\CnilPassword;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255|unique:users,username',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'cp' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:100',
            'age' => 'nullable|integer|min:1|max:120',
            'password' => ['required', new CnilPassword()],
        ]);

        $username = $request->filled('username')
            ? Str::slug($request->string('username')->value(), '_')
            : $this->makeUsernameFrom($request->email);

        if ($username === '') {
            $username = $this->makeUsernameFrom($request->email);
        } elseif (User::where('username', $username)->exists()) {
            $username = $this->makeUsernameUnique($username);
        }

        $name = $request->filled('name')
            ? $request->string('name')->value()
            : Str::title(str_replace(['_', '-'], ' ', $username));

        $age = $request->integer('age') ?? 18;

        $user = User::create([
            'name' => $name,
            'username' => $username,
            'email' => $request->email,
            'phone' => $request->input('phone'),
            'address' => $request->input('address'),
            'city' => $request->input('city'),
            'cp' => $request->input('cp'),
            'country' => $request->input('country'),
            'age' => $age,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return to_route('dashboard');
    }

    private function makeUsernameFrom(string $email): string
    {
        $base = Str::slug(Str::before($email, '@'), '_');

        if ($base === '') {
            $base = 'joueur';
        }

        return $this->makeUsernameUnique($base);
    }

    private function makeUsernameUnique(string $base): string
    {
        $username = $base;
        $suffix = 1;

        while (User::where('username', $username)->exists()) {
            $username = $base.'_'.$suffix;
            $suffix++;
        }

        return $username;
    }
}
