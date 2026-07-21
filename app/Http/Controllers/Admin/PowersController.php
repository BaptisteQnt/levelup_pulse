<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PowersController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::query()
            ->select([
                'id',
                'name',
                'username',
                'email',
                'is_admin',
                'is_editor',
                'is_super_admin',
                'is_security_officer',
                'created_at',
            ])
            ->orderBy('username')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'is_editor' => $user->is_editor,
                'is_super_admin' => $user->is_super_admin,
                'is_security_officer' => $user->is_security_officer,
                'joined_at' => $user->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/Powers', [
            'users' => $users,
            'canManageSensitiveRoles' => (bool) $request->user()?->is_super_admin,
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ]);
    }

    public function update(Request $request, User $user, AuditLogger $auditLogger): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'Tu ne peux pas modifier tes propres pouvoirs.');
        }

        $validated = $request->validate([
            'is_admin' => ['required', 'boolean'],
            'is_editor' => ['required', 'boolean'],
            'is_super_admin' => ['sometimes', 'boolean'],
            'is_security_officer' => ['sometimes', 'boolean'],
        ]);

        $before = $user->only([
            'is_admin',
            'is_editor',
            'is_super_admin',
            'is_security_officer',
        ]);

        $updates = [
            'is_admin' => $validated['is_admin'],
            'is_editor' => $validated['is_editor'],
        ];

        if ($request->user()->is_super_admin) {
            $updates['is_super_admin'] = $validated['is_super_admin'] ?? $user->is_super_admin;
            $updates['is_security_officer'] = $validated['is_security_officer'] ?? $user->is_security_officer;
        }

        $user->forceFill($updates)->save();

        $after = $user->only([
            'is_admin',
            'is_editor',
            'is_super_admin',
            'is_security_officer',
        ]);

        $auditLogger->log($request, 'roles.updated', $user, [
            'target_username' => $user->username,
            'before' => $before,
            'after' => $after,
        ], $user);

        return back()->with(
            'success',
            sprintf('Les roles de %s ont ete mis a jour.', $user->name ?? $user->username),
        );
    }
}
