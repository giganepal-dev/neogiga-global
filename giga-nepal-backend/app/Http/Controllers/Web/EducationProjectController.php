<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Education\EducationProjectService;
use Illuminate\Http\Request;

class EducationProjectController extends Controller
{
    public function __construct(
        private EducationProjectService $projectService,
    ) {}

    public function index(Request $request)
    {
        $filters = array_filter([
            'category' => $request->input('category'),
            'skill_level' => $request->input('skill_level'),
            'controller' => $request->input('controller'),
        ]);

        $result = $this->projectService->search(
            $request->input('q', ''),
            $filters,
            18,
            ($request->input('page', 1) - 1) * 18,
        );

        $categories = $this->projectService->getCategories();

        return view('frontend.education.index', [
            'projects' => new \Illuminate\Pagination\LengthAwarePaginator(
                $result['projects'],
                $result['total'],
                18,
                $request->input('page', 1),
                ['path' => $request->url(), 'query' => $request->query()]
            ),
            'categories' => $categories,
            'title' => 'STEM Education Projects - NeoGiga',
        ]);
    }

    public function show(string $slug)
    {
        $project = $this->projectService->getProject($slug);

        if (!$project) {
            abort(404);
        }

        $bomData = $this->projectService->getProjectBomWithLivePricing($project->id);
        $codeFiles = $this->projectService->getProjectCode($project->id);

        return view('frontend.education.show', [
            'project' => $project,
            'bom' => $bomData,
            'codeFiles' => $codeFiles,
        ]);
    }

    public function aiBuilder()
    {
        return view('frontend.education.ai-builder');
    }

    public function sensors(Request $request)
    {
        $sensors = \App\Models\Education\SensorKnowledge::query();
        if ($request->has('type')) {
            $sensors->where('sensor_type', $request->input('type'));
        }
        $sensors = $sensors->get();

        return view('frontend.education.sensors', ['sensors' => $sensors]);
    }
}
