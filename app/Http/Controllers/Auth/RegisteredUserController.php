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
    private const OVER_18_DECLARATION_LABEL = 'Je déclare avoir 18 ans ou plus.';

    private const RGPD_CONSENT_LABEL = 'J’accepte que mes données personnelles soient traitées conformément à la politique de confidentialité.';

    private const MINIMUM_AGE_ERROR = 'Vous devez avoir au moins 18 ans pour créer un compte.';

    private const RGPD_CONSENT_ERROR = 'Vous devez accepter le traitement de vos données personnelles.';

    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/Register', [
            'over18DeclarationLabel' => self::OVER_18_DECLARATION_LABEL,
            'rgpdConsentLabel' => self::RGPD_CONSENT_LABEL,
            'minimumAgeError' => self::MINIMUM_AGE_ERROR,
            'rgpdConsentError' => self::RGPD_CONSENT_ERROR,
        ]);
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
            'age' => 'nullable|integer|min:18|max:120',
            'password' => ['required', new CnilPassword()],
            'over_18_declared' => ['required', 'accepted'],
            'rgpd_consent' => ['required', 'accepted'],
        ], [
            'age.min' => self::MINIMUM_AGE_ERROR,
            'over_18_declared.required' => self::MINIMUM_AGE_ERROR,
            'over_18_declared.accepted' => self::MINIMUM_AGE_ERROR,
            'rgpd_consent.required' => self::RGPD_CONSENT_ERROR,
            'rgpd_consent.accepted' => self::RGPD_CONSENT_ERROR,
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

        $age = $request->filled('age') ? $request->integer('age') : 18;

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
            'over_18_declared' => true,
            'over_18_declaration_label' => self::OVER_18_DECLARATION_LABEL,
            'over_18_declared_at' => now(),
            'rgpd_consent' => true,
            'rgpd_consent_label' => self::RGPD_CONSENT_LABEL,
            'rgpd_consented_at' => now(),
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
