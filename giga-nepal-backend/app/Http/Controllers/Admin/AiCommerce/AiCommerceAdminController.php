<?php

namespace App\Http\Controllers\Admin\AiCommerce;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AiCommerceAdminController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_sessions' => DB::table('ai_sessions')->count(),
            'active_sessions' => DB::table('ai_sessions')->whereNull('expires_at')->orWhere('expires_at', '>', now())->count(),
            'sessions_today' => DB::table('ai_sessions')->whereDate('created_at', today())->count(),
            'total_bom_builds' => DB::table('ai_bom_builds')->count(),
            'bom_builds_today' => DB::table('ai_bom_builds')->whereDate('created_at', today())->count(),
            'total_cart_actions' => DB::table('ai_cart_actions')->count(),
        ];

        $recentSessions = DB::table('ai_sessions')
            ->leftJoin('users', 'ai_sessions.user_id', '=', 'users.id')
            ->select('ai_sessions.*', 'users.name as user_name', 'users.email as user_email')
            ->orderByDesc('ai_sessions.created_at')
            ->limit(20)
            ->get();

        return view('admin.ai-commerce.index', compact('stats', 'recentSessions'));
    }

    public function sessions(Request $request): View
    {
        $query = DB::table('ai_sessions')
            ->leftJoin('users', 'ai_sessions.user_id', '=', 'users.id')
            ->select('ai_sessions.*', 'users.name as user_name', 'users.email as user_email');

        if ($context = $request->input('context')) {
            $query->where('ai_sessions.context', $context);
        }
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'ilike', "%{$search}%")
                    ->orWhere('users.email', 'ilike', "%{$search}%")
                    ->orWhere('ai_sessions.session_id', 'ilike', "%{$search}%")
                    ->orWhere('ai_sessions.current_goal', 'ilike', "%{$search}%");
            });
        }

        $sessions = $query->orderByDesc('ai_sessions.created_at')->paginate(20);

        return view('admin.ai-commerce.sessions', compact('sessions'));
    }

    public function sessionDetail(string $sessionId): View
    {
        $session = DB::table('ai_sessions')
            ->leftJoin('users', 'ai_sessions.user_id', '=', 'users.id')
            ->select('ai_sessions.*', 'users.name as user_name', 'users.email as user_email')
            ->where('ai_sessions.session_id', $sessionId)
            ->first();

        abort_unless($session, 404);

        $messages = json_decode($session->conversation_history ?? '[]', true) ?? [];

        return view('admin.ai-commerce.session-detail', compact('session', 'messages'));
    }

    public function bomBuilds(): View
    {
        $builds = DB::table('ai_bom_builds')
            ->leftJoin('users', 'ai_bom_builds.user_id', '=', 'users.id')
            ->select('ai_bom_builds.*', 'users.name as user_name')
            ->orderByDesc('ai_bom_builds.created_at')
            ->paginate(20);

        return view('admin.ai-commerce.bom-builds', compact('builds'));
    }

    public function settings(): View
    {
        $config = [
            'ai_model' => config('services.neoai.model', 'gpt-4o-mini'),
            'ai_api_url' => config('services.neoai.api_url', 'https://api.openai.com/v1/chat/completions'),
            'ai_api_key_set' => ! empty(config('services.neoai.api_key')),
            'max_tokens' => config('services.neoai.max_tokens', 4096),
            'temperature' => config('services.neoai.temperature', 0.7),
        ];

        return view('admin.ai-commerce.settings', compact('config'));
    }

    public function terminateSession(string $sessionId): RedirectResponse
    {
        DB::table('ai_sessions')
            ->where('session_id', $sessionId)
            ->update(['expires_at' => now()]);

        return back()->with('status', 'Session terminated.');
    }
}
