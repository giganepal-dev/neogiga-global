<?php

namespace App\Http\Controllers\Api\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\Affiliate\Affiliate;
use App\Services\Affiliate\AffiliateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AffiliateController extends Controller
{
    public function __construct(private readonly AffiliateService $affiliates)
    {
    }

    /** POST /api/v1/affiliate/apply  (auth: api.token) */
    public function apply(Request $request): JsonResponse
    {
        $data = $request->validate([
            'display_name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:190'],
            'default_currency' => ['nullable', 'string', 'size:3'],
            'payout_method' => ['nullable', 'string', 'max:60'],
            'payout_details' => ['nullable', 'array'],
        ]);

        $affiliate = $this->affiliates->apply($request->user(), $data);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $affiliate->id,
                'status' => $affiliate->status,
                'codes' => $affiliate->codes->pluck('code'),
            ],
        ], 201);
    }

    /** GET /api/v1/affiliate/dashboard  (auth: api.token) */
    public function dashboard(Request $request): JsonResponse
    {
        $affiliate = Affiliate::with('codes')->where('user_id', $request->user()->id)->first();

        if (!$affiliate) {
            return response()->json(['success' => true, 'data' => null, 'message' => 'Not an affiliate yet.']);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $affiliate->status,
                'codes' => $affiliate->codes->map(fn ($c) => [
                    'code' => $c->code,
                    'clicks' => $c->click_count,
                    'orders' => $c->order_count,
                ]),
                'earnings' => [
                    'pending' => (float) $affiliate->commissions()->where('status', 'pending')->sum('commission_amount'),
                    'approved' => (float) $affiliate->commissions()->where('status', 'approved')->sum('commission_amount'),
                    'paid' => (float) $affiliate->commissions()->where('status', 'paid')->sum('commission_amount'),
                    'currency' => $affiliate->default_currency,
                ],
            ],
        ]);
    }

    /** POST /api/v1/affiliate/track  (public) — records a referral click. */
    public function track(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:32'],
            'visitor_token' => ['nullable', 'string', 'max:80'],
            'utm_source' => ['nullable', 'string', 'max:120'],
            'utm_medium' => ['nullable', 'string', 'max:120'],
            'utm_campaign' => ['nullable', 'string', 'max:120'],
            'source_url' => ['nullable', 'string', 'max:1024'],
        ]);

        $attribution = $this->affiliates->trackClick($data['code'], [
            'visitor_token' => $data['visitor_token'] ?? null,
            'utm_source' => $data['utm_source'] ?? null,
            'utm_medium' => $data['utm_medium'] ?? null,
            'utm_campaign' => $data['utm_campaign'] ?? null,
            'source_url' => $data['source_url'] ?? $request->headers->get('referer'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Fail-soft: unknown code returns 200 with tracked=false (no info leak).
        return response()->json([
            'success' => true,
            'data' => [
                'tracked' => (bool) $attribution,
                'visitor_token' => $attribution?->visitor_token,
            ],
        ]);
    }
}
