<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Brand;
use App\Models\ContentPlan;
use App\Models\Post;
use App\Models\Task;
use App\Models\User;
use App\Notifications\PlatformNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $smm;

    private User $client;

    private Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->admin = User::factory()->admin()->create();
        $this->smm = User::factory()->employee(['tasks.create', 'brands', 'brands.files', 'brands.social', 'brands.content'])->create();
        $this->brand = Brand::create(['name' => 'Test Marka', 'color' => '#123456']);
        $this->client = User::factory()->client()->create();
        $this->client->brands()->attach($this->brand);
    }

    /** Fayl hissə-hissə yüklənir və Attachment yaranır */
    private function upload(User $user, string $content = 'PDFDATA', string $name = 'plan.pdf', string $scope = 'content'): int
    {
        $start = $this->actingAs($user)->postJson('/uploads/start', [
            'name' => $name, 'size' => strlen($content), 'mime' => 'application/pdf', 'brand_id' => $this->brand->id, 'scope' => $scope,
        ])->assertOk()->json();

        $res = $this->actingAs($user)->call('POST', '/uploads/'.$start['id'].'/chunk', [], [], [], [
            'HTTP_X_OFFSET' => '0', 'CONTENT_TYPE' => 'application/octet-stream', 'HTTP_ACCEPT' => 'application/json',
        ], $content);
        $res->assertOk()->assertJson(['done' => true]);

        return $res->json('attachment.id');
    }

    public function test_chunked_upload_in_multiple_parts(): void
    {
        $data = str_repeat('a', 100);
        $start = $this->actingAs($this->smm)->postJson('/uploads/start', ['name' => 'x.txt', 'size' => 100, 'scope' => 'files'])->json();
        $headers = ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/octet-stream'];
        $this->call('POST', '/uploads/'.$start['id'].'/chunk', [], [], [], $headers + ['HTTP_X_OFFSET' => '0'], substr($data, 0, 60))->assertJson(['done' => false]);
        // yanlış offset rədd edilir
        $this->call('POST', '/uploads/'.$start['id'].'/chunk', [], [], [], $headers + ['HTTP_X_OFFSET' => '10'], substr($data, 60))->assertStatus(500);
        $this->call('POST', '/uploads/'.$start['id'].'/chunk', [], [], [], $headers + ['HTTP_X_OFFSET' => '60'], substr($data, 60))->assertJson(['done' => true]);

        $a = Attachment::first();
        $this->assertSame(100, $a->size);
        $this->assertSame($data, Storage::disk('local')->get($a->path));
    }

    public function test_content_plan_full_approval_cycle(): void
    {
        Notification::fake();

        // SMM v1 yükləyir → rəhbər təsdiqi gözləyir, müştəri görmür
        $id = $this->upload($this->smm);
        $this->actingAs($this->smm)->post("/brands/{$this->brand->slug}/content/2026/11", ['attachment_ids' => [$id], 'action' => 'submit'])->assertRedirect();
        $plan = ContentPlan::first();
        $v1 = $plan->versions()->first();
        $this->assertSame('pending', $v1->status);
        Notification::assertSentTo($this->admin, PlatformNotification::class);

        $this->actingAs($this->client)->get("/portal/content/{$plan->id}")->assertNotFound();
        $this->actingAs($this->client)->get("/files/{$id}")->assertForbidden();

        // SMM öz-özünə təsdiqləyə bilməz
        $this->actingAs($this->smm)->post("/content-versions/{$v1->id}/approve")->assertForbidden();

        // Rəhbər təsdiqləyir → müştəriyə düşür
        $this->actingAs($this->admin)->post("/content-versions/{$v1->id}/approve")->assertRedirect();
        $this->assertSame('sent', $v1->fresh()->status);
        Notification::assertSentTo($this->client, PlatformNotification::class);
        $this->actingAs($this->client)->get("/portal/content/{$plan->id}")->assertOk()->assertSee('Təsdiqinizi gözləyir');
        $this->actingAs($this->client)->get("/files/{$id}")->assertOk();

        // Müştəri rəy bildirir
        $this->actingAs($this->client)->post("/portal/content-versions/{$v1->id}/feedback", ['body' => '3-cü postu dəyişin'])->assertRedirect();
        $this->assertSame('client_changes', $v1->fresh()->status);
        $this->assertSame('client_changes', $plan->fresh()->status);

        // v2 yüklənir, rəhbər təsdiqləyir, müştəri köhnə və yeni versiyanı görür
        $id2 = $this->upload($this->smm, 'V2', 'plan-v2.pdf');
        $this->actingAs($this->smm)->post("/brands/{$this->brand->slug}/content/2026/11", ['attachment_ids' => [$id2], 'action' => 'submit']);
        $v2 = $plan->versions()->first();
        $this->assertSame(2, $v2->version);
        $this->actingAs($this->client)->get("/portal/content/{$plan->id}")->assertSee('v1')->assertDontSee('YENİ · v2');
        $this->actingAs($this->admin)->post("/content-versions/{$v2->id}/approve");
        $this->actingAs($this->client)->get("/portal/content/{$plan->id}")->assertSee('YENİ · v2')->assertSee('KÖHNƏ · v1');

        $this->actingAs($this->client)->post("/portal/content-versions/{$v2->id}/approve")->assertRedirect();
        $this->assertSame('client_approved', $plan->fresh()->status);
        // təkrar qərar verilə bilməz
        $this->actingAs($this->client)->post("/portal/content-versions/{$v2->id}/approve")->assertStatus(422);
    }

    public function test_post_flow_and_client_isolation(): void
    {
        Notification::fake();
        $img = $this->upload($this->smm, 'IMG', 'post.jpg', 'posts');
        $this->actingAs($this->smm)->post("/brands/{$this->brand->slug}/social", [
            'title' => 'Bayram postu', 'publish_date' => today()->addDay()->toDateString(), 'type' => 'post',
            'platforms' => ['instagram'], 'caption' => 'Mətn', 'attachment_ids' => [$img], 'action' => 'submit',
        ])->assertRedirect();
        $post = Post::first();
        $this->assertSame('pending', $post->status);
        $this->assertSame(1, $post->attachments()->count());

        $this->actingAs($this->client)->get("/portal/posts/{$post->id}")->assertNotFound();
        $this->actingAs($this->admin)->post("/posts/{$post->id}/approve");
        $this->actingAs($this->client)->get("/portal/posts/{$post->id}")->assertOk()->assertSee('Bayram postu');

        // Başqa markanın müştərisi görə bilməz
        $other = User::factory()->client()->create();
        $other->brands()->attach(Brand::create(['name' => 'Başqa']));
        $this->actingAs($other)->get("/portal/posts/{$post->id}")->assertForbidden();
        $this->actingAs($other)->get("/files/{$img}")->assertForbidden();

        // Müştəri agentlik panelinə girə bilməz
        $this->actingAs($this->client)->get('/tasks')->assertRedirect('/portal');

        $this->actingAs($this->client)->post("/portal/posts/{$post->id}/approve")->assertRedirect();
        $this->assertSame('client_approved', $post->fresh()->status);
    }

    public function test_reminders_are_grouped_and_limited(): void
    {
        Notification::fake();
        foreach (range(1, 3) as $i) {
            Post::create(['brand_id' => $this->brand->id, 'publish_date' => today(), 'type' => 'post', 'title' => "P{$i}", 'status' => 'sent']);
        }
        $this->artisan('plan:reminders --force')->assertSuccessful();
        // 3 paylaşım üçün 1 mail (qruplaşdırılmış)
        Notification::assertSentToTimes($this->client, PlatformNotification::class, 1);
        $this->assertSame(1, Post::first()->reminder_count);
    }

    public function test_task_assignment_and_visibility(): void
    {
        Notification::fake();
        $designer = User::factory()->employee(['brands'])->create();
        $this->actingAs($this->smm)->post('/tasks', [
            'title' => 'Dizayn et', 'status' => 'todo', 'priority' => 'high', 'brand_id' => $this->brand->id,
            'responsible' => [$designer->id], 'watcher' => [$this->admin->id],
        ])->assertRedirect();
        $task = Task::first();
        Notification::assertSentTo($designer, PlatformNotification::class);

        // təyin olunan işçi görür, kənar işçi görmür
        $this->actingAs($designer)->get("/tasks/{$task->id}")->assertOk();
        $this->actingAs($designer)->get('/my-tasks')->assertSee('Dizayn et');
        $stranger = User::factory()->employee([])->create();
        $this->actingAs($stranger)->get("/tasks/{$task->id}")->assertForbidden();
        $this->actingAs($stranger)->get('/tasks/create')->assertForbidden();

        // status dəyişmək və şərh yazmaq
        $this->actingAs($designer)->patch("/tasks/{$task->id}/status", ['status' => 'done'])->assertRedirect();
        $this->assertNotNull($task->fresh()->completed_at);
        $this->actingAs($designer)->post("/tasks/{$task->id}/comments", ['body' => 'Hazırdır'])->assertRedirect();
        $this->assertSame(1, $task->comments()->count());
    }

    public function test_admin_dashboard_and_pages_render(): void
    {
        foreach (['/', '/tasks', '/tasks?view=kanban', '/brands', "/brands/{$this->brand->slug}", "/brands/{$this->brand->slug}/social", "/brands/{$this->brand->slug}/content", '/approvals', '/clients', '/team', '/settings'] as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk();
        }
        foreach (['/portal', '/portal/content', '/portal/posts', '/portal/media', '/portal/settings'] as $url) {
            $this->actingAs($this->client)->get($url)->assertOk();
        }
    }

    public function test_inactive_user_cannot_login(): void
    {
        $u = User::factory()->create(['password' => 'secret123', 'is_active' => false]);
        $this->post('/login', ['email' => $u->email, 'password' => 'secret123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
