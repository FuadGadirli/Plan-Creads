<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Attachment;
use App\Models\Brand;
use App\Models\ContentPlan;
use App\Models\ContentPlanVersion;
use App\Models\User;
use App\Services\FileStore;
use App\Services\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContentPlanController extends Controller
{
    public function index(Request $request, Brand $brand)
    {
        $year = (int) $request->query('year', now()->year);
        $plans = ContentPlan::where('brand_id', $brand->id)->where('year', $year)
            ->with('latestVersion')->withCount('versions')->get()->keyBy('month');

        return view('content.index', compact('brand', 'year', 'plans'));
    }

    private function validMonth(int $year, int $month): void
    {
        abort_unless($month >= 1 && $month <= 12 && $year >= 2000 && $year <= 2100, 404);
    }

    public function show(Brand $brand, int $year, int $month)
    {
        $this->validMonth($year, $month);
        $plan = ContentPlan::firstOrNew(['brand_id' => $brand->id, 'year' => $year, 'month' => $month]);
        $versions = $plan->exists
            ? $plan->versions()->with(['attachment', 'uploader', 'reviewer', 'clientDecider'])->get()
            : collect();
        $comments = $plan->exists ? $plan->comments()->with('user')->get() : collect();

        return view('content.show', compact('brand', 'plan', 'versions', 'comments', 'year', 'month'));
    }

    public function upload(Request $request, Brand $brand, int $year, int $month)
    {
        $this->validMonth($year, $month);
        $data = $request->validate([
            'attachment_ids' => ['required', 'array', 'min:1'],
            'note' => ['nullable', 'string', 'max:5000'],
            'action' => ['nullable', 'in:submit,send'],
        ]);
        $attachment = Attachment::whereIn('id', $data['attachment_ids'])
            ->where('uploaded_by', $request->user()->id)->whereNull('attachable_id')->latest('id')->firstOrFail();

        $version = DB::transaction(function () use ($brand, $year, $month, $attachment, $data, $request) {
            $plan = ContentPlan::firstOrCreate(['brand_id' => $brand->id, 'year' => $year, 'month' => $month]);
            $version = $plan->versions()->create([
                'version' => ((int) $plan->versions()->max('version')) + 1,
                'attachment_id' => $attachment->id,
                'note' => $data['note'] ?? null,
                'status' => 'pending',
                'uploaded_by' => $request->user()->id,
            ]);
            FileStore::claim([$attachment->id], $request->user(), $version, ['brand_id' => $brand->id]);

            return $version;
        });

        $plan = $version->plan;
        Activity::log("{$brand->name} — {$plan->title()} kontent planının v{$version->version} versiyasını yüklədi", $plan, $brand->id);

        if (($data['action'] ?? 'submit') === 'send' && $request->user()->hasPerm('approve')) {
            $this->send($version);

            return back()->with('success', "v{$version->version} təsdiqləndi və müştəriyə göndərildi.");
        }

        $plan->syncStatus();
        Notifier::send(User::approvers(), "Kontent plan təsdiq gözləyir: {$plan->title()}",
            $request->user()->name." v{$version->version} versiyasını yüklədi.".($version->note ? ' Qeyd: '.$version->note : ''),
            route('content.show', [$brand, $year, $month]), $brand->name);

        return back()->with('success', "v{$version->version} yükləndi və rəhbər təsdiqinə göndərildi.");
    }

    private function send(ContentPlanVersion $version): void
    {
        $plan = $version->plan;
        $version->update(['status' => 'sent', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
        $plan->syncStatus();
        Activity::log("{$plan->brand->name} — {$plan->title()} kontent planının v{$version->version} versiyasını müştəriyə göndərdi", $plan, $plan->brand_id);
        Notifier::send(User::clientUsersOf($plan->brand), "{$plan->title()} kontent planı təsdiqinizi gözləyir",
            ($version->version > 1 ? "Rəyinizə əsasən yenilənmiş versiya (v{$version->version}) hazırdır. " : 'Yeni ayın kontent planı hazırdır. ')
            .'Zəhmət olmasa baxıb təsdiqləyin və ya rəy bildirin.',
            route('portal.content.show', $plan), $plan->brand->name);
        if ($version->uploaded_by && $version->uploaded_by !== auth()->id()) {
            Notifier::send([$version->uploader], "Kontent plan təsdiqləndi: {$plan->title()}",
                "v{$version->version} rəhbər tərəfindən təsdiqlənib müştəriyə göndərildi.", route('content.show', [$plan->brand, $plan->year, $plan->month]), $plan->brand->name);
        }
    }

    public function approve(ContentPlanVersion $version)
    {
        abort_unless(in_array($version->status, ['pending', 'internal_changes']), 422, 'Bu versiya artıq müştəriyə göndərilib.');
        $this->send($version);

        return back()->with('success', 'Təsdiqləndi və müştəri panelinə göndərildi.');
    }

    public function reject(Request $request, ContentPlanVersion $version)
    {
        abort_unless($version->status === 'pending', 422);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $plan = $version->plan;
        $version->update(['status' => 'internal_changes', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
        $plan->comments()->create(['user_id' => $request->user()->id, 'body' => "v{$version->version} üçün düzəliş: ".$data['body'], 'visibility' => 'internal']);
        $plan->syncStatus();
        Notifier::send(array_filter([$version->uploader]), "Kontent planda düzəliş lazımdır: {$plan->title()}",
            $request->user()->name.': '.str($data['body'])->limit(250), route('content.show', [$plan->brand, $plan->year, $plan->month]), $plan->brand->name);

        return back()->with('success', 'Düzəliş tələbi göndərildi.');
    }

    public function comment(Request $request, ContentPlan $plan)
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $toClient = $request->boolean('to_client') && $plan->clientVersions()->exists();
        $plan->comments()->create(['user_id' => $request->user()->id, 'body' => $data['body'], 'visibility' => $toClient ? 'client' : 'internal']);
        if ($toClient) {
            Notifier::send(User::clientUsersOf($plan->brand), "Agentlikdən mesaj: {$plan->title()} kontent planı",
                str($data['body'])->limit(300), route('portal.content.show', $plan), $plan->brand->name);
        }

        return back();
    }

    public function remind(ContentPlan $plan)
    {
        abort_unless($plan->status === 'sent', 422);
        Notifier::send(User::clientUsersOf($plan->brand), "Xatırlatma: {$plan->title()} kontent planı təsdiqinizi gözləyir",
            'Çəkiliş və dizayn işlərinə başlamaq üçün təsdiqiniz lazımdır.', route('portal.content.show', $plan), $plan->brand->name);

        return back()->with('success', 'Müştəriyə xatırlatma göndərildi.');
    }
}
