<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class SmdAdminController extends Controller
{
    public function index()
    {
        $stats = [
            'markings' => DB::table('smd_marking_codes')->count(),
            'matches' => DB::table('smd_marking_matches')->count(),
            'unique_mpns' => DB::table('smd_marking_matches')->distinct('normalized_mpn')->count(),
            'linked' => DB::table('smd_marking_matches')->whereNotNull('product_id')->count(),
            'unverified' => DB::table('smd_marking_matches')->where('verification_status', 'unverified')->count(),
            'verified' => DB::table('smd_marking_matches')->where('verification_status', 'verified')->count(),
            'packages' => DB::table('smd_packages')->count(),
        ];

        $recentMatches = DB::table('smd_marking_matches')
            ->join('smd_marking_codes', 'smd_marking_matches.smd_marking_code_id', '=', 'smd_marking_codes.id')
            ->leftJoin('products', 'smd_marking_matches.product_id', '=', 'products.id')
            ->leftJoin('manufacturers', 'smd_marking_matches.manufacturer_id', '=', 'manufacturers.id')
            ->select(
                'smd_marking_matches.*',
                'smd_marking_codes.display_marking',
                'products.name as product_name',
                DB::raw('COALESCE(manufacturers.name, smd_marking_matches.manufacturer_text) as manufacturer_name')
            )
            ->orderByDesc('smd_marking_matches.id')
            ->limit(50)
            ->get();

        return view('admin.smd.index', compact('stats', 'recentMatches'));
    }

    public function markings()
    {
        $markings = DB::table('smd_marking_codes')
            ->selectRaw('*, (SELECT count(*) FROM smd_marking_matches WHERE smd_marking_code_id = smd_marking_codes.id) as match_count')
            ->orderByDesc('match_count')
            ->paginate(50);

        return view('admin.smd.markings', compact('markings'));
    }

    public function queue()
    {
        $queue = DB::table('smd_marking_matches')
            ->join('smd_marking_codes', 'smd_marking_matches.smd_marking_code_id', '=', 'smd_marking_codes.id')
            ->leftJoin('products', 'smd_marking_matches.product_id', '=', 'products.id')
            ->leftJoin('manufacturers', 'smd_marking_matches.manufacturer_id', '=', 'manufacturers.id')
            ->whereIn('smd_marking_matches.verification_status', ['unverified', 'reported'])
            ->select('smd_marking_matches.*', 'smd_marking_codes.display_marking', 'products.name as product_name', DB::raw('COALESCE(manufacturers.name, smd_marking_matches.manufacturer_text) as manufacturer_name'))
            ->orderByDesc('smd_marking_matches.match_confidence')
            ->paginate(50);

        return view('admin.smd.queue', compact('queue'));
    }

    public function verify(int $id)
    {
        DB::table('smd_marking_matches')->where('id', $id)->update([
            'verification_status' => 'verified',
            'match_confidence' => 100,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Match verified.');
    }

    public function reject(int $id)
    {
        DB::table('smd_marking_matches')->where('id', $id)->update([
            'verification_status' => 'rejected',
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Match rejected.');
    }
}
