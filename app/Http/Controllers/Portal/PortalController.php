<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;

abstract class PortalController extends Controller
{
    /** Müştərinin hazırda seçdiyi marka (birdən çox markası ola bilər) */
    protected function brand(Request $request): Brand
    {
        $brands = $request->user()->brands()->where('is_active', true)->orderBy('name')->get();
        abort_if($brands->isEmpty(), 403, 'Hesabınıza hələ marka təyin edilməyib. Agentliklə əlaqə saxlayın.');
        $brand = $brands->firstWhere('id', $request->session()->get('portal.brand')) ?? $brands->first();
        view()->share('portalBrands', $brands);
        view()->share('portalBrand', $brand);

        return $brand;
    }

    protected function ensureBrand(Request $request, int $brandId): void
    {
        abort_unless($request->user()->brands()->whereKey($brandId)->exists(), 403);
        $request->session()->put('portal.brand', $brandId);
    }
}
