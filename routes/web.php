<?php

use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\BrandFileController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContentPlanController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Portal;
use App\Http\Controllers\PostController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

// ---------- Giriş ----------
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/forgot-password', [AuthController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendReset'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/home', fn () => auth()->user()->isClient()
        ? redirect()->route('portal.dashboard')
        : redirect()->route('dashboard'))->name('home');

    // Fayllar (həm agentlik, həm müştəri; icazə controller-də yoxlanır)
    Route::get('/files/{attachment}', [FileController::class, 'show'])->name('files.show');
    Route::get('/files/{attachment}/preview', [FileController::class, 'preview'])->name('files.preview');
    Route::get('/files/{attachment}/thumb', [FileController::class, 'thumb'])->name('files.thumb');

    // Bildirişlər
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/count', [NotificationController::class, 'count'])->name('notifications.count');
    Route::get('/notifications/{id}/open', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
});

// ---------- Agentlik paneli (Rəhbərlik + İşçilər) ----------
Route::middleware(['auth', 'active', 'staff'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/my-tasks', [TaskController::class, 'mine'])->name('tasks.mine');
    Route::get('/search', [SearchController::class, 'index'])->name('search');

    // Yükləmə (hissə-hissə)
    Route::post('/uploads/start', [UploadController::class, 'start'])->name('uploads.start');
    Route::post('/uploads/{id}/chunk', [UploadController::class, 'chunk'])->name('uploads.chunk');
    Route::delete('/files/{attachment}', [FileController::class, 'destroy'])->name('files.destroy');

    // Tapşırıqlar
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/create', [TaskController::class, 'create'])->middleware('perm:tasks.create')->name('tasks.create');
    Route::post('/tasks', [TaskController::class, 'store'])->middleware('perm:tasks.create')->name('tasks.store');
    Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
    Route::get('/tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
    Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::patch('/tasks/{task}/status', [TaskController::class, 'status'])->name('tasks.status');
    Route::post('/tasks/{task}/move', [TaskController::class, 'move'])->name('tasks.move');
    Route::post('/tasks/{task}/comments', [TaskController::class, 'comment'])->name('tasks.comment');
    Route::post('/tasks/{task}/files', [TaskController::class, 'files'])->name('tasks.files');
    Route::post('/tasks/{task}/people', [TaskController::class, 'people'])->name('tasks.people');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

    // Təsdiq gözləyənlər (rəhbər)
    Route::get('/approvals', [ApprovalController::class, 'index'])->middleware('perm:approve')->name('approvals.index');

    // Markalar
    Route::middleware('perm:brands')->group(function () {
        Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
        Route::get('/brands/create', [BrandController::class, 'create'])->middleware('perm:brands.manage')->name('brands.create');
        Route::post('/brands', [BrandController::class, 'store'])->middleware('perm:brands.manage')->name('brands.store');
        Route::get('/brands/{brand}', [BrandController::class, 'show'])->name('brands.show');
        Route::get('/brands/{brand}/edit', [BrandController::class, 'edit'])->middleware('perm:brands.manage')->name('brands.edit');
        Route::put('/brands/{brand}', [BrandController::class, 'update'])->middleware('perm:brands.manage')->name('brands.update');
        Route::middleware('perm:brands.manage')->group(function () {
            Route::post('/brands/{brand}/contacts', [BrandController::class, 'addContact'])->name('brands.contacts.store');
            Route::delete('/brands/{brand}/contacts/{user}', [BrandController::class, 'removeContact'])->name('brands.contacts.destroy');
            Route::post('/brands/{brand}/contacts/{user}/invite', [BrandController::class, 'inviteContact'])->name('brands.contacts.invite');
        });
        Route::get('/brands/{brand}/tasks', [TaskController::class, 'brand'])->name('brands.tasks');

        Route::middleware('perm:brands.files')->group(function () {
            Route::get('/brands/{brand}/files', [BrandFileController::class, 'index'])->name('brands.files');
            Route::post('/brands/{brand}/files', [BrandFileController::class, 'store'])->name('brands.files.store');
            Route::post('/brands/{brand}/folders', [BrandFileController::class, 'folder'])->name('brands.folders.store');
            Route::put('/brands/{brand}/folders/{folder}', [BrandFileController::class, 'updateFolder'])->name('brands.folders.update');
            Route::delete('/brands/{brand}/folders/{folder}', [BrandFileController::class, 'destroyFolder'])->name('brands.folders.destroy');
            Route::patch('/brands/{brand}/files/{attachment}', [BrandFileController::class, 'toggle'])->name('brands.files.toggle');
        });

        Route::middleware('perm:brands.social')->group(function () {
            Route::get('/brands/{brand}/social', [PostController::class, 'index'])->name('brands.social');
            Route::get('/brands/{brand}/social/create', [PostController::class, 'create'])->name('posts.create');
            Route::post('/brands/{brand}/social', [PostController::class, 'store'])->name('posts.store');
            Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');
            Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
            Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
            Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
            Route::post('/posts/{post}/submit', [PostController::class, 'submit'])->name('posts.submit');
            Route::post('/posts/{post}/files', [PostController::class, 'files'])->name('posts.files');
            Route::post('/posts/{post}/comments', [PostController::class, 'comment'])->name('posts.comment');
            Route::post('/posts/{post}/approve', [PostController::class, 'approve'])->middleware('perm:approve')->name('posts.approve');
            Route::post('/posts/{post}/reject', [PostController::class, 'reject'])->middleware('perm:approve')->name('posts.reject');
            Route::post('/posts/{post}/published', [PostController::class, 'published'])->name('posts.published');
            Route::post('/posts/{post}/remind', [PostController::class, 'remind'])->name('posts.remind');
            Route::post('/brands/{brand}/social/remind', [PostController::class, 'remindAll'])->name('brands.social.remind');
        });

        Route::middleware('perm:brands.content')->group(function () {
            Route::get('/brands/{brand}/content', [ContentPlanController::class, 'index'])->name('brands.content');
            Route::get('/brands/{brand}/content/{year}/{month}', [ContentPlanController::class, 'show'])
                ->whereNumber(['year', 'month'])->name('content.show');
            Route::post('/brands/{brand}/content/{year}/{month}', [ContentPlanController::class, 'upload'])
                ->whereNumber(['year', 'month'])->name('content.upload');
            Route::post('/content-versions/{version}/approve', [ContentPlanController::class, 'approve'])->middleware('perm:approve')->name('content.approve');
            Route::post('/content-versions/{version}/reject', [ContentPlanController::class, 'reject'])->middleware('perm:approve')->name('content.reject');
            Route::post('/content-plans/{plan}/comments', [ContentPlanController::class, 'comment'])->name('content.comment');
            Route::post('/content-plans/{plan}/remind', [ContentPlanController::class, 'remind'])->name('content.remind');
        });
    });

    // Əməkdaşlıq (CRM)
    Route::middleware('perm:crm')->group(function () {
        Route::resource('clients', ClientController::class);
        Route::post('/clients/{client}/events', [ClientController::class, 'event'])->name('clients.events.store');
        Route::delete('/clients/{client}/events/{event}', [ClientController::class, 'destroyEvent'])->name('clients.events.destroy');
        Route::post('/clients/{client}/files', [ClientController::class, 'files'])->name('clients.files');
        Route::post('/clients/{client}/brand', [ClientController::class, 'makeBrand'])->middleware('perm:brands.manage')->name('clients.brand');
    });

    // İşçilər və icazələr
    Route::middleware('perm:team')->group(function () {
        Route::get('/team', [TeamController::class, 'index'])->name('team.index');
        Route::get('/team/create', [TeamController::class, 'create'])->name('team.create');
        Route::post('/team', [TeamController::class, 'store'])->name('team.store');
        Route::get('/team/{user}/edit', [TeamController::class, 'edit'])->name('team.edit');
        Route::put('/team/{user}', [TeamController::class, 'update'])->name('team.update');
    });

    // Ayarlar
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings/profile', [SettingsController::class, 'profile'])->name('settings.profile');
    Route::put('/settings/password', [SettingsController::class, 'password'])->name('settings.password');
    Route::middleware('perm:settings')->group(function () {
        Route::put('/settings/agency', [SettingsController::class, 'agency'])->name('settings.agency');
        Route::post('/settings/test-mail', [SettingsController::class, 'testMail'])->name('settings.testMail');
        Route::get('/onedrive/connect', [SettingsController::class, 'oneDriveConnect'])->name('onedrive.connect');
        Route::post('/onedrive/disconnect', [SettingsController::class, 'oneDriveDisconnect'])->name('onedrive.disconnect');
    });
});

// Microsoft geri dönüş ünvanı (App Registration-da bu ünvan yazılır)
Route::get('/auth/microsoft/callback', [SettingsController::class, 'oneDriveCallback'])
    ->middleware(['auth', 'staff', 'perm:settings'])->name('onedrive.callback');

// ---------- Müştəri paneli ----------
Route::prefix('portal')->name('portal.')->middleware(['auth', 'active', 'client'])->group(function () {
    Route::get('/', [Portal\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/brand/{brand}', [Portal\DashboardController::class, 'switchBrand'])->name('brand');
    Route::get('/content', [Portal\ContentController::class, 'index'])->name('content');
    Route::get('/content/{plan}', [Portal\ContentController::class, 'show'])->name('content.show');
    Route::post('/content-versions/{version}/approve', [Portal\ContentController::class, 'approve'])->name('content.approve');
    Route::post('/content-versions/{version}/feedback', [Portal\ContentController::class, 'feedback'])->name('content.feedback');
    Route::get('/posts', [Portal\PostController::class, 'index'])->name('posts');
    Route::get('/posts/{post}', [Portal\PostController::class, 'show'])->name('posts.show');
    Route::post('/posts/{post}/approve', [Portal\PostController::class, 'approve'])->name('posts.approve');
    Route::post('/posts/{post}/feedback', [Portal\PostController::class, 'feedback'])->name('posts.feedback');
    Route::post('/posts-approve-all', [Portal\PostController::class, 'approveAll'])->name('posts.approveAll');
    Route::get('/media', [Portal\MediaController::class, 'index'])->name('media');
    Route::get('/settings', [Portal\SettingsController::class, 'index'])->name('settings');
    Route::put('/settings/profile', [Portal\SettingsController::class, 'profile'])->name('settings.profile');
    Route::put('/settings/password', [Portal\SettingsController::class, 'password'])->name('settings.password');
});
