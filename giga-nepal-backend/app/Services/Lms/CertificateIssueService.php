<?php

namespace App\Services\Lms;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CertificateIssueService
{
    public function issueIfEligible(int $enrollmentId): array
    {
        $enrollment = DB::table('lms_enrollments')->find($enrollmentId);
        if (!$enrollment) {
            return ['issued' => false, 'reason' => 'Enrollment not found.'];
        }

        if ((float) $enrollment->progress_percent < 100) {
            return ['issued' => false, 'reason' => 'Course is not complete.'];
        }

        $existing = DB::table('lms_certificates')->where('lms_enrollment_id', $enrollmentId)->first();
        if ($existing) {
            return ['issued' => false, 'certificate_number' => $existing->certificate_number, 'reason' => 'Already issued.'];
        }

        $number = 'NG-LMS-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
        $id = DB::table('lms_certificates')->insertGetId([
            'lms_enrollment_id' => $enrollmentId,
            'lms_course_id' => $enrollment->lms_course_id,
            'user_id' => $enrollment->user_id,
            'email' => $enrollment->email,
            'certificate_number' => $number,
            'status' => 'issued',
            'issued_at' => now(),
            'metadata' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['issued' => true, 'id' => $id, 'certificate_number' => $number];
    }
}
