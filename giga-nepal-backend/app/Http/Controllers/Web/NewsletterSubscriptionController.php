<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Marketing\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NewsletterSubscriptionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'return_path' => ['nullable', 'string', 'max:500'],
        ]);

        NewsletterSubscriber::query()->updateOrCreate(
            ['email' => strtolower($data['email'])],
            [
                'source' => 'public_landing',
                'status' => 'subscribed',
                'double_opt_in_token' => Str::random(48),
                'unsubscribed_at' => null,
            ],
        );

        $returnPath = (string) ($data['return_path'] ?? '/en');
        if (! str_starts_with($returnPath, '/') || str_starts_with($returnPath, '//')) {
            $returnPath = '/en';
        }

        return redirect($returnPath . '#newsletter')
            ->with('status', 'You are subscribed to NeoGiga updates.');
    }
}
