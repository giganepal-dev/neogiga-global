<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Education\EducationProject;
use App\Models\Education\BomLine;
use App\Models\Education\CodeFile;
use App\Models\Education\SensorKnowledge;
use App\Models\Lms\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EducationAdminController extends Controller
{
    /** GET /admin/education */
    public function dashboard()
    {
        $stats = [
            'total_projects' => EducationProject::count(),
            'published_projects' => EducationProject::where('verification_status', 'published')->count(),
            'draft_projects' => EducationProject::where('verification_status', 'draft')->count(),
            'total_views' => EducationProject::sum('view_count'),
            'total_enrollments' => EducationProject::sum('enrollment_count'),
            'avg_rating' => EducationProject::where('rating_avg', '>', 0)->avg('rating_avg'),
            'total_sensors' => SensorKnowledge::count(),
            'total_courses' => Course::count(),
            'categories' => EducationProject::select('category', DB::raw('count(*) as count'))->groupBy('category')->orderByDesc('count')->get(),
            'controllers' => EducationProject::whereNotNull('main_controller')->select('main_controller', DB::raw('count(*) as count'))->groupBy('main_controller')->orderByDesc('count')->get(),
            'recent_projects' => EducationProject::with('author')->latest()->limit(10)->get(),
        ];

        return view('admin.education.dashboard', $stats);
    }

    /** GET /admin/education/projects */
    public function projects(Request $request)
    {
        $query = EducationProject::with('author');

        if ($request->has('status')) {
            $query->where('verification_status', $request->input('status'));
        }
        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }
        if ($request->has('q')) {
            $q = $request->input('q');
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")
                  ->orWhere('slug', 'like', "%{$q}%")
                  ->orWhere('main_controller', 'like', "%{$q}%");
            });
        }

        $projects = $query->latest()->paginate(25)->withQueryString();
        $categories = EducationProject::select('category', DB::raw('count(*) as count'))->groupBy('category')->orderByDesc('count')->get();

        return view('admin.education.projects', compact('projects', 'categories'));
    }

    /** GET /admin/education/projects/{id} */
    public function projectShow(int $id)
    {
        $project = EducationProject::with(['bomLines.preferredProduct', 'codeFiles', 'author', 'reviewer'])->findOrFail($id);
        $totalBomCost = $project->bomLines->sum(fn ($l) => ($l->unit_price ?? 0) * $l->quantity);

        return view('admin.education.project-detail', compact('project', 'totalBomCost'));
    }

    /** POST /admin/education/projects/{id}/update */
    public function projectUpdate(Request $request, int $id)
    {
        $project = EducationProject::findOrFail($id);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'summary' => ['sometimes', 'string'],
            'description' => ['sometimes', 'string'],
            'category' => ['sometimes', 'string', 'max:100'],
            'skill_level' => ['sometimes', 'string', 'in:beginner,intermediate,advanced,expert'],
            'main_controller' => ['nullable', 'string', 'max:100'],
            'verification_status' => ['sometimes', 'string', 'in:draft,ai_generated,review_required,technically_reviewed,published,needs_update,archived'],
            'is_featured' => ['sometimes', 'boolean'],
        ]);

        $project->update($validated);

        if (($validated['verification_status'] ?? '') === 'published') {
            $project->update(['last_reviewed_at' => now(), 'reviewer_id' => auth()->id()]);
        }

        return redirect()->back()->with('success', 'Project updated.');
    }

    /** POST /admin/education/projects/{id}/approve */
    public function projectApprove(int $id)
    {
        EducationProject::where('id', $id)->update([
            'verification_status' => 'published',
            'last_reviewed_at' => now(),
            'reviewer_id' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Project approved and published.');
    }

    /** POST /admin/education/projects/{id}/reject */
    public function projectReject(Request $request, int $id)
    {
        EducationProject::where('id', $id)->update([
            'verification_status' => 'review_required',
        ]);

        return redirect()->back()->with('success', 'Project sent back for review.');
    }

    /** POST /admin/education/projects/{id}/archive */
    public function projectArchive(int $id)
    {
        EducationProject::where('id', $id)->update(['verification_status' => 'archived']);
        return redirect()->back()->with('success', 'Project archived.');
    }

    /** POST /admin/education/projects/{id}/bom/add */
    public function projectAddBomLine(Request $request, int $id)
    {
        $validated = $request->validate([
            'component_role' => ['nullable', 'string', 'max:100'],
            'preferred_mpn' => ['nullable', 'string', 'max:200'],
            'quantity' => ['required', 'integer', 'min:1'],
            'is_required' => ['nullable', 'boolean'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ]);

        $maxLine = BomLine::where('education_project_id', $id)->max('line_no') ?? 0;

        BomLine::create(array_merge($validated, [
            'education_project_id' => $id,
            'line_no' => $maxLine + 1,
        ]));

        return redirect()->back()->with('success', 'BOM line added.');
    }

    /** POST /admin/education/projects/{id}/bom/{line}/delete */
    public function projectDeleteBomLine(int $id, int $line)
    {
        BomLine::where('education_project_id', $id)->where('id', $line)->delete();
        return redirect()->back()->with('success', 'BOM line deleted.');
    }

    /** POST /admin/education/projects/{id}/code/add */
    public function projectAddCode(Request $request, int $id)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'language' => ['required', 'string', 'max:50'],
            'target_board' => ['nullable', 'string', 'max:100'],
            'source_code' => ['required', 'string'],
            'verification_status' => ['nullable', 'string', 'in:draft,verified,unverified'],
        ]);

        CodeFile::create(array_merge($validated, [
            'education_project_id' => $id,
            'author_id' => auth()->id(),
        ]));

        return redirect()->back()->with('success', 'Code file added.');
    }

    /** GET /admin/education/sensors */
    public function sensors(Request $request)
    {
        $query = SensorKnowledge::query();
        if ($request->has('q')) {
            $q = $request->input('q');
            $query->where(function ($w) use ($q) {
                $w->where('sensor_type', 'like', "%{$q}%")
                  ->orWhere('display_name', 'like', "%{$q}%");
            });
        }
        $sensors = $query->orderBy('sensor_type')->paginate(30)->withQueryString();
        return view('admin.education.sensors', compact('sensors'));
    }

    /** POST /admin/education/sensors/store */
    public function sensorStore(Request $request)
    {
        $validated = $request->validate([
            'sensor_type' => ['required', 'string', 'max:100', 'unique:sensor_knowledge,sensor_type'],
            'display_name' => ['required', 'string', 'max:200'],
            'function_description' => ['nullable', 'string'],
            'interface' => ['nullable', 'string', 'max:50'],
            'voltage_range' => ['nullable', 'string', 'max:50'],
            'range' => ['nullable', 'string', 'max:100'],
            'accuracy' => ['nullable', 'string', 'max:100'],
        ]);

        SensorKnowledge::create($validated);
        return redirect()->route('admin.education.sensors')->with('success', 'Sensor created.');
    }

    /** POST /admin/education/sensors/{id}/update */
    public function sensorUpdate(Request $request, int $id)
    {
        $sensor = SensorKnowledge::findOrFail($id);
        $sensor->update($request->validated());
        return redirect()->back()->with('success', 'Sensor updated.');
    }

    /** GET /admin/education/courses */
    public function courses()
    {
        $courses = Course::withCount('modules')->latest()->paginate(30);
        return view('admin.education.courses', compact('courses'));
    }

    /** GET /admin/education/analytics */
    public function analytics()
    {
        $stats = [
            'total_views' => EducationProject::sum('view_count'),
            'total_enrollments' => EducationProject::sum('enrollment_count'),
            'avg_rating' => EducationProject::where('rating_avg', '>', 0)->avg('rating_avg'),
            'top_projects' => EducationProject::published()->orderByDesc('view_count')->limit(10)->get(),
            'top_categories' => EducationProject::select('category', DB::raw('SUM(view_count) as total_views'), DB::raw('count(*) as project_count'))->groupBy('category')->orderByDesc('total_views')->get(),
            'top_controllers' => EducationProject::whereNotNull('main_controller')->select('main_controller', DB::raw('count(*) as count'))->groupBy('main_controller')->orderByDesc('count')->get(),
            'verification_stats' => EducationProject::select('verification_status', DB::raw('count(*) as count'))->groupBy('verification_status')->get(),
        ];

        return view('admin.education.analytics', $stats);
    }
}
