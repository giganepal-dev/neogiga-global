<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News\NewsCategory;
use App\Models\News\NewsPost;
use App\Models\News\NewsTag;
use App\Models\News\NewsModalCampaign;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NewsBlogAdminController extends Controller
{
    /** GET /admin/news */
    public function index(Request $request)
    {
        $query = NewsPost::with(['category', 'author']);

        if ($request->has('type')) $query->where('post_type', $request->input('type'));
        if ($request->has('status')) $query->where('status', $request->input('status'));
        if ($request->has('q')) {
            $q = $request->input('q');
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")->orWhere('slug', 'like', "%{$q}%");
            });
        }

        $posts = $query->latest()->paginate(25)->withQueryString();
        $categories = NewsCategory::active()->orderBy('name')->get();
        $stats = [
            'total' => NewsPost::count(),
            'published' => NewsPost::where('status', 'published')->count(),
            'draft' => NewsPost::where('status', 'draft')->count(),
            'scheduled' => NewsPost::where('status', 'scheduled')->count(),
            'total_views' => NewsPost::sum('view_count'),
        ];

        return view('admin.news.index', compact('posts', 'categories', 'stats'));
    }

    /** GET /admin/news/{id} */
    public function show(int $id)
    {
        $post = NewsPost::with(['category', 'author', 'tagsRel'])->findOrFail($id);
        $categories = NewsCategory::active()->orderBy('name')->get();
        return view('admin.news.show', compact('post', 'categories'));
    }

    /** POST /admin/news/store */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:500'],
            'post_type' => ['required', 'string', 'max:50'],
            'news_category_id' => ['nullable', 'integer', 'exists:news_categories,id'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'excerpt' => ['nullable', 'string'],
            'body' => ['required', 'string'],
            'hero_image' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['slug'] = Str::slug($validated['title']);
        $validated['author_id'] = auth()->id();
        $validated['status'] = 'draft';

        $post = NewsPost::create($validated);

        return redirect()->route('admin.news.show', $post->id)->with('success', 'Article created as draft.');
    }

    /** POST /admin/news/{id}/update */
    public function update(Request $request, int $id)
    {
        $post = NewsPost::findOrFail($id);
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:500'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'excerpt' => ['nullable', 'string'],
            'body' => ['sometimes', 'string'],
            'news_category_id' => ['nullable', 'integer', 'exists:news_categories,id'],
            'hero_image' => ['nullable', 'string', 'max:500'],
            'meta_title' => ['nullable', 'string', 'max:200'],
            'meta_description' => ['nullable', 'string'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_pinned' => ['sometimes', 'boolean'],
            'add_to_modal' => ['sometimes', 'boolean'],
            'comments_enabled' => ['sometimes', 'boolean'],
        ]);

        if (isset($validated['title']) && $validated['title'] !== $post->title) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $post->update($validated);
        return redirect()->back()->with('success', 'Article updated.');
    }

    /** POST /admin/news/{id}/publish */
    public function publish(int $id)
    {
        NewsPost::where('id', $id)->update(['status' => 'published', 'published_at' => now()]);
        return redirect()->back()->with('success', 'Article published.');
    }

    /** POST /admin/news/{id}/unpublish */
    public function unpublish(int $id)
    {
        NewsPost::where('id', $id)->update(['status' => 'draft']);
        return redirect()->back()->with('success', 'Article unpublished.');
    }

    /** POST /admin/news/{id}/schedule */
    public function schedule(Request $request, int $id)
    {
        $validated = $request->validate(['scheduled_at' => ['required', 'date', 'after:now']]);
        NewsPost::where('id', $id)->update(['status' => 'scheduled', 'scheduled_at' => $validated['scheduled_at']]);
        return redirect()->back()->with('success', 'Article scheduled.');
    }

    /** POST /admin/news/{id}/archive */
    public function archive(int $id)
    {
        NewsPost::where('id', $id)->update(['status' => 'archived']);
        return redirect()->back()->with('success', 'Article archived.');
    }

    /** GET /admin/news/categories */
    public function categories()
    {
        $categories = NewsCategory::withCount('posts')->orderBy('sort_order')->get();
        return view('admin.news.categories', compact('categories'));
    }

    /** POST /admin/news/categories/store */
    public function categoryStore(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'type' => ['required', 'string', 'max:50'],
        ]);
        $validated['slug'] = Str::slug($validated['name']);
        NewsCategory::create($validated);
        return redirect()->back()->with('success', 'Category created.');
    }

    /** GET /admin/news/modal */
    public function modal()
    {
        $campaigns = NewsModalCampaign::with('post')->latest()->get();
        return view('admin.news.modal', compact('campaigns'));
    }

    /** POST /admin/news/modal/store */
    public function modalStore(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'cta_text' => ['nullable', 'string', 'max:100'],
            'target_url' => ['nullable', 'string', 'max:500'],
            'frequency' => ['nullable', 'string', 'in:once_per_session,once_per_day,every_visit'],
        ]);
        NewsModalCampaign::create($validated);
        return redirect()->back()->with('success', 'Modal campaign created.');
    }

    /** POST /admin/news/modal/{id}/toggle */
    public function modalToggle(int $id)
    {
        $campaign = NewsModalCampaign::findOrFail($id);
        $campaign->update(['is_active' => !$campaign->is_active]);
        return redirect()->back()->with('success', 'Modal campaign ' . ($campaign->is_active ? 'activated' : 'deactivated') . '.');
    }
}
