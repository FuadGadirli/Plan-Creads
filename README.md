# Creads Plan

Reklam agentliyi üçün daxili iş və müştəri əməkdaşlıq platforması.

**3 istifadəçi növü:**
- **Rəhbərlik** — hər şeyi görür, tapşırıq verir, kontent plan və paylaşımları təsdiqləyib müştəriyə göndərir.
- **İşçilər** — rəhbərin verdiyi icazələrə uyğun bölmələri görür.
- **Müştəri** — yalnız təsdiqlənmiş kontent planları, paylaşım təqvimini və media portfoliosunu görür. Təsdiqləyir və ya rəy bildirir.

**Əsas imkanlar:**
- Tapşırıqlar: siyahı və kanban, məsul / dəstəkçi / tag, şərh, fayl, deadline, “Mənim işlərim”
- Markalar: tapşırıqlar, fayllar (qovluqlar), sosial media plan (təqvim + siyahı), kontent plan (12 aylıq)
- Təsdiq zənciri: SMM → rəhbər → müştəri → (rəy → yeni versiya → …), versiyalar yan-yana
- Avtomatik mail xatırlatmaları (iş saatlarında, qruplaşdırılmış)
- Əməkdaşlıq (CRM): müştəri kartı, toxunuş tarixçəsi, sənədlər
- Fayllar OneDrive-da saxlanılır (Microsoft Graph), platformada yükləmədən baxılır
- Qlobal axtarış

**Texnologiya:** Laravel 13 (PHP 8.3), MySQL, Blade + Tailwind CSS + Alpine.js.

## Lokal işə salmaq

```bash
composer install && npm install && npm run build
cp .env.example .env    # DB_CONNECTION=sqlite, MAIL_MAILER=log, APP_ENV=local edin
php artisan key:generate
php artisan migrate --seed --seeder=DemoSeeder
php artisan serve
```

Demo giriş: `rehber@demo.az` / `demo12345`, müştəri: `musteri1@demo.az` / `demo12345`.

Testlər: `php artisan test`

## Canlıya çıxarmaq

Bax: [QURASDIRMA.md](QURASDIRMA.md)
