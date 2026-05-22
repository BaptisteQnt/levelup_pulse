<?php

namespace App\Http\Controllers;

use App\Models\DataErasureRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DataErasureRequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'request_type' => ['required', Rule::in(['account_deletion', 'data_deletion'])],
            'details'      => ['nullable', 'string', 'max:2000'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        DataErasureRequest::create([
            'user_id'      => $user->id,
            'request_type' => $validated['request_type'],
            'details'      => $validated['details'] ?? null,
            'status'       => 'pending',
        ]);

        return redirect()
            ->route('legal.mentions')
            ->with('success', 'Votre demande a bien été enregistrée. Notre équipe reviendra vers vous rapidement.');
    }
}
