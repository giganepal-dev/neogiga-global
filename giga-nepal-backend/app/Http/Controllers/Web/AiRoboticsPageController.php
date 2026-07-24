<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AiRobotics\RobotModel;
use App\Models\AiRobotics\RobotType;
use App\Models\AiRobotics\RobotApplication;
use App\Models\AiRobotics\AiModel;
use App\Models\AiRobotics\InstitutionalPackage;
use App\Models\AiRobotics\LearningPath;
use App\Models\AiRobotics\Event;
use App\Models\AiRobotics\Article;
use App\Models\AiRobotics\RobotManufacturer;
use App\Models\Lms\Course;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiRoboticsPageController extends Controller
{
    /**
     * AI & Robotics Homepage
     */
    public function index(?Request $request = null): View
    {
        $featuredRobots = RobotModel::active()->featured()->with('manufacturer', 'type')->limit(6)->get();
        $featuredAiModels = AiModel::active()->featured()->limit(6)->get();
        $featuredCourses = Course::where('status', 'published')->limit(4)->get();
        $featuredLearningPaths = LearningPath::active()->featured()->withCount('courses')->limit(3)->get();
        $featuredPackages = InstitutionalPackage::active()->featured()->limit(3)->get();
        $featuredManufacturers = RobotManufacturer::active()->limit(8)->get();
        $upcomingEvents = Event::upcoming()->limit(4)->get();
        $recentArticles = Article::published()->limit(4)->get();
        $robotTypes = RobotType::where('is_active', true)->withCount('robotModels')->orderBy('sort_order')->get();

        return view('frontend.ai-robotics.index', compact(
            'featuredRobots', 'featuredAiModels', 'featuredCourses',
            'featuredLearningPaths', 'featuredPackages', 'featuredManufacturers',
            'upcomingEvents', 'recentArticles', 'robotTypes'
        ));
    }

    /**
     * Robot Explorer
     */
    public function robots(?Request $request = null): View
    {
        $query = RobotModel::active()->with(['manufacturer', 'type', 'applications']);

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('model_number', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            });
        }
        if ($type = $request->input('type')) {
            $query->where('robot_type_id', $type);
        }
        if ($application = $request->input('application')) {
            $query->whereHas('applications', fn($q) => $q->where('robot_applications.id', $application));
        }
        if ($manufacturer = $request->input('manufacturer')) {
            $query->where('manufacturer_id', $manufacturer);
        }
        if ($ros = $request->input('ros')) {
            $query->where('ros2_support', true);
        }
        if ($indoorOutdoor = $request->input('use')) {
            $query->where('indoor_outdoor', $indoorOutdoor);
        }

        $robots = $query->orderByDesc('is_featured')->orderBy('name')->paginate(12);

        $types = RobotType::where('is_active', true)->withCount('robotModels')->get();
        $applications = RobotApplication::where('is_active', true)->withCount('robotModels')->get();
        $manufacturers = RobotManufacturer::active()->has('robotModels')->get();

        return view('frontend.ai-robotics.robots', compact(
            'robots', 'types', 'applications', 'manufacturers'
        ));
    }

    /**
     * Robot Detail
     */
    public function robotDetail(string $slug): View
    {
        $robot = RobotModel::active()
            ->with(['manufacturer', 'type', 'applications', 'compatibleProducts', 'categories'])
            ->where('slug', $slug)
            ->firstOrFail();

        $relatedRobots = RobotModel::active()
            ->where('id', '!=', $robot->id)
            ->where('robot_type_id', $robot->robot_type_id)
            ->with('manufacturer')
            ->limit(4)
            ->get();

        return view('frontend.ai-robotics.robot-detail', compact('robot', 'relatedRobots'));
    }

    /**
     * AI Models Directory
     */
    public function aiModels(?Request $request = null): View
    {
        $query = AiModel::active();

        if ($search = $request->input('q')) {
            $query->where('name', 'ilike', "%{$search}%");
        }
        if ($type = $request->input('type')) {
            $query->where('model_type', $type);
        }

        $models = $query->orderByDesc('is_featured')->paginate(12);

        return view('frontend.ai-robotics.ai-models', compact('models'));
    }

    /**
     * AI Model Detail
     */
    public function aiModelDetail(string $slug): View
    {
        $model = AiModel::active()->with('hardware')->where('slug', $slug)->firstOrFail();
        return view('frontend.ai-robotics.ai-model-detail', compact('model'));
    }

    /**
     * AI & Robotics Store (products filtered by AI/robotics categories)
     */
    public function store(?Request $request = null): View
    {
        $query = \App\Models\Marketplace\Product::query()
            ->where('status', 'approved')
            ->with(['brand', 'manufacturer', 'category']);

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('mpn', 'ilike', "%{$search}%")
                    ->orWhere('sku', 'ilike', "%{$search}%");
            });
        }

        $products = $query->orderByDesc('is_featured')->paginate(24);

        return view('frontend.ai-robotics.store', compact('products'));
    }

    /**
     * Learning Paths
     */
    public function learning(): View
    {
        $paths = LearningPath::active()->withCount('courses')->get();
        $courses = Course::where('status', 'published')->limit(12)->get();

        return view('frontend.ai-robotics.learning', compact('paths', 'courses'));
    }

    /**
     * Learning Path Detail
     */
    public function learningPathDetail(string $slug): View
    {
        $path = LearningPath::active()->with(['courses'])->where('slug', $slug)->firstOrFail();
        return view('frontend.ai-robotics.learning-path-detail', compact('path'));
    }

    /**
     * Lab & Demos
     */
    public function lab(): View
    {
        $packages = InstitutionalPackage::active()->get();
        return view('frontend.ai-robotics.lab', compact('packages'));
    }

    /**
     * Institutional Packages
     */
    public function institutional(): View
    {
        $packages = InstitutionalPackage::active()->get();
        return view('frontend.ai-robotics.institutional', compact('packages'));
    }

    /**
     * Manufacturers Directory
     */
    public function manufacturers(): View
    {
        $manufacturers = RobotManufacturer::active()->withCount('robotModels')->orderBy('name')->get();
        return view('frontend.ai-robotics.manufacturers', compact('manufacturers'));
    }

    /**
     * Manufacturer Detail
     */
    public function manufacturerDetail(string $slug): View
    {
        $manufacturer = RobotManufacturer::active()->where('slug', $slug)->firstOrFail();
        $robots = RobotModel::active()->where('manufacturer_id', $manufacturer->id)->get();
        return view('frontend.ai-robotics.manufacturer-detail', compact('manufacturer', 'robots'));
    }

    /**
     * Events
     */
    public function events(): View
    {
        $events = Event::upcoming()->get();
        $pastEvents = Event::active()->where('ends_at', '<', now())->latest('starts_at')->limit(6)->get();
        return view('frontend.ai-robotics.events', compact('events', 'pastEvents'));
    }

    /**
     * News & Releases
     */
    public function news(?Request $request = null): View
    {
        $query = Article::published();
        if ($type = $request->input('type')) {
            $query->where('article_type', $type);
        }
        $articles = $query->orderByDesc('published_at')->paginate(12);
        $featured = Article::published()->where('is_featured', true)->first();

        return view('frontend.ai-robotics.news', compact('articles', 'featured'));
    }

    /**
     * Article Detail
     */
    public function articleDetail(string $slug): View
    {
        $article = Article::published()->where('slug', $slug)->firstOrFail();
        return view('frontend.ai-robotics.article-detail', compact('article'));
    }

    /**
     * Integrators
     */
    public function integrators(): View
    {
        $integrators = \App\Models\AiRobotics\Integrator::active()->orderBy('name')->get();
        return view('frontend.ai-robotics.integrators', compact('integrators'));
    }

    /**
     * Robot Comparison
     */
    public function compare(?Request $request = null): View
    {
        $ids = $request->input('robots', []);
        $robots = RobotModel::active()->whereIn('id', $ids)->with(['manufacturer', 'type'])->get();
        return view('frontend.ai-robotics.compare', compact('robots'));
    }

    /**
     * Integrator Detail
     */
    public function integratorDetail(string $slug): View
    {
        $integrator = \App\Models\AiRobotics\Integrator::active()->where('slug', $slug)->firstOrFail();
        return view('frontend.ai-robotics.integrator-detail', compact('integrator'));
    }

    /**
     * Institutional Package Detail
     */
    public function institutionalDetail(string $slug): View
    {
        $package = InstitutionalPackage::active()->with('products')->where('slug', $slug)->firstOrFail();
        return view('frontend.ai-robotics.institutional-detail', compact('package'));
    }

    /**
     * Demo Request Form
     */
    public function demoRequestForm(?Request $request = null): View
    {
        $robots = RobotModel::active()->orderBy('name')->get();
        $manufacturers = RobotManufacturer::active()->orderBy('name')->get();
        return view('frontend.ai-robotics.demo-request', compact('robots', 'manufacturers'));
    }

    /**
     * Store Demo Request
     */
    public function storeDemoRequest(Request $request)
    {
        $data = $request->validate([
            'contact_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'institution_name' => 'nullable|string|max:255',
            'robot_model_id' => 'nullable|exists:robot_models,id',
            'manufacturer_id' => 'nullable|exists:ai_robotics_manufacturers,id',
            'requirements' => 'nullable|string|max:2000',
        ]);

        \App\Models\AiRobotics\DemoRequest::create($data);

        return redirect('/ai')->with('status', 'Demo request submitted. We will contact you shortly.');
    }

    /**
     * Lab Booking Form
     */
    public function labBookingForm(?Request $request = null): View
    {
        return view('frontend.ai-robotics.lab-booking');
    }

    /**
     * Store Lab Booking
     */
    public function storeLabBooking(Request $request)
    {
        $data = $request->validate([
            'contact_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'institution_name' => 'nullable|string|max:255',
            'booking_type' => 'required|string|in:demonstration,workshop,training,testing,prototyping',
            'preferred_date' => 'required|date|after:today',
            'preferred_time' => 'nullable|string|max:20',
            'requirements' => 'nullable|string|max:2000',
        ]);

        \App\Models\AiRobotics\LabBooking::create($data);

        return redirect('/ai/lab')->with('status', 'Lab booking submitted. We will confirm your session shortly.');
    }

    /**
     * Event Detail
     */
    public function eventDetail(string $slug): View
    {
        $event = Event::active()->where('slug', $slug)->firstOrFail();
        return view('frontend.ai-robotics.event-detail', compact('event'));
    }

    /**
     * Store Event Registration
     */
    public function storeEventRegistration(Request $request, string $slug)
    {
        $event = Event::active()->where('slug', $slug)->firstOrFail();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'institution' => 'nullable|string|max:255',
        ]);

        $event->registrations()->create($data);
        $event->increment('current_attendees');

        return redirect('/ai/events')->with('status', 'Registration confirmed for ' . $event->name . '.');
    }
}
