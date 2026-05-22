<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LegalController extends Controller
{
    public function mentions(Request $request): Response
    {
        return Inertia::render('LegalMentions');
    }

    public function privacy(Request $request): Response
    {
        return Inertia::render('PrivacyPolicy', [
            'requests' => $this->userDataRequests($request),
            'flash'    => [
                'success' => $request->session()->get('success'),
                'error'   => $request->session()->get('error'),
            ],
        ]);
    }

    public function cookies(): Response
    {
        return Inertia::render('CookiePolicy');
    }

    public function terms(): Response
    {
        return Inertia::render('TermsOfSale');
    }

    protected function userDataRequests(Request $request): array
    {
        $user = $request->user();

        if (! $user) {
            return [];
        }

        return $user->dataErasureRequests()
            ->latest()
            ->get()
            ->map(fn ($dataRequest) => [
                'id'           => $dataRequest->id,
                'request_type' => $dataRequest->request_type,
                'details'      => $dataRequest->details,
                'status'       => $dataRequest->status,
                'admin_notes'  => $dataRequest->admin_notes,
                'created_at'   => $dataRequest->created_at?->toIso8601String(),
                'resolved_at'  => $dataRequest->resolved_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }
}
