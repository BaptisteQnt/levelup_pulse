<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $logs = AuditLog::query()
            ->with(['actor:id,name,username,email', 'targetUser:id,name,username,email'])
            ->latest()
            ->paginate(30)
            ->through(fn (AuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'auditable_type' => $log->auditable_type,
                'auditable_id' => $log->auditable_id,
                'metadata' => $log->metadata ?? [],
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'created_at' => $log->created_at?->toIso8601String(),
                'actor' => $log->actor ? [
                    'id' => $log->actor->id,
                    'name' => $log->actor->name,
                    'username' => $log->actor->username,
                    'email' => $log->actor->email,
                ] : null,
                'target_user' => $log->targetUser ? [
                    'id' => $log->targetUser->id,
                    'name' => $log->targetUser->name,
                    'username' => $log->targetUser->username,
                    'email' => $log->targetUser->email,
                ] : null,
            ]);

        return Inertia::render('security/AuditLogs', [
            'logs' => [
                'data' => $logs->items(),
                'links' => $logs->linkCollection()->map(fn ($link) => [
                    'url' => $link['url'],
                    'label' => $link['label'],
                    'active' => $link['active'],
                ])->values(),
                'meta' => [
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                ],
            ],
        ]);
    }
}
