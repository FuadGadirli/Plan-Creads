<?php

namespace App\Http\Controllers\Portal;

use App\Models\Brand;
use App\Models\ContentPlan;
use Illuminate\Http\Request;

class DashboardController extends PortalController
{
    public function index(Request $request)
    {
        $brand = $this->brand($request);
        $monthPosts = $brand->posts()->clientVisible()
            ->whereYear('publish_date', now()->year)->whereMonth('publish_date', now()->month);

        $stats = [
            'month_total' => (clone $monthPosts)->count(),
            'awaiting' => $brand->posts()->where('status', 'sent')->count(),
            'approved' => (clone $monthPosts)->whereIn('status', ['client_approved', 'published'])->count(),
            'published' => (clone $monthPosts)->where('status', 'published')->count(),
            'files' => $brand->libraryFiles()->where('client_visible', true)->count(),
        ];

        $awaitingPosts = $brand->posts()->with('attachments')->where('status', 'sent')->orderBy('publish_date')->limit(6)->get();
        $plansAwaiting = ContentPlan::where('brand_id', $brand->id)->where('status', 'sent')->get();
        $currentPlan = ContentPlan::where('brand_id', $brand->id)->where('year', now()->year)->where('month', now()->month)
            ->whereHas('versions', fn ($q) => $q->whereIn('status', ['sent', 'client_changes', 'client_approved']))->first();
        $upcoming = $brand->posts()->clientVisible()->with('attachments')->where('publish_date', '>=', today())
            ->orderBy('publish_date')->limit(5)->get();

        return view('portal.dashboard', compact('brand', 'stats', 'awaitingPosts', 'plansAwaiting', 'currentPlan', 'upcoming'));
    }

    public function switchBrand(Request $request, Brand $brand)
    {
        $this->ensureBrand($request, $brand->id);

        return redirect()->route('portal.dashboard');
    }
}
