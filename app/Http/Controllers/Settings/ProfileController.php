<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if (! $request->user()->subscribed('default')) {
            unset($validated['display_name_color'], $validated['display_alias'], $validated['profile_border_style']);
        }

        if (array_key_exists('display_name_color', $validated)) {
            $validated['display_name_color'] = $validated['display_name_color'] !== null && $validated['display_name_color'] !== ''
                ? strtolower($validated['display_name_color'])
                : null;
        }

        if (array_key_exists('display_alias', $validated)) {
            $validated['display_alias'] = $validated['display_alias'] !== null && $validated['display_alias'] !== ''
                ? trim($validated['display_alias'])
                : null;
        }

        if (($validated['profile_border_style'] ?? null) === 'none') {
            $validated['profile_border_style'] = null;
        }

        $request->user()->fill($validated);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
