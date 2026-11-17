<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Client;
use App\Models\User;
use App\Notifications\InviteNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BrandContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_brand_with_contact_creates_client_account_and_crm_card(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/brands', [
            'name' => 'Qaymaq', 'color' => '#ff0000',
            'contact_name' => 'Aysel', 'contact_email' => 'aysel@qaymaq.az',
        ])->assertRedirect();

        $brand = Brand::where('name', 'Qaymaq')->first();
        $client = User::where('email', 'aysel@qaymaq.az')->first();
        $this->assertTrue($client->isClient());
        $this->assertTrue($client->brands->contains($brand));
        $this->assertNotNull($brand->client_id);
        $this->assertSame('Qaymaq', Client::find($brand->client_id)->name);
        Notification::assertSentTo($client, InviteNotification::class);

        // Müştəri öz panelini görür
        $this->actingAs($client)->get('/portal')->assertOk()->assertSee('Qaymaq');
    }

    public function test_manage_contacts_on_existing_brand(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->employee()->create();
        $brand = Brand::create(['name' => 'Marka']);

        $this->actingAs($admin)->post("/brands/{$brand->slug}/contacts", ['contact_name' => 'Ali', 'contact_email' => 'ali@m.az'])->assertRedirect();
        $ali = User::where('email', 'ali@m.az')->first();
        $this->assertTrue($brand->clientUsers()->whereKey($ali->id)->exists());

        // işçinin maili müştəri kimi əlavə oluna bilməz
        $this->actingAs($admin)->post("/brands/{$brand->slug}/contacts", ['contact_name' => 'X', 'contact_email' => $staff->email])->assertSessionHasErrors('contact_email');

        $this->actingAs($admin)->get("/brands/{$brand->slug}/edit")->assertOk()->assertSee('ali@m.az');
        $this->actingAs($admin)->get("/brands/{$brand->slug}/social")->assertSee('ali@m.az');

        $this->actingAs($admin)->post("/brands/{$brand->slug}/contacts/{$ali->id}/invite")->assertRedirect();
        Notification::assertSentToTimes($ali, InviteNotification::class, 2);

        $this->actingAs($admin)->delete("/brands/{$brand->slug}/contacts/{$ali->id}")->assertRedirect();
        $this->assertFalse($brand->clientUsers()->whereKey($ali->id)->exists());
        $this->assertFalse($ali->fresh()->is_active);
    }
}
