<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataErasureRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DataErasureRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $requests = DataErasureRequest::query()
            ->with(['user:id,name,username,email'])
            ->latest()
            ->get()
            ->map(fn (DataErasureRequest $dataRequest) => [
                'id'           => $dataRequest->id,
                'request_type' => $dataRequest->request_type,
                'details'      => $dataRequest->details,
                'status'       => $dataRequest->status,
                'admin_notes'  => $dataRequest->admin_notes,
                'created_at'   => $dataRequest->created_at?->toIso8601String(),
                'resolved_at'  => $dataRequest->resolved_at?->toIso8601String(),
                'user'         => [
                    'id'       => $dataRequest->user->id,
                    'name'     => $dataRequest->user->name,
                    'username' => $dataRequest->user->username,
                    'email'    => $dataRequest->user->email,
                ],
            ])
            ->values()
            ->all();

        return Inertia::render('admin/DataRequests', [
            'requests' => $requests,
            'flash'    => [
                'success' => $request->session()->get('success'),
                'error'   => $request->session()->get('error'),
            ],
        ]);
    }

    public function update(Request $request, DataErasureRequest $dataErasureRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status'      => ['required', Rule::in(['pending', 'in_progress', 'resolved'])],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $dataErasureRequest->fill([
            'status'      => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? null,
        ]);

        if ($validated['status'] === 'resolved') {
            $dataErasureRequest->resolved_at = $dataErasureRequest->resolved_at ?? now();
        } else {
            $dataErasureRequest->resolved_at = null;
        }

        $dataErasureRequest->save();

        return redirect()
            ->back()
            ->with('success', 'La demande a été mise à jour.');
    }

    public function destroyAccount(DataErasureRequest $dataErasureRequest): RedirectResponse
    {
        if ($dataErasureRequest->request_type !== 'account_deletion') {
            return redirect()
                ->back()
                ->with('error', 'Cette action n’est pas disponible pour le type de demande sélectionné.');
        }

        $user = $dataErasureRequest->user;

        if ($user === null) {
            return redirect()
                ->back()
                ->with('error', 'Le compte associé à cette demande a déjà été supprimé.');
        }

        DB::transaction(function () use ($user, $dataErasureRequest) {
            $dataErasureRequest->forceFill([
                'status'      => 'resolved',
                'resolved_at' => now(),
            ])->save();

            $user->tokens()->delete();
            $user->oauthAccounts()->delete();

            $user->subscriptions()
                ->get()
                ->each(function ($subscription) {
                    try {
                        if (! $subscription->ended()) {
                            $subscription->cancelNow();
                        }
                    } catch (\Throwable $exception) {
                        Log::warning('Échec de l’annulation d’un abonnement lors d’une suppression RGPD.', [
                            'subscription_id' => $subscription->id,
                            'user_id'         => $subscription->user_id,
                            'error'           => $exception->getMessage(),
                        ]);

                        $subscription->forceFill([
                            'stripe_status' => 'canceled',
                            'ends_at'       => now(),
                        ])->save();
                    }
                });

            $user->delete();
        });

        return redirect()
            ->route('admin.privacy.requests.index')
            ->with('success', 'Le compte utilisateur a été supprimé et la demande est clôturée.');
    }

    public function erasePersonalData(DataErasureRequest $dataErasureRequest): RedirectResponse
    {
        if ($dataErasureRequest->request_type !== 'data_deletion') {
            return redirect()
                ->back()
                ->with('error', 'Cette action n’est pas disponible pour le type de demande sélectionné.');
        }

        $user = $dataErasureRequest->user;

        if ($user === null) {
            return redirect()
                ->back()
                ->with('error', 'Le compte associé à cette demande est introuvable.');
        }

        $identifier = Str::uuid()->toString();

        DB::transaction(function () use ($user, $dataErasureRequest, $identifier) {
            $user->forceFill([
                'name'                  => 'Compte anonymisé',
                'username'              => 'anon-'.$identifier,
                'email'                 => 'anon+'.$identifier.'@deleted.local',
                'phone'                 => null,
                'address'               => null,
                'city'                  => null,
                'cp'                    => null,
                'country'               => null,
                'age'                   => 0,
                'display_name_color'    => null,
                'display_alias'         => null,
                'profile_border_style'  => null,
                'password'              => Str::random(40),
                'email_verified_at'     => null,
                'remember_token'        => null,
            ])->save();

            $user->tokens()->delete();
            $user->oauthAccounts()->delete();

            $dataErasureRequest->forceFill([
                'status'      => 'resolved',
                'resolved_at' => now(),
            ])->save();
        });

        return redirect()
            ->back()
            ->with('success', 'Les données personnelles ont été anonymisées et la demande est clôturée.');
    }
}
