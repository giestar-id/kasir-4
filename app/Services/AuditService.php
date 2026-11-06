<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;

class AuditService
{
    public static function log(string $action, mixed $subject = null, ?array $meta = null, ?User $user = null): AuditLog
    {
        $userId = $user?->id ?? auth()->id();

        $subjectType = null;
        $subjectId = null;

        if (is_object($subject) && method_exists($subject, 'getKey')) {
            $subjectType = get_class($subject);
            $subjectId = $subject->getKey();
        }

        return AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'meta' => $meta,
            'ip' => request()?->ip(),
        ]);
    }
}
