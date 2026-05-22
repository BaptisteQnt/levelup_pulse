<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PowersController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::query()
            ->select(['id', 'name', 'username', 'email', 'is_admin', 'created_at'])
            ->orderBy('name')
            ->orderBy('username')
            ->get()
            ->map(fn (User $user) => [
                'id'        => $user->id,
                'name'      => $user->name,
                'username'  => $user->username,
                'email'     => $user->email,
                'is_admin'  => $user->is_admin,
                'joined_at' => $user->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/Powers', [
            'users' => $users,
            'flash' => [
                'success' => $request->session()->get('success'),
                'error'   => $request->session()->get('error'),
            ],
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'Tu ne peux pas modifier tes propres pouvoirs.');
        }

        $validated = $request->validate([
            'is_admin' => ['required', 'boolean'],
        ]);

        $user->forceFill([
            'is_admin' => $validated['is_admin'],
        ])->save();

        $message = $validated['is_admin']
            ? sprintf('%s est maintenant administrateur.', $user->name ?? $user->username)
            : sprintf("Les droits d'administrateur de %s ont été retirés.", $user->name ?? $user->username);

        return back()->with('success', $message);
    }
}
