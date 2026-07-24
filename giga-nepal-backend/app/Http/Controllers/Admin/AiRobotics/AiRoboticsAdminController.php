<?php

namespace App\Http\Controllers\Admin\AiRobotics;

use App\Http\Controllers\Controller;
use App\Models\AiRobotics\RobotModel;
use App\Models\AiRobotics\RobotType;
use App\Models\AiRobotics\RobotApplication;
use App\Models\AiRobotics\RobotManufacturer;
use App\Models\AiRobotics\AiModel;
use App\Models\AiRobotics\InstitutionalPackage;
use App\Models\AiRobotics\LearningPath;
use App\Models\AiRobotics\Event;
use App\Models\AiRobotics\Article;
use App\Models\AiRobotics\DemoRequest;
use App\Models\AiRobotics\LabBooking;
use App\Models\AiRobotics\Integrator;
use App\Models\AiRobotics\Project;
use App\Models\Lms\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AiRoboticsAdminController extends Controller
{
    public function dashboard(): View
    {
        $stats = [
            'robot_models' => RobotModel::count(),
            'ai_models' => AiModel::count(),
            'manufacturers' => RobotManufacturer::count(),
            'integrators' => Integrator::count(),
            'courses' => Course::count(),
            'learning_paths' => LearningPath::count(),
            'events' => Event::count(),
            'articles' => Article::count(),
            'demo_requests' => DemoRequest::count(),
            'lab_bookings' => LabBooking::count(),
            'packages' => InstitutionalPackage::count(),
            'projects' => Project::count(),
        ];

        $recentDemoRequests = DemoRequest::with(['robotModel', 'manufacturer'])->latest()->limit(10)->get();
        $recentLabBookings = LabBooking::latest()->limit(10)->get();

        return view('admin.ai-robotics.dashboard', compact('stats', 'recentDemoRequests', 'recentLabBookings'));
    }

    // ─── Robot Models ─────────────────────────────────────────────

    public function robotModels(Request $request): View
    {
        $query = RobotModel::with(['manufacturer', 'type']);
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")->orWhere('model_number', 'ilike', "%{$search}%");
            });
        }
        if ($type = $request->input('type')) {
            $query->where('robot_type_id', $type);
        }
        $models = $query->orderByDesc('created_at')->paginate(20);
        $types = RobotType::orderBy('name')->get();
        return view('admin.ai-robotics.robot-models', compact('models', 'types'));
    }

    public function robotModelCreate(): View
    {
        $types = RobotType::orderBy('name')->get();
        $applications = RobotApplication::orderBy('name')->get();
        $manufacturers = RobotManufacturer::active()->orderBy('name')->get();
        return view('admin.ai-robotics.robot-model-form', ['model' => null, 'types' => $types, 'applications' => $applications, 'manufacturers' => $manufacturers]);
    }

    public function robotModelStore(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:robot_models,slug',
            'model_number' => 'nullable|string|max:255',
            'manufacturer_id' => 'nullable|exists:ai_robotics_manufacturers,id',
            'robot_type_id' => 'nullable|exists:robot_types,id',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'image' => 'nullable|string|max:500',
            'payload_kg' => 'nullable|numeric|min:0',
            'reach_mm' => 'nullable|numeric|min:0',
            'degrees_of_freedom' => 'nullable|integer|min:0',
            'length_mm' => 'nullable|numeric|min:0',
            'width_mm' => 'nullable|numeric|min:0',
            'height_mm' => 'nullable|numeric|min:0',
            'weight_kg' => 'nullable|numeric|min:0',
            'speed_mps' => 'nullable|numeric|min:0',
            'battery_type' => 'nullable|string|max:255',
            'battery_runtime_min' => 'nullable|integer|min:0',
            'charging_time_min' => 'nullable|integer|min:0',
            'compute_platform' => 'nullable|string|max:255',
            'ai_accelerator' => 'nullable|string|max:255',
            'operating_system' => 'nullable|string|max:255',
            'ros_support' => 'nullable|boolean',
            'ros2_support' => 'nullable|boolean',
            'indoor_outdoor' => 'nullable|string|max:50',
            'ip_rating' => 'nullable|string|max:50',
            'global_price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'is_active' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'applications' => 'nullable|array',
        ]);

        $model = RobotModel::create($data);
        if (!empty($data['applications'])) {
            $model->applications()->sync($data['applications']);
        }

        return redirect('/admin/ai-robotics/robot-models')->with('status', "Robot model \"{$model->name}\" created.");
    }

    public function robotModelEdit(int $id): View
    {
        $model = RobotModel::with('applications')->findOrFail($id);
        $types = RobotType::orderBy('name')->get();
        $applications = RobotApplication::orderBy('name')->get();
        $manufacturers = RobotManufacturer::active()->orderBy('name')->get();
        return view('admin.ai-robotics.robot-model-form', ['model' => $model, 'types' => $types, 'applications' => $applications, 'manufacturers' => $manufacturers]);
    }

    public function robotModelUpdate(Request $request, int $id)
    {
        $model = RobotModel::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:robot_models,slug,' . $id,
            'model_number' => 'nullable|string|max:255',
            'manufacturer_id' => 'nullable|exists:ai_robotics_manufacturers,id',
            'robot_type_id' => 'nullable|exists:robot_types,id',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'image' => 'nullable|string|max:500',
            'payload_kg' => 'nullable|numeric|min:0',
            'reach_mm' => 'nullable|numeric|min:0',
            'degrees_of_freedom' => 'nullable|integer|min:0',
            'global_price' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'applications' => 'nullable|array',
        ]);

        $model->update($data);
        if (isset($data['applications'])) {
            $model->applications()->sync($data['applications']);
        }

        return redirect('/admin/ai-robotics/robot-models')->with('status', "Robot model updated.");
    }

    public function robotModelDestroy(int $id)
    {
        $model = RobotModel::findOrFail($id);
        $model->delete();
        return redirect('/admin/ai-robotics/robot-models')->with('status', 'Robot model archived.');
    }

    // ─── Robot Types ──────────────────────────────────────────────

    public function robotTypes(): View
    {
        $types = RobotType::withCount('robotModels')->orderBy('sort_order')->get();
        return view('admin.ai-robotics.robot-types', compact('types'));
    }

    public function robotTypeStore(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'slug' => 'required|string|max:255|unique:robot_types,slug', 'description' => 'nullable|string']);
        RobotType::create($data);
        return back()->with('status', 'Robot type created.');
    }

    // ─── Robot Applications ───────────────────────────────────────

    public function robotApplications(): View
    {
        $applications = RobotApplication::withCount('robotModels')->orderBy('sort_order')->get();
        return view('admin.ai-robotics.robot-applications', compact('applications'));
    }

    public function robotApplicationStore(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'slug' => 'required|string|max:255|unique:robot_applications,slug', 'description' => 'nullable|string']);
        RobotApplication::create($data);
        return back()->with('status', 'Robot application created.');
    }

    // ─── AI Models ────────────────────────────────────────────────

    public function aiModels(Request $request): View
    {
        $query = AiModel::query();
        if ($search = $request->input('search')) {
            $query->where('name', 'ilike', "%{$search}%");
        }
        if ($type = $request->input('type')) {
            $query->where('model_type', $type);
        }
        $models = $query->orderByDesc('created_at')->paginate(20);
        return view('admin.ai-robotics.ai-models', compact('models'));
    }

    public function aiModelCreate(): View
    {
        return view('admin.ai-robotics.ai-model-form', ['model' => null]);
    }

    public function aiModelStore(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:ai_models,slug',
            'provider' => 'nullable|string|max:255',
            'model_type' => 'nullable|string|max:100',
            'license_type' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);
        AiModel::create($data);
        return redirect('/admin/ai-robotics/ai-models')->with('status', "AI model created.");
    }

    public function aiModelEdit(int $id): View
    {
        $model = AiModel::findOrFail($id);
        return view('admin.ai-robotics.ai-model-form', ['model' => $model]);
    }

    public function aiModelUpdate(Request $request, int $id)
    {
        $model = AiModel::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:ai_models,slug,' . $id,
            'provider' => 'nullable|string|max:255',
            'model_type' => 'nullable|string|max:100',
            'license_type' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);
        $model->update($data);
        return redirect('/admin/ai-robotics/ai-models')->with('status', 'AI model updated.');
    }

    // ─── Manufacturers ────────────────────────────────────────────

    public function manufacturers(Request $request): View
    {
        $query = RobotManufacturer::query();
        if ($search = $request->input('search')) {
            $query->where('name', 'ilike', "%{$search}%");
        }
        $manufacturers = $query->orderBy('name')->paginate(20);
        return view('admin.ai-robotics.manufacturers', compact('manufacturers'));
    }

    public function manufacturerCreate(): View
    {
        return view('admin.ai-robotics.manufacturer-form', ['manufacturer' => null]);
    }

    public function manufacturerStore(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:ai_robotics_manufacturers,slug',
            'description' => 'nullable|string',
            'country' => 'nullable|string|max:100',
            'website_url' => 'nullable|url|max:500',
            'contact_email' => 'nullable|email|max:255',
            'is_active' => 'nullable|boolean',
        ]);
        RobotManufacturer::create($data);
        return redirect('/admin/ai-robotics/manufacturers')->with('status', 'Manufacturer created.');
    }

    public function manufacturerEdit(int $id): View
    {
        $manufacturer = RobotManufacturer::findOrFail($id);
        return view('admin.ai-robotics.manufacturer-form', ['manufacturer' => $manufacturer]);
    }

    public function manufacturerUpdate(Request $request, int $id)
    {
        $manufacturer = RobotManufacturer::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:ai_robotics_manufacturers,slug,' . $id,
            'description' => 'nullable|string',
            'country' => 'nullable|string|max:100',
            'website_url' => 'nullable|url|max:500',
            'is_active' => 'nullable|boolean',
        ]);
        $manufacturer->update($data);
        return redirect('/admin/ai-robotics/manufacturers')->with('status', 'Manufacturer updated.');
    }

    // ─── Learning Paths ───────────────────────────────────────────

    public function learningPaths(): View
    {
        $paths = LearningPath::withCount('courses')->orderByDesc('created_at')->paginate(20);
        return view('admin.ai-robotics.learning-paths', compact('paths'));
    }

    public function learningPathCreate(): View
    {
        $courses = Course::orderBy('title')->get();
        return view('admin.ai-robotics.learning-path-form', ['path' => null, 'courses' => $courses]);
    }

    public function learningPathStore(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:learning_paths,slug',
            'level' => 'required|string|in:beginner,intermediate,advanced',
            'description' => 'nullable|string',
            'estimated_hours' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'courses' => 'nullable|array',
        ]);
        $path = LearningPath::create($data);
        if (!empty($data['courses'])) {
            foreach ($data['courses'] as $index => $courseId) {
                $path->courses()->attach($courseId, ['sort_order' => $index, 'is_required' => true]);
            }
        }
        return redirect('/admin/ai-robotics/learning-paths')->with('status', 'Learning path created.');
    }

    public function learningPathEdit(int $id): View
    {
        $path = LearningPath::with('courses')->findOrFail($id);
        $courses = Course::orderBy('title')->get();
        return view('admin.ai-robotics.learning-path-form', ['path' => $path, 'courses' => $courses]);
    }

    public function learningPathUpdate(Request $request, int $id)
    {
        $path = LearningPath::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:learning_paths,slug,' . $id,
            'level' => 'required|string|in:beginner,intermediate,advanced',
            'description' => 'nullable|string',
            'estimated_hours' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'courses' => 'nullable|array',
        ]);
        $path->update($data);
        if (isset($data['courses'])) {
            $syncData = [];
            foreach ($data['courses'] as $index => $courseId) {
                $syncData[$courseId] = ['sort_order' => $index, 'is_required' => true];
            }
            $path->courses()->sync($syncData);
        }
        return redirect('/admin/ai-robotics/learning-paths')->with('status', 'Learning path updated.');
    }

    // ─── Events ───────────────────────────────────────────────────

    public function events(Request $request): View
    {
        $query = Event::query();
        if ($type = $request->input('type')) {
            $query->where('event_type', $type);
        }
        $events = $query->orderByDesc('starts_at')->paginate(20);
        return view('admin.ai-robotics.events', compact('events'));
    }

    public function eventCreate(): View
    {
        return view('admin.ai-robotics.event-form', ['event' => null]);
    }

    public function eventStore(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:ai_robotics_events,slug',
            'event_type' => 'required|string|max:50',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'location_type' => 'nullable|string|in:online,offline,hybrid',
            'starts_at' => 'required|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'ticket_price' => 'nullable|numeric|min:0',
            'max_attendees' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);
        Event::create($data);
        return redirect('/admin/ai-robotics/events')->with('status', 'Event created.');
    }

    public function eventEdit(int $id): View
    {
        $event = Event::findOrFail($id);
        return view('admin.ai-robotics.event-form', ['event' => $event]);
    }

    public function eventUpdate(Request $request, int $id)
    {
        $event = Event::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:ai_robotics_events,slug,' . $id,
            'event_type' => 'required|string|max:50',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'location_type' => 'nullable|string|in:online,offline,hybrid',
            'starts_at' => 'required|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'ticket_price' => 'nullable|numeric|min:0',
            'max_attendees' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);
        $event->update($data);
        return redirect('/admin/ai-robotics/events')->with('status', 'Event updated.');
    }

    // ─── Articles ─────────────────────────────────────────────────

    public function articles(Request $request): View
    {
        $query = Article::query();
        if ($type = $request->input('type')) {
            $query->where('article_type', $type);
        }
        $articles = $query->orderByDesc('created_at')->paginate(20);
        return view('admin.ai-robotics.articles', compact('articles'));
    }

    public function articleCreate(): View
    {
        return view('admin.ai-robotics.article-form', ['article' => null]);
    }

    public function articleStore(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:ai_robotics_articles,slug',
            'article_type' => 'required|string|max:50',
            'excerpt' => 'nullable|string|max:1000',
            'body' => 'nullable|string',
            'status' => 'nullable|string|in:draft,published',
            'is_featured' => 'nullable|boolean',
        ]);
        if (($data['status'] ?? 'draft') === 'published') {
            $data['published_at'] = now();
        }
        Article::create($data);
        return redirect('/admin/ai-robotics/articles')->with('status', 'Article created.');
    }

    public function articleEdit(int $id): View
    {
        $article = Article::findOrFail($id);
        return view('admin.ai-robotics.article-form', ['article' => $article]);
    }

    public function articleUpdate(Request $request, int $id)
    {
        $article = Article::findOrFail($id);
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:ai_robotics_articles,slug,' . $id,
            'article_type' => 'required|string|max:50',
            'excerpt' => 'nullable|string|max:1000',
            'body' => 'nullable|string',
            'status' => 'nullable|string|in:draft,published',
            'is_featured' => 'nullable|boolean',
        ]);
        if (($data['status'] ?? 'draft') === 'published' && !$article->published_at) {
            $data['published_at'] = now();
        }
        $article->update($data);
        return redirect('/admin/ai-robotics/articles')->with('status', 'Article updated.');
    }

    // ─── Demo Requests ────────────────────────────────────────────

    public function demoRequests(Request $request): View
    {
        $query = DemoRequest::with(['robotModel', 'manufacturer']);
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        $requests = $query->orderByDesc('created_at')->paginate(20);
        return view('admin.ai-robotics.demo-requests', compact('requests'));
    }

    public function demoRequestUpdateStatus(int $id, Request $request)
    {
        $demo = DemoRequest::findOrFail($id);
        $data = $request->validate(['status' => 'required|string|in:pending,contacted,scheduled,completed,cancelled']);
        $demo->update($data);
        return back()->with('status', 'Demo request status updated.');
    }

    // ─── Lab Bookings ─────────────────────────────────────────────

    public function labBookings(Request $request): View
    {
        $query = LabBooking::query();
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        $bookings = $query->orderByDesc('created_at')->paginate(20);
        return view('admin.ai-robotics.lab-bookings', compact('bookings'));
    }

    public function labBookingUpdateStatus(int $id, Request $request)
    {
        $booking = LabBooking::findOrFail($id);
        $data = $request->validate(['status' => 'required|string|in:pending,confirmed,completed,cancelled']);
        $booking->update($data);
        return back()->with('status', 'Lab booking status updated.');
    }

    // ─── Institutional Packages ───────────────────────────────────

    public function packages(): View
    {
        $packages = InstitutionalPackage::withCount('products')->orderByDesc('created_at')->paginate(20);
        return view('admin.ai-robotics.packages', compact('packages'));
    }

    public function packageCreate(): View
    {
        return view('admin.ai-robotics.package-form', ['package' => null]);
    }

    public function packageStore(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:institutional_packages,slug',
            'target_institution' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'base_price' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);
        InstitutionalPackage::create($data);
        return redirect('/admin/ai-robotics/packages')->with('status', 'Package created.');
    }

    public function packageEdit(int $id): View
    {
        $package = InstitutionalPackage::with('products')->findOrFail($id);
        return view('admin.ai-robotics.package-form', ['package' => $package]);
    }

    public function packageUpdate(Request $request, int $id)
    {
        $package = InstitutionalPackage::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:institutional_packages,slug,' . $id,
            'target_institution' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'base_price' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);
        $package->update($data);
        return redirect('/admin/ai-robotics/packages')->with('status', 'Package updated.');
    }

    // ─── Integrators ──────────────────────────────────────────────

    public function integrators(Request $request): View
    {
        $query = Integrator::query();
        if ($search = $request->input('search')) {
            $query->where('name', 'ilike', "%{$search}%");
        }
        $integrators = $query->orderBy('name')->paginate(20);
        return view('admin.ai-robotics.integrators', compact('integrators'));
    }

    public function integratorCreate(): View
    {
        return view('admin.ai-robotics.integrator-form', ['integrator' => null]);
    }

    public function integratorStore(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:ai_robotics_integrators,slug',
            'description' => 'nullable|string',
            'country' => 'nullable|string|max:100',
            'website_url' => 'nullable|url|max:500',
            'is_active' => 'nullable|boolean',
        ]);
        Integrator::create($data);
        return redirect('/admin/ai-robotics/integrators')->with('status', 'Integrator created.');
    }

    public function integratorEdit(int $id): View
    {
        $integrator = Integrator::findOrFail($id);
        return view('admin.ai-robotics.integrator-form', ['integrator' => $integrator]);
    }

    public function integratorUpdate(Request $request, int $id)
    {
        $integrator = Integrator::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:ai_robotics_integrators,slug,' . $id,
            'description' => 'nullable|string',
            'country' => 'nullable|string|max:100',
            'website_url' => 'nullable|url|max:500',
            'is_active' => 'nullable|boolean',
        ]);
        $integrator->update($data);
        return redirect('/admin/ai-robotics/integrators')->with('status', 'Integrator updated.');
    }

    // ─── Projects ─────────────────────────────────────────────────

    public function projects(Request $request): View
    {
        $query = Project::with('user');
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        $projects = $query->orderByDesc('created_at')->paginate(20);
        return view('admin.ai-robotics.projects', compact('projects'));
    }
}
