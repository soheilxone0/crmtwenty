<div dir="rtl">

# crmtwenty — نسخه فارسی Twenty CRM

این مخزن، کد کامل [Twenty CRM](https://github.com/twentyhq/twenty) (نسخه `v2.58.0`) به همراه فارسی‌سازی و شخصی‌سازی است و طوری تنظیم شده که **با یک `git push` روی سرور خودتان مستقر شود**.

## چه چیزهایی تغییر کرده است

| بخش | تغییر |
|---|---|
| زبان پیش‌فرض | رابط کاربری به‌صورت پیش‌فرض **فارسی و راست‌به‌چپ** باز می‌شود (حتی اگر مرورگر انگلیسی باشد). با `?locale=en` یا از تنظیمات پروفایل می‌توان زبان را عوض کرد. |
| ترجمه | ترجمه‌های فارسی سمت سرور تکمیل شد (پیام‌های خطای باقی‌مانده). |
| تقویم | تاریخ‌ها در جدول‌ها، فیلدها، تایم‌لاین و فیلترها به **تقویم شمسی** و با اعداد فارسی نمایش داده می‌شوند (مثلاً «۲۳ آبان ۱۴۰۵ ساعت ۱۴:۰۰»). انتخابگر تاریخ، ورودی تایپی و نمای تقویم هم شمسی‌اند (هفته از شنبه). در پایگاه داده و API همچنان تاریخ استاندارد ISO ذخیره می‌شود. |
| ایمیل شرکت | صفحه جدید **پنل مدیریت ← ایمیل شرکت** برای تنظیم SMTP، ارسال ایمیل آزمایشی و روشن کردن تأیید ایمیل هنگام ثبت‌نام. |
| فونت | فونت **وزیرمتن** (Vazirmatn) به‌صورت محلی اضافه شد؛ متن فارسی با وزیرمتن و متن لاتین با Inter نمایش داده می‌شود. |
| استقرار | فایل‌های `deploy/` (Docker Compose + HTTPS خودکار با Caddy) و GitHub Actions برای ساخت و استقرار خودکار. |
| CI | ورک‌فلوهای CI پروژه اصلی (که به سرویس‌های داخلی Twenty وابسته‌اند) حذف شد؛ `Build & Deploy` (ساخت و استقرار) و `Checks` (بررسی TypeScript و تست‌ها روی Pull Request) جایگزین شدند. |

فایل‌های تغییر یافته اصلی:

- `packages/twenty-front/src/utils/i18n/DefaultAppLocale.ts` و `initialI18nActivate.ts` — زبان پیش‌فرض
- `packages/twenty-front/src/modules/localization/utils/formatDateWithPersianCalendar.ts` — نمایش تاریخ شمسی
- `packages/twenty-front/src/modules/localization/utils/persian-calendar/` — منطق تقویم شمسی، انتخابگر و ورودی تاریخ
- `packages/twenty-front/src/modules/settings/admin-panel/company-email/` — صفحه «ایمیل شرکت» در پنل مدیریت
- `packages/twenty-server/src/engine/core-modules/admin-panel/admin-panel-company-email.resolver.ts` — ارسال ایمیل آزمایشی
- `packages/twenty-front/src/index.css` و `public/fonts/vazirmatn/` — فونت
- `packages/twenty-server/src/engine/core-modules/i18n/locales/fa-IR.po` — ترجمه
- `deploy/` و `.github/workflows/deploy.yml` — استقرار

---

## تقویم شمسی (جلالی) در همه‌جا

وقتی زبان رابط کاربری فارسی است:

- **انتخابگر تاریخ** (فیلدهای تاریخ، فیلترها، فرم‌های گردش‌کار و …) ماه‌های شمسی را نشان می‌دهد: فروردین تا اسفند، سال‌های کبیسه درست (مثلاً اسفند ۱۴۰۳ سی روز دارد)، روزها با اعداد فارسی، و هفته از **شنبه** شروع می‌شود.
- انتخاب ماه/سال در بالای تقویم شمسی است (مثلاً «آبان» و «۱۴۰۵»).
- **تایپ تاریخ** هم شمسی است: `1405/08/23` یا `1405/08/23 14:30` (ارقام فارسی هم پذیرفته می‌شوند). تاریخ نامعتبر مثل `1404/12/30` پذیرفته نمی‌شود.
- **نمای تقویم رکوردها** (Calendar view) ماه شمسی را نشان می‌دهد؛ جمعه به‌عنوان تعطیل هفته رنگ متفاوت دارد.
- نمایش تاریخ‌ها در جدول، کارت‌ها، تایم‌لاین، رویدادهای تقویم و برچسب فیلترها شمسی است.
- در دیتابیس و API تاریخ‌ها همچنان استاندارد ISO (میلادی) ذخیره می‌شوند، پس گزارش‌ها، فیلترها و یکپارچه‌سازی‌ها درست کار می‌کنند.

فایل‌های اصلی: `packages/twenty-front/src/modules/localization/utils/persian-calendar/` و
`packages/twenty-front/src/modules/ui/input/components/internal/date/components/Persian*.tsx`.

## ورود با ایمیل، فعال‌سازی حساب و فراموشی رمز

صفحات ورود/ثبت‌نام با ایمیل و رمز عبور، «فراموشی رمز عبور» و تأیید ایمیل در Twenty وجود دارند و فارسی شده‌اند.
ایمیل‌ها (فعال‌سازی، بازیابی رمز، دعوت) به زبان کاربر — یعنی فارسی — ارسال می‌شوند.

### تنظیم ایمیل شرکت از پنل مدیریت (بدون دست زدن به سرور)

1. **اولین کسی که ثبت‌نام می‌کند مدیر کل سرور می‌شود.** بلافاصله بعد از راه‌اندازی، خودتان ثبت‌نام کنید.
2. بروید به **تنظیمات ← پنل مدیریت ← ایمیل شرکت**.
3. «ارسال ایمیل از طریق SMTP» را روشن کنید و اطلاعات را وارد کنید:

   | فیلد | نمونه |
   |---|---|
   | ایمیل فرستنده | `info@company.ir` |
   | نام فرستنده | `شرکت من` |
   | سرور SMTP | `mail.company.ir` / `smtp.gmail.com` |
   | پورت | `587` (STARTTLS) یا `465` (SSL) |
   | نام کاربری / رمز | حساب ایمیل شرکت (برای Gmail: App Password) |

4. **ذخیره تنظیمات** را بزنید، سپس با **ارسال ایمیل آزمایشی** مطمئن شوید ایمیل می‌رسد (خطای SMTP همان‌جا نمایش داده می‌شود).
5. حالا «**الزام تأیید ایمیل هنگام ثبت‌نام**» را روشن کنید و دوباره ذخیره کنید. از این به بعد کاربر جدید تا روی لینک فعال‌سازی کلیک نکند وارد نمی‌شود.

> این مقادیر در دیتابیس ذخیره می‌شوند و بر فایل `.env` سرور اولویت دارند و بدون ری‌استارت اعمال می‌شوند.
> رمز SMTP بعد از ذخیره نمایش داده نمی‌شود؛ برای تغییرش فقط مقدار جدید را وارد کنید.
> تأیید ایمیل را **قبل از** تست موفق SMTP روشن نکنید، وگرنه کاربران جدید نمی‌توانند حساب خود را فعال کنند.

## استقرار روی سرور (فقط یک بار تنظیم، بعد فقط push)

### پیش‌نیاز سرور
- لینوکس (Ubuntu 22.04/24.04 پیشنهاد می‌شود)، حداقل **۲ هسته و ۴ گیگ رم** و ۲۰ گیگ فضا
- یک دامنه یا ساب‌دامنه (مثلاً `crm.example.com`) که رکورد A آن به IP سرور اشاره کند

### قدم ۱ — آماده کردن سرور (یک بار)
روی سرور اجرا کنید:

</div>

```bash
sudo mkdir -p /opt/crmtwenty && sudo chown $USER /opt/crmtwenty
cd /opt/crmtwenty
curl -fsSLO https://raw.githubusercontent.com/soheilxone0/crmtwenty/main/deploy/setup-server.sh
curl -fsSLO https://raw.githubusercontent.com/soheilxone0/crmtwenty/main/deploy/.env.example
bash setup-server.sh        # نصب Docker و ساخت .env با رمزهای تصادفی
nano .env                   # SERVER_URL و DOMAIN را تنظیم کنید
```

<div dir="rtl">

در فایل `.env`:
- `SERVER_URL=https://crm.example.com` (آدرس دقیق سایت)
- اگر روی سرور **nginx یا وب‌سرور دیگری ندارید**: `DOMAIN=crm.example.com` و خط `COMPOSE_PROFILES=https` را از کامنت دربیاورید تا HTTPS خودکار (Let's Encrypt) فعال شود.
- اگر **nginx دارید**: برنامه روی `127.0.0.1:3000` بالا می‌آید؛ کافی است در nginx یک `proxy_pass http://127.0.0.1:3000;` بگذارید (نمونه پایین صفحه).

### قدم ۲ — تنظیم Secrets در گیت‌هاب (یک بار)
در گیت‌هاب: **Settings → Secrets and variables → Actions → New repository secret**

| نام | مقدار |
|---|---|
| `SSH_HOST` | IP یا دامنه سرور |
| `SSH_USER` | نام کاربری SSH (باید اجازه اجرای docker داشته باشد) |
| `SSH_PRIVATE_KEY` | کلید خصوصی SSH (کلید عمومی‌اش در `~/.ssh/authorized_keys` سرور باشد) |
| `SSH_PORT` | (اختیاری) پورت SSH، پیش‌فرض 22 |
| `DEPLOY_PATH` | (اختیاری) مسیر روی سرور، پیش‌فرض `/opt/crmtwenty` |

ساخت کلید مخصوص استقرار:

</div>

```bash
ssh-keygen -t ed25519 -f crm_deploy -N ""
ssh-copy-id -i crm_deploy.pub user@your-server     # کلید عمومی روی سرور
cat crm_deploy                                      # این را در SSH_PRIVATE_KEY بگذارید
```

<div dir="rtl">

### قدم ۳ — push کنید ✅
از این به بعد هر `git push` روی شاخه `main`:
1. ایمیج داکر را در GitHub Actions می‌سازد (حدود ۲۰ تا ۴۰ دقیقه بار اول، بعد سریع‌تر به‌خاطر کش)
2. آن را در `ghcr.io/soheilxone0/crmtwenty` منتشر می‌کند
3. با SSH به سرور وصل می‌شود و `docker compose pull && docker compose up -d` را اجرا می‌کند

پیشرفت کار را در تب **Actions** مخزن ببینید. بعد از اتمام، آدرس سایت را باز کنید و اولین حساب (مدیر) را بسازید.

> ساخت ایمیج روی سرور شما انجام نمی‌شود (بیلد فرانت‌اند به ~۸ گیگ رم نیاز دارد)؛ روی سرورهای گیت‌هاب ساخته و فقط دانلود می‌شود.

### نمونه تنظیم nginx (اگر از قبل nginx دارید)

</div>

```nginx
server {
    server_name crm.example.com;
    client_max_body_size 100M;
    location / {
        proxy_pass http://127.0.0.1:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

<div dir="rtl">

### دستورات مفید روی سرور

</div>

```bash
cd /opt/crmtwenty
docker compose ps                    # وضعیت سرویس‌ها
docker compose logs -f server        # لاگ‌ها
docker compose exec db pg_dump -U postgres default > backup.sql   # پشتیبان‌گیری
```

<div dir="rtl">

---

## اجرای محلی برای توسعه

راهنمای کامل توسعه در [`README.twenty.md`](README.twenty.md) و مستندات Twenty است. خلاصه: Node.js نسخه ۲۶ (`.nvmrc`)، Yarn 4، PostgreSQL و Redis لازم است:

</div>

```bash
corepack enable && yarn install
npx nx start twenty-server   # و در ترمینال دیگر:
npx nx start twenty-front
```

<div dir="rtl">

## به‌روزرسانی از نسخه اصلی Twenty

</div>

```bash
git remote add upstream https://github.com/twentyhq/twenty.git
git fetch upstream --tags --depth 1
# فایل‌های تغییر یافته بالا را هنگام ادغام بررسی کنید
```

<div dir="rtl">

## محدودیت‌های فعلی
- قالب‌بندی دلخواه تاریخ (Custom format) در تنظیمات فیلد همچنان میلادی است.
- نمودارها (Charts) برچسب محور زمان را با فرمت پیش‌فرض کتابخانه نمایش می‌دهند.

## مجوز
کد Twenty عمدتاً تحت **AGPLv3** است (به [LICENSE](LICENSE) مراجعه کنید). استفاده داخلی آزاد است؛ اگر نسخه تغییر یافته را به‌صورت سرویس آنلاین در اختیار دیگران قرار دهید، باید کد منبع آن را هم منتشر کنید (که با عمومی بودن همین مخزن رعایت می‌شود). نام و لوگوی «Twenty» علامت تجاری صاحبانش است ([TRADEMARK](.github/TRADEMARK.md)).

</div>
