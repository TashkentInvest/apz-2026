# APZ-2026 — loyiha qo'llanmasi

"Toshkent Invest kompaniyasi" AJ uchun arxitektura-rejalashtirish topshirig'i (ART/APZ) qo'shimcha yig'imi shartnomalari va to'lovlari reestri. Laravel 10, PHP 8.4, MySQL, Blade.

## Avval o'qing
- Ish tartibi va agentlar: `docs/METODOLOGIYA.md`
- Ma'lum kamchiliklar: `docs/KAMCHILIKLAR.md`
- Yig'im formulasi: `docs/spec/yigim-hisoblash.md`
- Shartnoma va hujjatlar: `docs/spec/shartnoma-hujjatlari.md`
- Qaror matni: `docs/materials/qaror-VI-104-94-14-0-K24.txt`

## Majburiy qoidalar
- **Spetsifikatsiyasiz kod yo'q.** Hisoblash yoki hujjat qoidasi `docs/spec/` da manba bandi bilan yozilmagan bo'lsa, avval spetsifikatsiya yangilanadi.
- **Pul summalari** BCMath yoki butun tiyinda hisoblanadi va `DECIMAL(20,2)` turida saqlanadi. `float` ishlatilmaydi.
- **BHM, koeffitsiyentlar, zonalar va rekvizitlar** bazadagi versiyalangan ma'lumotnomalarda saqlanadi, kodga yozilmaydi.
- **Imzolangan shartnoma** tahrirlanmaydi. O'zgarish faqat qo'shimcha kelishuv orqali kiritiladi.
- **Ruxsatlar** rol orqali tekshiriladi. Email yoki ismga bog'lanish taqiqlanadi, `canEditContractSchedule()` bunga yomon misol.
- **SQL** MySQL uchun yoziladi. SQLite'da dashboard ishlamaydi. Testlar uchun alohida MySQL test bazasi ishlatiladi.
- **Repo GitHub'da ochiq.** Investor ma'lumotlari, shablonlar, parollar va `.env` commit qilinmaydi. Maxfiy materiallar `docs/materials/private/` papkasida turadi va gitignore qilingan.
- **Interfeys tili** o'zbekcha. Mavjud ekranlar qaysi yozuvda bo'lsa, o'sha yozuvda yoziladi.

## Buyruqlar
Lokal PHP: PATH dagi `php` OSPanel PHP 8.1 ga ishora qiladi, bu loyiha uchun eski. PHP 8.4 ni to'liq yo'l bilan chaqiring:

```bash
"/c/Users/inves/AppData/Local/Microsoft/WinGet/Packages/PHP.PHP.8.4_Microsoft.Winget.Source_8wekyb3d8bbwe/php.exe" artisan test
```

Windows'da `artisan serve` faqat `--no-reload` bilan ishlaydi.

## Ehtiyot bo'ling
- `php artisan db:seed` APZ jadvallarini to'liq tozalab, CSV'dan qayta yuklaydi. Production'da ishlatmang.
- CSV import Excel buzilishlariga sezgir: sana raqam ko'rinishida keladi, telefon va INN `E+11` ko'rinishiga o'tadi. Importdan keyin CSV va bazadagi qatorlar soni hamda summalarni solishtiring.
- Server: `/var/www/art2.toshkentinvest.uz`, `investuz` foydalanuvchisida sudo yo'q. Serverda `artisan` ishlatilgach storage huquqlarini tiklang, aks holda sahifalar 500 xatosi beradi. Batafsil: `.claude/agents/apz-deployer.md`.
