<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Brand;
use App\Models\Client;
use App\Models\ContentPlan;
use App\Models\Post;
use App\Models\Task;
use App\Support\Labels;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $q = trim((string) $request->query('q'));
        $results = [];

        if (mb_strlen($q) >= 2) {
            $like = '%'.$q.'%';

            $results['Tapşırıqlar'] = Task::visibleTo($user)->with('brand')
                ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('description', 'like', $like))
                ->latest()->limit(10)->get()
                ->map(fn ($t) => ['title' => $t->title, 'meta' => ($t->brand?->name ?? 'Ümumi').' · '.Labels::label(Labels::TASK_STATUSES, $t->status), 'url' => route('tasks.show', $t)]);

            if ($user->hasPerm('brands')) {
                $results['Markalar'] = Brand::where('name', 'like', $like)->limit(10)->get()
                    ->map(fn ($b) => ['title' => $b->name, 'meta' => 'Marka', 'url' => route('brands.show', $b)]);

                // "Marka noyabr" kimi axtarış: ay adı + marka
                if ($user->hasPerm('brands.content')) {
                    $month = null;
                    foreach (Labels::MONTHS as $n => $name) {
                        if (mb_stripos($q, mb_substr($name, 0, 3)) !== false) {
                            $month = $n;
                        }
                    }
                    $plans = ContentPlan::with('brand')->has('versions');
                    $words = array_filter(preg_split('/\s+/u', $q), fn ($w) => mb_strlen($w) >= 2);
                    $plans->where(function ($w) use ($words, $month) {
                        foreach ($words as $word) {
                            $w->orWhereHas('brand', fn ($b) => $b->where('name', 'like', '%'.$word.'%'));
                        }
                        if ($month) {
                            $w->orWhere('month', $month);
                        }
                    });
                    if ($month) {
                        $plans->where('month', $month);
                    }
                    $results['Kontent planlar'] = $plans->orderByDesc('year')->orderByDesc('month')->limit(10)->get()
                        ->map(fn ($p) => ['title' => $p->brand->name.' — '.$p->title(), 'meta' => Labels::label(Labels::APPROVAL_STATUSES, $p->status), 'url' => route('content.show', [$p->brand, $p->year, $p->month])]);
                }

                if ($user->hasPerm('brands.social')) {
                    $results['Paylaşımlar'] = Post::with('brand')
                        ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('caption', 'like', $like))
                        ->latest('publish_date')->limit(10)->get()
                        ->map(fn ($p) => ['title' => $p->title, 'meta' => $p->brand->name.' · '.Labels::date($p->publish_date), 'url' => route('posts.show', $p)]);
                }

                if ($user->hasPerm('brands.files')) {
                    $results['Fayllar'] = Attachment::with('brand')->where('original_name', 'like', $like)
                        ->whereNotNull('attachable_id')->latest()->limit(10)->get()
                        ->map(fn ($a) => ['title' => $a->original_name, 'meta' => ($a->brand?->name ?? '—').' · '.$a->sizeLabel(), 'url' => $a->url()]);
                }
            }

            if ($user->hasPerm('crm')) {
                $results['Müştərilər'] = Client::where('name', 'like', $like)->orWhere('contact_person', 'like', $like)
                    ->limit(10)->get()
                    ->map(fn ($c) => ['title' => $c->name, 'meta' => Labels::label(Labels::CLIENT_STATUSES_CRM, $c->status), 'url' => route('clients.show', $c)]);
            }

            $results = array_filter($results, fn ($r) => $r->isNotEmpty());
        }

        return view('search', compact('q', 'results'));
    }
}
