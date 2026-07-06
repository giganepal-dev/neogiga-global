<?php

namespace App\Http\Controllers\Api\LMS;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Services\Lms\CertificateIssueService;
use App\Services\Lms\CourseCatalogService;
use App\Services\Lms\EnrollmentService;
use App\Services\Lms\ProgressTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LmsController extends Controller
{
    use ApiResponses;

    public function courses(Request $request, CourseCatalogService $catalog): JsonResponse
    {
        $filters = $request->only(['level', 'category', 'q']);

        return $this->success($catalog->courses($filters, (int) $request->query('per_page', 20)));
    }

    public function projects(Request $request, CourseCatalogService $catalog): JsonResponse
    {
        $filters = $request->only(['difficulty', 'course', 'q']);

        return $this->success($catalog->projects($filters, (int) $request->query('per_page', 20)));
    }

    public function showProject(string $slug, CourseCatalogService $catalog): JsonResponse
    {
        $project = $catalog->project($slug);

        return $project ? $this->success($project) : $this->error('Project not found.', 404);
    }

    public function projectComponents(string $slug, CourseCatalogService $catalog): JsonResponse
    {
        return $this->success($catalog->projectComponents($slug));
    }

    public function projectCodeSamples(string $slug, CourseCatalogService $catalog): JsonResponse
    {
        return $this->success($catalog->projectCodeSamples($slug));
    }

    public function courseModules(int $course, CourseCatalogService $catalog): JsonResponse
    {
        return $this->success($catalog->modulesForCourse($course));
    }

    public function enroll(Request $request, EnrollmentService $enrollments): JsonResponse
    {
        $data = $request->validate([
            'lms_course_id' => ['required', 'integer', 'exists:lms_courses,id'],
            'email' => ['nullable', 'email', 'max:190'],
        ]);

        $user = $request->user();
        $result = $enrollments->enroll($user?->id, $data['email'] ?? $user?->email, (int) $data['lms_course_id'], ['source' => 'api']);

        return $this->success($result, $result['created'] ? 201 : 200);
    }

    public function myEnrollments(Request $request, EnrollmentService $enrollments): JsonResponse
    {
        $user = $request->user();

        return $this->success($enrollments->forLearner($user?->id, $request->query('email', $user?->email)));
    }

    public function progress(Request $request, ProgressTrackingService $progress, CertificateIssueService $certificates): JsonResponse
    {
        $data = $request->validate([
            'lms_enrollment_id' => ['required', 'integer', 'exists:lms_enrollments,id'],
            'lms_lesson_id' => ['nullable', 'integer', 'exists:lms_lessons,id'],
            'event_type' => ['nullable', 'string', 'max:80'],
        ]);

        $result = $progress->record((int) $data['lms_enrollment_id'], $data['lms_lesson_id'] ?? null, $data['event_type'] ?? 'lesson_completed');
        if (($result['progress_percent'] ?? 0) >= 100) {
            $result['certificate'] = $certificates->issueIfEligible((int) $data['lms_enrollment_id']);
        }

        return $this->success($result);
    }
}
