<?php

namespace App\Services\Lms;

use App\Models\Marketplace\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CourseCatalogService
{
    public function courses(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return DB::table('lms_courses as c')
            ->leftJoin('lms_course_categories as cat', 'cat.id', '=', 'c.lms_course_category_id')
            ->select('c.*', 'cat.name as category_name', 'cat.slug as category_slug')
            ->when(($filters['status'] ?? null), fn ($q, $status) => $q->where('c.status', $status))
            ->when(! ($filters['include_drafts'] ?? false), fn ($q) => $q->where('c.status', 'published'))
            ->when(($filters['level'] ?? null), fn ($q, $level) => $q->where('c.level', $level))
            ->when(($filters['category'] ?? null), fn ($q, $category) => $q->where('cat.slug', $category))
            ->when(($filters['q'] ?? null), fn ($q, $term) => $q->where(function ($inner) use ($term) {
                $inner->where('c.title', 'like', "%{$term}%")->orWhere('c.description', 'like', "%{$term}%");
            }))
            ->orderByDesc('c.published_at')
            ->orderByDesc('c.id')
            ->paginate(max(1, min($perPage, 100)));
    }

    public function projects(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return DB::table('lms_projects as p')
            ->leftJoin('lms_courses as c', 'c.id', '=', 'p.lms_course_id')
            ->select('p.*', 'c.title as course_title', 'c.slug as course_slug')
            ->when(($filters['status'] ?? null), fn ($q, $status) => $q->where('p.status', $status))
            ->when(! ($filters['include_drafts'] ?? false), fn ($q) => $q->where('p.status', 'published'))
            ->when(($filters['difficulty'] ?? null), fn ($q, $level) => $q->where('p.difficulty_level', $level))
            ->when(($filters['course'] ?? null), fn ($q, $course) => $q->where('c.slug', $course))
            ->when(($filters['q'] ?? null), fn ($q, $term) => $q->where(function ($inner) use ($term) {
                $inner->where('p.title', 'like', "%{$term}%")->orWhere('p.summary', 'like', "%{$term}%");
            }))
            ->orderByDesc('p.published_at')
            ->orderByDesc('p.id')
            ->paginate(max(1, min($perPage, 100)));
    }

    public function project(string $slug): ?object
    {
        $project = DB::table('lms_projects as p')
            ->leftJoin('lms_courses as c', 'c.id', '=', 'p.lms_course_id')
            ->select('p.*', 'c.title as course_title', 'c.slug as course_slug')
            ->where('p.slug', $slug)
            ->where('p.status', 'published')
            ->first();

        if (! $project) {
            return null;
        }

        $project->lessons = DB::table('lms_lessons')
            ->where('lms_project_id', $project->id)
            ->where('status', 'published')
            ->orderBy('sort_order')
            ->get();

        return $project;
    }

    public function projectComponents(string $slug): Collection
    {
        $project = DB::table('lms_projects')->where('slug', $slug)->where('status', 'published')->first();
        if (! $project) {
            return new Collection;
        }

        return DB::table('lms_project_components as c')
            ->leftJoin('products as p', 'p.id', '=', 'c.product_id')
            ->where('c.lms_project_id', $project->id)
            ->where(function ($component) {
                $component->whereNull('c.product_id')
                    ->orWhereIn('c.product_id', Product::query()->published()->select('products.id'));
            })
            ->select('c.*', 'p.name as product_name', 'p.slug as product_slug', 'p.sku as product_sku')
            ->orderBy('c.sort_order')
            ->get();
    }

    public function projectCodeSamples(string $slug): Collection
    {
        $project = DB::table('lms_projects')->where('slug', $slug)->where('status', 'published')->first();
        if (! $project) {
            return new Collection;
        }

        return DB::table('lms_code_samples')
            ->where('lms_project_id', $project->id)
            ->orderBy('sort_order')
            ->get();
    }

    public function modulesForCourse(int $courseId): Collection
    {
        $modules = DB::table('lms_modules')
            ->where('lms_course_id', $courseId)
            ->where('status', 'published')
            ->orderBy('sort_order')
            ->get();

        $lessons = DB::table('lms_lessons')
            ->where('lms_course_id', $courseId)
            ->where('status', 'published')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('lms_module_id');

        return $modules->map(function ($module) use ($lessons) {
            $module->lessons = $lessons->get($module->id, collect())->values();

            return $module;
        });
    }

    public function createCourse(array $data): int
    {
        $title = $data['title'];

        return DB::table('lms_courses')->insertGetId([
            'lms_course_category_id' => $data['lms_course_category_id'] ?? null,
            'marketplace_id' => $data['marketplace_id'] ?? null,
            'country_id' => $data['country_id'] ?? null,
            'vendor_id' => $data['vendor_id'] ?? null,
            'instructor_user_id' => $data['instructor_user_id'] ?? null,
            'title' => $title,
            'slug' => $this->uniqueSlug('lms_courses', $data['slug'] ?? $title),
            'subtitle' => $data['subtitle'] ?? null,
            'description' => $data['description'] ?? null,
            'level' => $data['level'] ?? 'beginner',
            'status' => $data['status'] ?? 'draft',
            'language' => $data['language'] ?? 'en',
            'estimated_minutes' => $data['estimated_minutes'] ?? 0,
            'thumbnail_url' => $data['thumbnail_url'] ?? null,
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
            'metadata' => json_encode($data['metadata'] ?? []),
            'published_at' => ($data['status'] ?? 'draft') === 'published' ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function createProject(array $data): int
    {
        $title = $data['title'];

        return DB::table('lms_projects')->insertGetId([
            'lms_course_id' => $data['lms_course_id'] ?? null,
            'lms_skill_level_id' => $data['lms_skill_level_id'] ?? null,
            'marketplace_id' => $data['marketplace_id'] ?? null,
            'country_id' => $data['country_id'] ?? null,
            'title' => $title,
            'slug' => $this->uniqueSlug('lms_projects', $data['slug'] ?? $title),
            'summary' => $data['summary'] ?? null,
            'description' => $data['description'] ?? null,
            'difficulty_level' => $data['difficulty_level'] ?? 'beginner',
            'estimated_minutes' => $data['estimated_minutes'] ?? 0,
            'status' => $data['status'] ?? 'draft',
            'thumbnail_url' => $data['thumbnail_url'] ?? null,
            'metadata' => json_encode($data['metadata'] ?? []),
            'published_at' => ($data['status'] ?? 'draft') === 'published' ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function createLesson(array $data): int
    {
        $title = $data['title'];

        return DB::table('lms_lessons')->insertGetId([
            'lms_course_id' => $data['lms_course_id'] ?? null,
            'lms_project_id' => $data['lms_project_id'] ?? null,
            'lms_module_id' => $data['lms_module_id'] ?? null,
            'title' => $title,
            'slug' => Str::slug($data['slug'] ?? $title),
            'type' => $data['type'] ?? 'article',
            'summary' => $data['summary'] ?? null,
            'content' => $data['content'] ?? null,
            'video_url' => $data['video_url'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? 0,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_preview' => (bool) ($data['is_preview'] ?? false),
            'status' => $data['status'] ?? 'draft',
            'metadata' => json_encode($data['metadata'] ?? []),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function dashboard(): array
    {
        return [
            'courses' => DB::table('lms_courses')->count(),
            'published_courses' => DB::table('lms_courses')->where('status', 'published')->count(),
            'projects' => DB::table('lms_projects')->count(),
            'lessons' => DB::table('lms_lessons')->count(),
            'enrollments' => DB::table('lms_enrollments')->count(),
            'certificates' => DB::table('lms_certificates')->count(),
        ];
    }

    private function uniqueSlug(string $table, string $value): string
    {
        $base = Str::slug($value) ?: Str::random(8);
        $slug = $base;
        $i = 2;
        while (DB::table($table)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
