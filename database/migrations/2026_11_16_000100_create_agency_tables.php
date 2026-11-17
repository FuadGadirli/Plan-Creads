<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Əməkdaşlıq (CRM): müştəri şirkətləri
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('industry')->nullable();
            $table->string('website')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_position')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('address')->nullable();
            // lead, negotiation, active, paused, ended
            $table->string('status', 20)->default('lead')->index();
            $table->date('first_contact_at')->nullable();
            $table->date('started_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Müştəri ilə toxunuş nöqtələri: görüş, mail, zəng, qeyd, müqavilə
        Schema::create('client_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20)->default('note');
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('happened_at');
            $table->timestamps();
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color', 9)->default('#6366f1');
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Müştəri istifadəçilərinin görə bildiyi markalar
        Schema::create('brand_user', function (Blueprint $table) {
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['brand_id', 'user_id']);
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            // todo, in_progress, review, done
            $table->string('status', 20)->default('todo')->index();
            $table->string('priority', 20)->default('normal');
            $table->date('due_date')->nullable()->index();
            $table->unsignedInteger('position')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // responsible = məsul, supporter = dəstəkçi, watcher = tag edilən
        Schema::create('task_user', function (Blueprint $table) {
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('watcher');
            $table->primary(['task_id', 'user_id']);
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->morphs('commentable');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            // internal = yalnız agentlik görür; client = müştəri rəyi və ya müştəriyə görünən
            $table->string('visibility', 20)->default('internal');
            $table->timestamps();
        });

        Schema::create('folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('client_visible')->default(true);
            $table->timestamps();
        });

        // Bütün fayllar. Fiziki yer: local (server) və ya onedrive.
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('attachable');
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('disk', 20)->default('local');
            $table->string('path')->nullable();
            $table->string('remote_id')->nullable();
            $table->boolean('client_visible')->default(true);
            $table->timestamps();
        });

        // Aylıq kontent plan (PDF/PPTX)
        Schema::create('content_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->string('status', 30)->default('draft')->index();
            $table->timestamps();
            $table->unique(['brand_id', 'year', 'month']);
        });

        Schema::create('content_plan_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_plan_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->foreignId('attachment_id')->nullable()->constrained()->nullOnDelete();
            $table->text('note')->nullable();
            // pending, internal_changes, sent, client_changes, client_approved
            $table->string('status', 30)->default('pending');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('client_decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('client_decided_at')->nullable();
            $table->timestamps();
        });

        // Sosial media plan: real paylaşımlar
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->date('publish_date')->index();
            $table->string('publish_time', 5)->nullable();
            // post, story, reels, carousel, video
            $table->string('type', 20)->default('post');
            $table->json('platforms')->nullable();
            $table->string('title');
            $table->text('caption')->nullable();
            $table->text('notes')->nullable();
            // draft, pending, internal_changes, sent, client_changes, client_approved, published
            $table->string('status', 30)->default('draft')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('client_decided_at')->nullable();
            $table->foreignId('client_decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_reminder_at')->nullable();
            $table->unsignedInteger('reminder_count')->default(0);
            $table->timestamps();
        });

        // Fəaliyyət lenti
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('subject');
            $table->string('description');
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->longText('value')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['notifications', 'settings', 'activities', 'posts', 'content_plan_versions', 'content_plans',
            'attachments', 'folders', 'comments', 'task_user', 'tasks', 'brand_user', 'brands',
            'client_events', 'clients'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
