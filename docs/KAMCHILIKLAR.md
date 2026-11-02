# Loyiha kamchiliklari tahlili

**Sana:** 2026-11-01. **Asos:** kod, ma'lumotlar bazasi, VI-104-94-14-0-K/24 qarori va 5 ta shartnoma shabloni.
**Ustuvorlik:** P0 — xavfsizlik yoki pul hisobidagi xato. P1 — asosiy biznes talab. P2 — sifat va qo'llab-quvvatlash.

## A. Biznes funksiyalari yo'q

| # | Kamchilik | Dalil | Ustuvorlik |
|---|---|---|---|
| A1 | Yig'im tizimda hisoblanmaydi. Shartnoma qiymati qo'lda kiritiladi | `updateContractShow` metodida `contract_value` oddiy raqam maydoni. Kodda BHM yoki koeffitsiyentlar yo'q | P1 |
| A2 | Hisoblash uchun kirish ma'lumotlari saqlanmaydi | Bazada faqat `build_volume` va matnli `coefficient` bor. Hb, Hyu, Ha, Ht, Hu va Kt, Ko, Kz, Kj ustunlari yo'q | P1 |
| A3 | Shartnoma elektron shakllanmaydi | Faqat tayyor faylni yuklash mumkin, ya'ni `uploadContractFile` | P1 |
| A4 | Qo'shimcha kelishuv tushunchasi yo'q | Jadval ham, ekran ham yo'q. O'zgarishlar shartnoma qatoriga to'g'ridan-to'g'ri yoziladi va tarixi yo'qoladi | P1 |
| A5 | Talabnoma faqat raqam va sana maydonlaridan iborat | `demand_letter_number` va `demand_letter_date` ustunlari. Hujjat yaratilmaydi, muddat kuzatilmaydi. 2026-11-02 dagi talabnoma namunasi bo'yicha qoidalar: `docs/spec/shartnoma-hujjatlari.md`, 7-bo'lim | P1 |
| A6 | Dalolatnoma, bekor qilish xati va SMS yo'q | Shablonning 4.2–4.4 va 5.3-bandlarida talab qilinadi | P2 |
| A7 | Reja-jadval avtomatik tuzilmaydi | Jadval qo'lda kiritiladi, ya'ni `updateContractSchedule` | P1 |
| A8 | Hujjatlar elektron imzolanmaydi | `EImzoService` faqat kirishda ishlatiladi | P2 |

## B. Hisob-kitob va ma'lumotlar sifati

| # | Kamchilik | Dalil | Ustuvorlik |
|---|---|---|---|
| B1 | 262 ta shartnomadan 50 tasining qiymati formulaga mos kelmaydi | `docs/spec/yigim-hisoblash.md`, 6-bo'lim | P0 |
| B2 | Penya chegarasi shartnoma matnidan boshqacha talqin qilingan | Kodda shartnoma qiymatining 15% i, matnda kechiktirilgan summaning 15% i. 2026-11-02 dagi shartnoma va talabnoma namunalari ham kechiktirilgan summani yozadi. Namunadagi penya kunlik asos kechiktirilgan summa ekanini tasdiqlaydi, lekin chegara bu holatda ishlamagan. Ochiq: `shartnoma-hujjatlari.md`, Q-2 | P0 |
| B3 | Kj koeffitsiyenti qaror va shablonda qarama-qarshi yozilgan | Yig'im spetsifikatsiyasining 8.1-bandi. 2026-11-02 dagi shartnoma namunasi shablon talqinini takrorlaydi, K = 0,6. Savol yopilmagan | P0 |
| B4 | Holat qiymatlari aralash va model tekshiruvi ishlamaydi | Bazada `Bekor qilingan`, `Yakunlangan` va bo'sh qiymat. Forma `Бекор қилинган` yozadi. `ApzContract::isCompleted()` esa `Yakunlagan` qiymatini qidiradi, bunday qiymat bazada yo'q | P1 |
| B5 | Koeffitsiyent matn sifatida saqlanadi va noto'g'ri qiymatlar bor | `coefficient` ustunida `364`, `-` va bo'sh qiymatlar uchraydi | P1 |
| B6 | To'lov sharti Excel tomonidan buzilgan | `payment_terms` ustunida `окт.90`, ya'ni aslida 10/90 | P2 |
| B7 | Obyekt turi erkin matn | `turar joy`, `turar-joy`, `noturar-joy`, `qo'shimcha qavat` turli yozilishlarda | P1 |
| B8 | Jadval sanalari ikki formatda | Import `d.m.Y` kalitlarini yozadi, forma esa `Y-m-d` kalitlarini | P2 |
| B9 | Summalar `float` bilan hisoblanadi | Hisoblash funksiyalarida `float` va `round` ishlatiladi | P2 |
| B10 | Qo'lda to'ldirilgan hujjat bazadagi ma'lumotdan farq qiladi | 2026-11-02 dagi shartnoma namunasida reja-jadvalda bitta chorak tushib qolgan va oxirgi badal 2 yillik muddatdan chiqqan. Talabnoma namunasidagi asosiy qarz CSV jadvalidan bitta badalga kam. `shartnoma-hujjatlari.md`, Q-7 va Q-8 | P1 |

## C. Xavfsizlik va kirish huquqlari

| # | Kamchilik | Dalil | Ustuvorlik |
|---|---|---|---|
| C1 | Tahrirlash huquqi email va ismga qattiq bog'langan | `canEditContractSchedule()` faqat `superadmin@example.com` va `administrator` ismini qabul qiladi | P0 |
| C2 | Admin login va paroli ochiq README faylida | README dagi `make:admin` misoli. Repo GitHub'da hammaga ochiq | P0 |
| C3 | Investor shaxsiy ma'lumotlari ochiq repoda | `storage/dataset-apz/*.csv` fayllarida INN, PINFL, telefon va ism-familiyalar | P0 |
| C4 | O'zgarishlar jurnali yo'q | Kim, qachon va nimani o'zgartirgani yozilmaydi | P1 |
| C5 | `db:seed` jadvallarni to'liq tozalaydi | `DatabaseSeeder` APZ jadvallarini `truncate` qiladi. Production'da xavfli | P1 |

## D. Arxitektura va texnik qarz

| # | Kamchilik | Dalil | Ustuvorlik |
|---|---|---|---|
| D1 | Bitta nazoratchi faylda 4 497 qator | `TransactionController`. Hisobotlar, hisob-kitob, eksport, fayllar va ruxsatlar aralash | P1 |
| D2 | Avtomatik testlar yo'q | `tests/` ichida faqat namunaviy testlar | P1 |
| D3 | SQL faqat MySQL'da ishlaydi | `DATE_SUB`, `CURDATE`, `FIELD`, `DATE_FORMAT`. Testlar uchun MySQL test bazasi kerak | P2 |
| D4 | Eloquent modellari deyarli ishlatilmaydi | Ko'p joyda `DB::table` va xom SQL | P2 |
| D5 | Ma'lumot importi CSV faylga bog'langan | Import qatorni xatosiz o'tkazib yuborishi mumkin. Yangi CSV kiritishda Excel buzilishlari takrorlanadi | P2 |

## E. Ekspluatatsiya

| # | Kamchilik | Dalil | Ustuvorlik |
|---|---|---|---|
| E1 | `investuz` nomidan ishga tushirilgan `artisan` buyrug'i storage fayllarini veb-server uchun yozib bo'lmaydigan qiladi | 2026-11-01 da `/home` sahifasida 500 xatosi | P1 |
| E2 | SSO sirli kaliti sozlanmagan | `MODULE_SSO_SECRET` bo'sh | P2 |
| E3 | Lokal muhit `composer.lock` talab qiladigan PHP 8.4 bilan mos emas | PATH dagi OSPanel PHP 8.1 | P2 |
