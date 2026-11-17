# Creads Plan — Hostinger-də quraşdırma

Bu sənəd platformanı `plan.creads.az` ünvanında canlıya çıxarmaq üçün addım-addım təlimatdır.
Sonradan `plan.19mbt.com`-a keçəndə eyni addımlar təkrarlanır, sadəcə ünvan dəyişir.

## Ümumi sxem

```
GitHub (main budağı)
   │  hər dəyişiklikdən sonra GitHub Actions avtomatik "production" budağını yığır
   ▼
GitHub (production budağı — hazır paketlər daxil)
   │  Hostinger Git avtomatik çəkir
   ▼
Hostinger: public_html/plan  →  https://plan.creads.az
   │  hər dəqiqə cron: bildirişlər, xatırlatmalar, baza yeniləmələri
   ▼
OneDrive (fayllar)  +  noreply@creads.az (mail bildirişləri)
```

---

## 1. Subdomen

hPanel → **Domains → Subdomains** → `plan` yazın, domen `creads.az` → **Create**.
Qovluq avtomatik `public_html/plan` olacaq. İçində Hostinger-in qoyduğu default fayl varsa (**File Manager**-dən) silin, qovluq boş olmalıdır.

## 2. SSL

hPanel → **Security → SSL** → `plan.creads.az` üçün pulsuz SSL-i aktiv edin.

## 3. PHP versiyası

hPanel → **Advanced → PHP Configuration** → **PHP 8.3** seçin.

## 4. MySQL bazası

hPanel → **Databases → Management** → yeni baza yaradın.
Bazanın adı, istifadəçi adı və şifrəni bir yerə yazın (məs: `u123456789_plan`).

## 5. Mail ünvanı

hPanel → **Emails** → `creads.az` → `noreply@creads.az` adlı mail qutusu yaradın, şifrəsini yazın.

## 6. GitHub ilə əlaqə (Git deploy)

1. hPanel → **Advanced → GIT**.
2. Repo private-dırsa, həmin səhifədə Hostinger-in göstərdiyi **SSH key**-i kopyalayın →
   GitHub-da repo → **Settings → Deploy keys → Add deploy key** → yapışdırın (yazma icazəsi lazım deyil).
3. Hostinger-də:
   - **Repository:** `git@github.com:fuadgadirli/plan-creads.git`
   - **Branch:** `production`
   - **Directory:** `plan`
   - **Create** düyməsi.
4. **Auto Deployment** → göstərilən Webhook URL-i kopyalayın →
   GitHub → **Settings → Webhooks → Add webhook** → Payload URL-ə yapışdırın → **Add webhook**.
   Bundan sonra hər yenilik avtomatik sayta düşəcək.

> `production` budağı GitHub Actions tərəfindən avtomatik yaradılır (`main` budağına kod düşən kimi).
> GitHub → **Actions** bölməsində “Production build” yaşıl olmalıdır.

## 7. Ayarlar faylı (.env) və ilk quraşdırma

hPanel → **Advanced → SSH Access** → SSH-ı aktiv edin. Kompüterdə terminal açın (Windows: PowerShell):

```bash
ssh -p 65002 u123456789@SERVER_IP
```

Sonra:

```bash
cd ~/domains/creads.az/public_html/plan
cp .env.example .env
```

`.env` faylını **File Manager**-dən açıb doldurun:

| Sətir | Nə yazılır |
|---|---|
| `APP_URL` | `https://plan.creads.az` |
| `ADMIN_EMAIL`, `ADMIN_PASSWORD` | Sizin giriş məlumatlarınız (ilk rəhbər hesabı) |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | 4-cü addımdakı baza məlumatları |
| `MAIL_PASSWORD` | 5-ci addımdakı mail şifrəsi |
| `MICROSOFT_*` | Hələlik boş qalır (OneDrive sonra qoşulacaq) |

Yadda saxlayıb terminala qayıdın:

```bash
php artisan key:generate --force
```

```bash
php artisan migrate --force --seed
```

Bu, bazanı yaradır və sizin rəhbər hesabınızı açır.

## 8. Cron (avtomatik işlər)

hPanel → **Advanced → Cron Jobs** → **Custom**:

- Command: `/usr/bin/php /home/u123456789/domains/creads.az/public_html/plan/artisan schedule:run`
- Vaxt: hər dəqiqə (`* * * * *`)

Cron bunları edir: mail bildirişlərini göndərir, müştəriyə təsdiq xatırlatmalarını göndərir,
yeni versiya gələndə bazanı avtomatik yeniləyir.

## 9. Yoxlama

1. `https://plan.creads.az` açın → `.env`-dəki ADMIN_EMAIL/ADMIN_PASSWORD ilə daxil olun.
2. **Ayarlar → Özümə test maili göndər** — mail gəlməlidir.
3. **İşçilər və icazələr** → komandanı əlavə edin (şifrəni boş saxlasanız, onlara dəvət maili gedir).
4. **Markalar → Yeni marka**, sonra müştəri hesabı açın.

---

## OneDrive-ı qoşmaq (IT hazır olanda)

1. IT-dən App Registration məlumatlarını alın (Tenant ID, Client ID, Client Secret).
   Redirect URI: `https://plan.creads.az/auth/microsoft/callback`
   İcazələr (Delegated): `User.Read`, `Files.ReadWrite`, `offline_access`.
2. `.env`-ə yazın: `MICROSOFT_TENANT_ID`, `MICROSOFT_CLIENT_ID`, `MICROSOFT_CLIENT_SECRET`.
3. Platformada **Ayarlar → OneDrive-ı qoş** → Microsoft hesabınızla daxil olun.
4. Bundan sonra bütün yeni fayllar `OneDrive / Creads Plan / Marka / Bölmə` qovluqlarına düşəcək.

## plan.19mbt.com-a keçid

1. Hostinger-də yeni subdomen, eyni addımlar (6–8).
2. Bazanı köçürmək: köhnə bazanı **phpMyAdmin → Export**, yenisinə **Import**.
3. `.env`-də `APP_URL`, mail ayarları dəyişir. Bildiriş linkləri avtomatik yeni ünvana keçir.
4. Azure App Registration-da redirect URI artıq hər iki ünvan üçün yazılıb.
