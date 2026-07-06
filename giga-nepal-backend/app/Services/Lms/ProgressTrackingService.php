<?php

namespace App\Services\Lms;

use Illuminate\Support\Facades\DB;

class ProgressTrackingService
{
    public function record(int $enrollmentId, ?int $lessonId, string $eventType = 'lesson_completed'): array
    {
        $enrollment = DB::table('lms_enrollments')->find($enrollmentId);
        if (!$enrollment) {
            return ['error' => 'Enrollment not found.'];
        }

        $totalLessons = DB::table('lms_lessons')
            ->where('lms_course_id', $enrollment->lms_course_id)
            ->where('status', 'published')
            ->count();

        DB::table('lms_progress_events')->insert([
            'lms_enrollment_id' => $enrollmentId,
            'lms_course_id' => $enrollment->lms_course_id,
            'lms_lesson_id' => $lessonId,
            'event_type' => $eventType,
            'progress_percent' => $enrollment->progress_percent,
            'metadata' => json_encode([]),
            'occurred_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $completedLessons = DB::table('lms_progress_events')
            ->where('lms_enrollment_id', $enrollmentId)
            ->where('event_type', 'lesson_completed')
            ->whereNotNull('lms_lesson_id')
            ->distinct('lms_lesson_id')
            ->count('lms_lesson_id');

        $progress = $totalLessons > 0 ? min(100, round(($completedLessons / $totalLessons) * 100, 2)) : 0;
        DB::table('lms_enrollments')->where('id', $enrollmentId)->update([
            'progress_percent' => $progress,
            'completed_at' => $progress >= 100 ? now() : null,
            'updated_at' => now(),
        ]);

        return ['enrollment_id' => $enrollmentId, 'progress_percent' => $progress, 'completed_lessons' => $completedLessons, 'total_lessons' => $totalLessons];
    }
}
