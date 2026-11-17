<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

/**
 * Hər dəqiqə cron ilə işləyir və SSH-sız quraşdırmanı təmin edir:
 * - APP_KEY boşdursa yaradır
 * - yeni versiya gəlibsə bazanı yeniləyir (migrate)
 * - heç bir istifadəçi yoxdursa .env-dəki məlumatlarla rəhbər hesabını açır
 */
class PostDeploy extends Command
{
    protected $signature = 'plan:post-deploy {--force}';

    protected $description = 'Yeni versiya yüklənibsə miqrasiyaları işlədir və keşi təmizləyir';

    public function handle(): int
    {
        if (! config('app.key') && is_writable(base_path('.env'))) {
            Artisan::call('key:generate', ['--force' => true]);
            $this->info('APP_KEY yaradıldı.');
        }

        $markerFile = storage_path('framework/deploy.marker');
        $marker = @md5_file(base_path('composer.lock')).'|'.@md5_file(public_path('build/manifest.json'))
            .'|'.count(glob(database_path('migrations/*.php')));

        if ($this->option('force') || @file_get_contents($markerFile) !== $marker) {
            Artisan::call('migrate', ['--force' => true]);
            $this->line(Artisan::output());
            Artisan::call('view:clear');
            file_put_contents($markerFile, $marker);
            $this->info('Yeni versiya quraşdırıldı.');
        }

        if (Schema::hasTable('users') && ! User::exists() && env('ADMIN_EMAIL') && env('ADMIN_PASSWORD')) {
            Artisan::call('db:seed', ['--force' => true]);
            $this->line(Artisan::output());
        }

        return self::SUCCESS;
    }
}
