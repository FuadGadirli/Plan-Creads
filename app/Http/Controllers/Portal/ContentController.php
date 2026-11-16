<?php

namespace App\Http\Controllers\Portal;

use App\Models\Activity;
use App\Models\ContentPlan;
use App\Models\ContentPlanVersion;
use App\Models\User;
use App\Services\Notifier;
use Illuminate\Http\Request;

class ContentController extends PortalController
{
    public function index(Request $request)
    {
        $brand = $this->brand($request);
        $year = (int) $request->query('year', now()->year);
        $plans = ContentPlan::where('brand_id', $brand->id)->where('year', $year)
            ->whereHas('clientVersions')->with(['clientVersions'])->get()->keyBy('month');

        return view('portal.content.index', compact('brand', 'year', 'plans'));
    }

    private function plan(Request $request, ContentPlan $plan): ContentPlan
    {
        $this->ensureBrand($request, $plan->brand_id);
        $this->brand($request);
        abort_unless($plan->clientVersions()->exists(), 404);

        return $plan;
    }

    public function show(Request $request, ContentPlan $plan)
    {
        $plan = $this->plan($request, $plan);
        $versions = $plan->clientVersions()->with('attachment')->get();
        $comments = $plan->comments()->with('user')->where('visibility', 'client')->get();

        return view('portal.content.show', compact('plan', 'versions', 'comments'));
    }

    private function version(Request $request, ContentPlanVersion $version): ContentPlanVersion
    {
        $this->plan($request, $version->plan);
        abort_unless($version->status === 'sent', 422, 'Bu versiya üzrə artıq qərar verilib.');

        return $version;
    }

    public function approve(Request $request, ContentPlanVersion $version)
    {
        $version = $this->version($request, $version);
        $plan = $version->plan;
        $version->update(['status' => 'client_approved', 'client_decided_by' => $request->user()->id, 'client_decided_at' => now()]);
        $plan->syncStatus();
        Activity::log("Müştəri {$plan->title()} kontent planını (v{$version->version}) təsdiqlədi", $plan, $plan->brand_id);
        Notifier::send($this->team($version), "✅ Müştəri təsdiqlədi: {$plan->title()} kontent planı",
            $request->user()->name." v{$version->version} versiyasını təsdiqlədi. Çəkiliş və dizayn işlərinə başlamaq olar.",
            route('content.show', [$plan->brand, $plan->year, $plan->month]), $plan->brand->name);

        return back()->with('success', 'Kontent planı təsdiqlədiniz. Təşəkkür edirik!');
    }

    public function feedback(Request $request, ContentPlanVersion $version)
    {
        $version = $this->version($request, $version);
        $data = $request->validate(['body' => ['required', 'string', 'max:10000']]);
        $plan = $version->plan;
        $version->update(['status' => 'client_changes', 'client_decided_by' => $request->user()->id, 'client_decided_at' => now()]);
        $plan->comments()->create(['user_id' => $request->user()->id, 'body' => $data['body'], 'visibility' => 'client']);
        $plan->syncStatus();
        Activity::log("Müştəri {$plan->title()} kontent planına (v{$version->version}) rəy bildirdi", $plan, $plan->brand_id);
        Notifier::send($this->team($version), "✏️ Müştəri rəyi: {$plan->title()} kontent planı",
            $request->user()->name.': '.str($data['body'])->limit(300),
            route('content.show', [$plan->brand, $plan->year, $plan->month]), $plan->brand->name);

        return back()->with('success', 'Rəyiniz agentliyə göndərildi. Yenilənmiş versiya hazır olanda sizə bildiriş gələcək.');
    }

    private function team(ContentPlanVersion $version)
    {
        return User::approvers()->push($version->uploader)->filter();
    }
}
