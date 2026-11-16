<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Client;
use App\Models\Post;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Sınaq üçün nümunə məlumatlar. İşlətmək: php artisan db:seed --class=DemoSeeder
 * Bütün demo hesabların şifrəsi: demo12345
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $pw = 'demo12345';
        $admin = User::firstOrCreate(['email' => 'rehber@demo.az'], ['name' => 'Fuad Qədirli', 'password' => $pw, 'role' => 'admin', 'position' => 'Rəqəmsal marketinq rəhbəri']);

        $staff = collect([
            ['Aysel Məmmədova', 'smm@demo.az', 'SMM menecer', ['tasks.create', 'brands', 'brands.files', 'brands.social', 'brands.content']],
            ['Murad Həsənov', 'dizayn@demo.az', 'Qrafik dizayner', ['brands', 'brands.files', 'brands.social']],
            ['Leyla Əliyeva', 'video@demo.az', 'Videoqraf', ['brands', 'brands.files']],
            ['Kamran Rzayev', 'foto@demo.az', 'Fotoqraf', ['brands', 'brands.files']],
            ['Nərmin Quliyeva', 'account@demo.az', 'Hesab meneceri', ['tasks.all', 'tasks.create', 'brands', 'brands.files', 'brands.social', 'brands.content', 'crm']],
        ])->map(fn ($s) => User::firstOrCreate(['email' => $s[1]], ['name' => $s[0], 'password' => $pw, 'role' => 'employee', 'position' => $s[2], 'permissions' => $s[3]]));

        $names = [['Baku Coffee', '#7c3aed', 'Qida və içki'], ['Nar Fitness', '#ef4444', 'İdman'], ['Xəzər Mebel', '#0ea5e9', 'Retail'], ['Lalə Klinika', '#10b981', 'Səhiyyə'], ['Dəniz Otel', '#f59e0b', 'Turizm'], ['Ulduz Market', '#ec4899', 'Retail']];
        foreach ($names as $i => [$name, $color, $industry]) {
            $client = Client::firstOrCreate(['name' => $name.' MMC'], ['industry' => $industry, 'status' => 'active', 'contact_person' => 'Marketinq meneceri', 'email' => 'info@'.str($name)->slug().'.az', 'started_at' => now()->subMonths(6 - $i), 'first_contact_at' => now()->subMonths(7 - $i), 'owner_id' => $admin->id]);
            $client->events()->firstOrCreate(['title' => 'İlk görüş'], ['type' => 'meeting', 'happened_at' => now()->subMonths(7 - $i), 'user_id' => $admin->id]);
            $brand = Brand::firstOrCreate(['name' => $name], ['color' => $color, 'client_id' => $client->id]);
            if ($brand->wasRecentlyCreated) {
                foreach (['Brendbuk', 'Kataloq', 'Loqolar', 'Foto', 'Video'] as $f) {
                    $brand->folders()->create(['name' => $f]);
                }
            }
            $cu = User::firstOrCreate(['email' => 'musteri'.($i + 1).'@demo.az'], ['name' => $name.' nümayəndəsi', 'password' => $pw, 'role' => 'client', 'client_id' => $client->id]);
            $cu->brands()->syncWithoutDetaching([$brand->id]);

            if ($brand->tasks()->exists()) {
                continue;
            }
            $statuses = ['todo', 'in_progress', 'review', 'done'];
            foreach (['Aylıq kontent plan hazırla', 'Post dizaynları (12 ədəd)', 'Reels çəkilişi', 'Story seriyası', 'Reklam kampaniyası hesabatı'] as $j => $title) {
                $t = Task::create(['title' => $title, 'brand_id' => $brand->id, 'status' => $statuses[($i + $j) % 4], 'priority' => ['normal', 'high', 'normal', 'urgent', 'low'][$j], 'due_date' => today()->addDays($j * 3 - 4), 'created_by' => $admin->id]);
                $t->users()->attach([$staff[$j % 5]->id => ['role' => 'responsible'], $staff[($j + 1) % 5]->id => ['role' => 'supporter']]);
                if ($t->status === 'done') {
                    $t->update(['completed_at' => now()->subDay()]);
                }
            }
            foreach (range(0, 7) as $k) {
                $date = now()->startOfMonth()->addDays($k * 4 + $i % 3);
                Post::create(['brand_id' => $brand->id, 'publish_date' => $date, 'publish_time' => '19:00', 'type' => ['post', 'story', 'reels', 'carousel'][$k % 4], 'platforms' => ['instagram', 'facebook'],
                    'title' => ['Məhsul təqdimatı', 'Həftəlik endirim', 'Komandamız', 'Müştəri rəyi', 'Bayram təbriki', 'Kampaniya teaser', 'Behind the scenes', 'FAQ'][$k],
                    'caption' => "Nümunə paylaşım mətni.\n#{$brand->slug} #creads",
                    'status' => ['published', 'published', 'client_approved', 'sent', 'sent', 'pending', 'draft', 'client_changes'][$k], 'created_by' => $staff[0]->id, 'sent_at' => now()->subDays(2)]);
            }
        }
        $this->command?->info('Demo məlumatlar yaradıldı. Giriş: rehber@demo.az / demo12345, müştəri: musteri1@demo.az / demo12345');
    }
}
