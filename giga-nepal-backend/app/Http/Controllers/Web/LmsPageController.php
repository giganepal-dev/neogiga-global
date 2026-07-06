<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Lms\CourseCatalogService;
use Illuminate\View\View;

class LmsPageController extends Controller
{
    public function index(CourseCatalogService $catalog): View
    {
        return view('web.lms.index', [
            'courses' => $catalog->courses([], 12),
            'projects' => $catalog->projects([], 12),
        ]);
    }

    public function project(string $slug, CourseCatalogService $catalog): View
    {
        $project = $catalog->project($slug);
        abort_unless($project, 404);

        return view('web.lms.project', [
            'project' => $project,
            'components' => $catalog->projectComponents($slug),
            'codeSamples' => $catalog->projectCodeSamples($slug),
        ]);
    }
}
