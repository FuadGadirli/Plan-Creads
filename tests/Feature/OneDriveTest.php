<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Brand;
use App\Models\Setting;
use App\Models\User;
use App\Services\OneDrive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OneDriveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.microsoft.client_id' => 'cid', 'services.microsoft.client_secret' => 'secret', 'services.microsoft.tenant' => 'tid']);
        Setting::putSecret('onedrive_refresh_token', 'refresh-1');
    }

    public function test_upload_goes_to_onedrive_and_nothing_is_stored_locally(): void
    {
        Http::fake([
            'login.microsoftonline.com/*' => Http::response(['access_token' => 'at', 'refresh_token' => 'refresh-2', 'expires_in' => 3600]),
            'graph.microsoft.com/v1.0/me/drive/root:*createUploadSession' => Http::response(['uploadUrl' => 'https://upload.onedrive.test/session1']),
            'upload.onedrive.test/*' => Http::sequence()
                ->push([], 202)
                ->push(['id' => 'ITEM123', 'name' => 'video.mp4', 'size' => 10, 'file' => ['mimeType' => 'video/mp4']], 201),
            'graph.microsoft.com/v1.0/me/drive/items/ITEM123' => Http::response(['@microsoft.graph.downloadUrl' => 'https://dl.onedrive.test/file']),
        ]);

        $user = User::factory()->employee(['brands', 'brands.files'])->create();
        $brand = Brand::create(['name' => 'Nar Fitness']);
        $this->assertTrue(OneDrive::connected());

        $start = $this->actingAs($user)->postJson('/uploads/start', ['name' => 'video.mp4', 'size' => 10, 'brand_id' => $brand->id, 'scope' => 'files'])
            ->assertOk()->assertJson(['disk' => 'onedrive'])->json();
        $h = ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/octet-stream'];
        $this->call('POST', "/uploads/{$start['id']}/chunk", [], [], [], $h + ['HTTP_X_OFFSET' => '0'], '12345')->assertJson(['done' => false]);
        $this->call('POST', "/uploads/{$start['id']}/chunk", [], [], [], $h + ['HTTP_X_OFFSET' => '5'], '67890')->assertJson(['done' => true]);

        $a = Attachment::first();
        $this->assertSame('onedrive', $a->disk);
        $this->assertSame('ITEM123', $a->remote_id);
        $this->assertNull($a->path);

        Http::assertSent(fn ($r) => str_contains($r->url(), rawurlencode('Creads Plan').'/Nar%20Fitness/Fayllar/video.mp4:/createUploadSession'));
        Http::assertSent(fn ($r) => $r->url() === 'https://upload.onedrive.test/session1' && $r->header('Content-Range')[0] === 'bytes 5-9/10');
        $this->assertSame('refresh-2', Setting::getSecret('onedrive_refresh_token'));

        // Fayla baxış OneDrive-ın müvəqqəti linkinə yönləndirir
        $this->actingAs($user)->get("/files/{$a->id}")->assertRedirect('https://dl.onedrive.test/file');
    }
}
