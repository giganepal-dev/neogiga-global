<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Services\Lms\CourseCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LmsAdminController extends Controller
{
    use ApiResponses;

    public function overview(CourseCatalogService $catalog): JsonResponse
    {
        return $this->success($catalog->dashboard());
    }

    public function courses(Request $request, CourseCatalogService $catalog): JsonResponse
    {
        return $this->success($catalog->courses(['include_drafts' => true] + $request->only(['status', 'level', 'category', 'q']), (int) $request->query('per_page', 25)));
    }

    public function storeCourse(Request $request, CourseCatalogService $catalog): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'string', 'max:190'],
            'subtitle' => ['nullable', 'string', 'max:190'],
            'description' => ['nullable', 'string'],
            'level' => ['nullable', 'string', 'max:40'],
            'status' => ['nullable', 'string', 'in:draft,published,archived'],
            'language' => ['nullable', 'string', 'max:12'],
            'estimated_minutes' => ['nullable', 'integer', 'min:0'],
            'lms_course_category_id' => ['nullable', 'integer'],
            'marketplace_id' => ['nullable', 'integer'],
            'country_id' => ['nullable', 'integer'],
            'vendor_id' => ['nullable', 'integer'],
            'instructor_user_id' => ['nullable', 'integer'],
            'thumbnail_url' => ['nullable', 'string', 'max:255'],
            'seo_title' => ['nullable', 'string', 'max:190'],
            'seo_description' => ['nullable', 'string', 'max:1000'],
            'metadata' => ['nullable', 'array'],
        ]);

        return $this->success(['id' => $catalog->createCourse($data)], 201);
    }

    public function projects(Request $request, CourseCatalogService $catalog): JsonResponse
    {
        return $this->success($catalog->projects(['include_drafts' => true] + $request->only(['status', 'difficulty', 'course', 'q']), (int) $request->query('per_page', 25)));
    }

    public function storeProject(Request $request, CourseCatalogService $catalog): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'string', 'max:190'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string'],
            'difficulty_level' => ['nullable', 'string', 'max:40'],
            'estimated_minutes' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'string', 'in:draft,published,archived'],
            'lms_course_id' => ['nullable', 'integer'],
            'lms_skill_level_id' => ['nullable', 'integer'],
            'marketplace_id' => ['nullable', 'integer'],
            'country_id' => ['nullable', 'integer'],
            'thumbnail_url' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ]);

        return $this->success(['id' => $catalog->createProject($data)], 201);
    }

    public function storeLesson(Request $request, CourseCatalogService $catalog): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'string', 'max:190'],
            'lms_course_id' => ['nullable', 'integer'],
            'lms_project_id' => ['nullable', 'integer'],
            'lms_module_id' => ['nullable', 'integer'],
            'type' => ['nullable', 'string', 'max:40'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'string'],
            'video_url' => ['nullable', 'string', 'max:255'],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_preview' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'in:draft,published,archived'],
            'metadata' => ['nullable', 'array'],
        ]);

        return $this->success(['id' => $catalog->createLesson($data)], 201);
    }

    public function enrollments(): JsonResponse
    {
        return $this->success(DB::table('lms_enrollments')->orderByDesc('id')->paginate(25));
    }

    public function certificates(): JsonResponse
    {
        return $this->success(DB::table('lms_certificates')->orderByDesc('id')->paginate(25));
    }
}
