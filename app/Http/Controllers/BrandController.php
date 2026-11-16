<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Brand;
use App\Models\Client;
use App\Models\ContentPlan;
use App\Models\Post;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $showInactive = $request->boolean('inactive');
        $brands = Brand::query()
            ->where('is_active', ! $showInactive)
            ->withCount([
                'tasks as open_tasks' => fn ($q) => $q->where('status', '!=', 'done'),
                'tasks as overdue_tasks' => fn ($q) => $q->where('status', '!=', 'done')->whereDate('due_date', '<', today()),
                'posts as month_posts' => fn ($q) => $q->whereYear('publish_date', now()->year)->whereMonth('publish_date', now()->month),
                'posts as awaiting_posts' => fn ($q) => $q->whereIn('status', ['pending', 'sent', 'client_changes']),
            ])
            ->with('client')
            ->orderBy('name')->get();

        return view('brands.index', compact('brands', 'showInactive'));
    }

    public function create(Request $request)
    {
        $brand = new Brand(['color' => '#6366f1', 'client_id' => $request->query('client')]);

        return view('brands.form', ['brand' => $brand, 'clients' => Client::orderBy('name')->get()]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'description' => ['nullable', 'string', 'max:5000'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'logo' => ['nullable', 'image', 'max:4096'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $brand = new Brand(collect($data)->except('logo')->all());
        $brand->is_active = true;
        if ($request->hasFile('logo')) {
            $brand->logo_path = $request->file('logo')->store('logos', 'public');
        }
        $brand->save();
        foreach (['Brendbuk', 'Kataloq', 'Loqolar', 'Foto', 'Video'] as $name) {
            $brand->folders()->create(['name' => $name]);
        }
        Activity::log("“{$brand->name}” markasını yaratdı", $brand, $brand->id);

        return redirect()->route('brands.show', $brand)->with('success', 'Marka yaradıldı.');
    }

    public function show(Brand $brand)
    {
        $stats = [
            'open_tasks' => $brand->tasks()->where('status', '!=', 'done')->count(),
            'overdue' => $brand->tasks()->where('status', '!=', 'done')->whereDate('due_date', '<', today())->count(),
            'files' => $brand->libraryFiles()->count(),
            'month_posts' => $brand->posts()->whereYear('publish_date', now()->year)->whereMonth('publish_date', now()->month)->count(),
            'awaiting_client' => $brand->posts()->where('status', 'sent')->count(),
        ];
        $currentPlan = ContentPlan::where('brand_id', $brand->id)->where('year', now()->year)->where('month', now()->month)->first();
        $nextPosts = Post::where('brand_id', $brand->id)->where('publish_date', '>=', today())->orderBy('publish_date')->limit(5)->get();
        $activities = Activity::with('user')->where('brand_id', $brand->id)->latest()->limit(10)->get();
        $brand->load('client', 'clientUsers');

        return view('brands.show', compact('brand', 'stats', 'currentPlan', 'nextPosts', 'activities'));
    }

    public function edit(Brand $brand)
    {
        return view('brands.form', ['brand' => $brand, 'clients' => Client::orderBy('name')->get()]);
    }

    public function update(Request $request, Brand $brand)
    {
        $data = $this->validated($request);
        $brand->fill(collect($data)->except('logo')->all());
        $brand->is_active = $request->boolean('is_active');
        if ($request->hasFile('logo')) {
            $brand->logo_path = $request->file('logo')->store('logos', 'public');
        }
        $brand->save();

        return redirect()->route('brands.show', $brand)->with('success', 'Marka yeniləndi.');
    }
}
