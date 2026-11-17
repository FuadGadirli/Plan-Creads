<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

/**
 * Yeni versiya serverə gələndə (Git deploy) bazanı avtomatik yeniləyir.
 * Hər dəqiqə işləyir, amma yalnız kod dəyişəndə nəsə edir.
 */
class PostDeploy extends Command
{
    protected $signature = 'plan:post-deploy {--force}';

    protected $description = 'Yeni versiya yüklənibsə miqrasiyaları işlədir və keşi təmizləyir';

    public function handle(): int
    {
        $marker = @md5_file(base_path('composer.lock')).'|'.@md5_file(public_path('build/manifest.json'))
            .'|'.count(glob(database_path('migrations/*.php')));

        if (! $this->option('force') && Cache::get('deploy.marker') === $marker) {
            return self::SUCCESS;
        }

        Artisan::call('migrate', ['--force' => true]);
        $this->line(Artisan::output());
        Artisan::call('view:clear');
        Cache::forever('deploy.marker', $marker);
        $this->info('Yeni versiya quraşdırıldı.');

        return self::SUCCESS;
    }
}
