<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogger
{
    public function log(
        Request $request,
        string $action,
        ?Model $auditable = null,
        array $metadata = [],
        ?User $targetUser = null,
    ): AuditLog {
        $actor = $request->user();

        return AuditLog::create([
            'actor_id' => $actor?->id,
            'target_user_id' => $targetUser?->id,
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'metadata' => $metadata === [] ? null : $metadata,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
