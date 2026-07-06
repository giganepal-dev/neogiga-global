<?php

namespace App\Services\Lms;

use Illuminate\Support\Facades\DB;

class EnrollmentService
{
    public function enroll(?int $userId, ?string $email, int $courseId, array $metadata = []): array
    {
        $existing = DB::table('lms_enrollments')
            ->where('lms_course_id', $courseId)
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when(!$userId && $email, fn ($q) => $q->where('email', $email))
            ->first();

        if ($existing) {
            return ['id' => $existing->id, 'status' => $existing->status, 'created' => false];
        }

        $id = DB::table('lms_enrollments')->insertGetId([
            'user_id' => $userId,
            'email' => $email,
            'lms_course_id' => $courseId,
            'status' => 'active',
            'progress_percent' => 0,
            'started_at' => now(),
            'metadata' => json_encode($metadata),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['id' => $id, 'status' => 'active', 'created' => true];
    }

    public function forLearner(?int $userId, ?string $email)
    {
        return DB::table('lms_enrollments as e')
            ->join('lms_courses as c', 'c.id', '=', 'e.lms_course_id')
            ->select('e.*', 'c.title as course_title', 'c.slug as course_slug')
            ->when($userId, fn ($q) => $q->where('e.user_id', $userId))
            ->when(!$userId && $email, fn ($q) => $q->where('e.email', $email))
            ->orderByDesc('e.id')
            ->get();
    }
}
