# Spetsifikatsiya: elektron shartnoma va unga bog'liq hujjatlar

**Manbalar:** `docs/materials/private/shablonlar/` papkasidagi 5 ta shartnoma shabloni (repoga kiritilmaydi) va [yigim-hisoblash.md](yigim-hisoblash.md).
**Holat:** Qoralama. Qo'shimcha kelishuv, talabnoma va dalolatnoma shablonlari hali taqdim etilmagan.

## 1. Shartnoma shablonlari

Shablon uchta belgi bo'yicha tanlanadi:

| To'lov usuli | Investor turi | Vakillik | Shablon fayli |
|---|---|---|---|
| Bir yo'la | Yuridik shaxs | Direktor, Ustav asosida | `договор бир йўлаб/договор юрлицо.docx` |
| Bir yo'la | Jismoniy shaxs | O'zi | `договор бир йўлаб/договор физлицо.docx` |
| Bo'lib-bo'lib | Yuridik shaxs | Direktor, Ustav asosida | `договор булаб булаб/договор юрлицо.docx` |
| Bo'lib-bo'lib | Jismoniy shaxs | O'zi | `договор булаб булаб/договор физлицо.docx` |
| Bo'lib-bo'lib | Jismoniy shaxs | Ishonchnoma bo'yicha vakil | `договор булаб булаб/договор физлицо (ишончнома).docx` |

Bir yo'la to'lanadigan jismoniy shaxs uchun ishonchnomali shablon yo'q. Bu variant kerakmi, aniqlash lozim.

## 2. Shablon maydonlari

Har bir `____` bo'sh joy tizimdagi aniq maydonga bog'lanadi. Qo'lda yoziladigan matn qolmasligi kerak.

| Guruh | Maydonlar |
|---|---|
| Rekvizitlar | shartnoma raqami `ART-{yil}-А-{...}-{...}`, sana, joy |
| Kompaniya | imzolovchi F.I.Sh. va lavozimi, manzil, STIR, bank, MFO. Ma'lumotnomadan olinadi |
| Jamg'arma | STIR, g'aznachilik hisob raqami, shaxsiy hisob raqami. Ma'lumotnomadan olinadi |
| Yuridik investor | nomi, tashkiliy shakli, direktor F.I.Sh., manzil, hisob raqami, STIR, MFO, OKED, bank |
| Jismoniy investor | F.I.Sh., pasport yoki ID karta seriyasi va raqami, berilgan sana, JShShIR, manzil |
| Vakil | ishonchnoma raqami va sanasi, vakil F.I.Sh. |
| Murojaat | investor xatining sanasi va raqami |
| Obyekt | manzil, obyekt turi, eskiz loyiha muallifi, ekspertiza xulosasi raqami, sanasi va tashkiloti |
| Hisob-kitob | Hb, Hyu, Ha, Ht, Hu, zona, Kt, Ko, Kz, Kj, K, BHM, yig'im summasi raqam va so'z bilan |
| To'lov | usul, oldindan to'lov foizi va summasi, qolgan qism, choraklar soni va muddat yillarda |
| Ilova | qolgan 80% uchun reja-jadval: sanalar va summalar |

**Summani so'z bilan yozish.** Shablonda summalar qavs ichida yozuvda ham beriladi, masalan "5 749 128 000,00 (besh milliard ...) so'm". O'zbek tilida raqamni so'zga aylantiruvchi funksiya kerak.

## 3. To'lov shartlari, shablonlar asosida

| Shart | Bir yo'la | Bo'lib-bo'lib |
|---|---|---|
| Oldindan to'lov | 100%, 3 ish kuni ichida | 20%, 3 ish kuni ichida |
| Qolgan qism | — | 80%, har chorakda teng ulushlarda, eng ko'pi N yil |
| Reja-jadval sanalari | — | Shablon namunasida har chorakning 20-sanasi: 20.04, 20.07, 20.10, 20.01 |
| Oxirgi muddat | Foydalanishga ruxsatnoma sanasigacha | Foydalanishga ruxsatnoma sanasigacha |
| Oldindan to'lov o'tkazilmasa | Shartnoma xabarnomasiz haqiqiy emas | Shartnoma xabarnomasiz haqiqiy emas |
| Kafolat | — | Bank kafolati yoki sug'urta polisi, olingandan keyin 2 ish kuni ichida |

Mavjud bazada boshqa nisbatlar ham uchraydi: 40/60, 50/50, 30/70, 68.1/31.9. Shuning uchun oldindan to'lov foizi o'zgaruvchan parametr bo'lishi kerak, standart qiymat 20%.

## 4. Shartnoma holatlari

```
qoralama → hisoblangan → tekshirilgan → imzolashga yuborilgan → imzolangan (amalda)
                                                                     │
          ┌────────────────────────────┬─────────────────────────────┼──────────────────┐
          ▼                            ▼                             ▼                  ▼
  qo'shimcha kelishuv           talabnoma yuborilgan          yakunlangan        bekor qilingan
  (yangi versiya)               → dalolatnoma                 (100% to'langan)   (sabab bilan)
```

- Har bir o'tish kim, qachon va qaysi asos bilan bajarilganini audit jurnaliga yozadi.
- Imzolangan shartnoma matni o'zgartirilmaydi. Har qanday o'zgarish faqat qo'shimcha kelishuv orqali kiritiladi.
- Bazadagi holat qiymatlari bitta enum bo'lishi kerak. Hozir `Bekor qilingan`, `Бекор қилинган`, `Yakunlangan`, `Якунланган` va bo'sh qiymat aralash ishlatiladi.

## 5. Qo'shimcha kelishuv

**Asoslar,** shablon 2.6-bandi bo'yicha:
- Kengash qarori bilan koeffitsiyentlar o'zgarganda;
- shartnoma parametrlari ekspertiza xulosasidan farq qilganda;
- ekspertiza parametrlari foydalanishga qabul qilingan bino parametrlaridan farq qilganda.

**Jarayon:**
1. Investor o'zgarish aniqlangan kundan boshlab 5 ish kuni ichida kelishuv loyihasi bilan murojaat qiladi. Kechikkan har bir kun uchun yig'im summasining 0,01% miqdorida penya, 15% gacha.
2. Tizim yangi parametrlar bo'yicha qayta hisoblaydi. Hajm oshgan bo'lsa, qo'shimcha summa **kelishuv imzolanadigan kundagi BHM** bo'yicha olinadi, 5.1-band.
3. Yangi reja-jadval tuziladi. To'langan summalar saqlanib qoladi.
4. Kelishuv raqamlanadi, chop etiladi va imzolanadi. Shartnomaning yangi versiyasi faol bo'ladi.

**Kerakli material:** qo'shimcha kelishuv shabloni hali yo'q.

## 6. Talabnoma va dalolatnoma

Shablonning 4.2–4.4, 5.3 va 6.1–6.3-bandlari asosida:

| Hodisa | Tizim harakati |
|---|---|
| Reja-jadval to'lovi muddatida tushmadi | Talabnoma, ya'ni ogohlantirish xati, va SMS tayyorlanadi. Javob muddati 3 ish kuni |
| Muddat buzilishi | Kompaniya dalolatnomasi bilan tasdiqlanadi |
| Penya | Kechiktirilgan summaning kuniga 0,01% i, kechiktirilgan summaning 15% idan oshmaydi |
| 10 ish kunidan ortiq kechikish | Kompaniya shartnomani bir tomonlama bekor qilish huquqiga ega |
| 3 oydan ortiq kechikish | Shartnomani bekor qilish uchun sudga murojaat |

Hozir tizimda talabnoma faqat ikki maydon sifatida saqlanadi: raqam va sana. Hujjat yaratilmaydi, holati kuzatilmaydi.

**Penya chegarasi bo'yicha farq.** Kodda umumiy penya shartnoma qiymatining 15% i bilan cheklangan. Shartnoma matnida esa "kechiktirilgan summaning 15% i" deyilgan. Qaysi talqin to'g'riligini huquqshunos tasdiqlashi kerak.

**Kerakli materiallar:** talabnoma, dalolatnoma va bekor qilish xati shablonlari.

## 7. Elektron imzo

- Tizimda E-IMZO allaqachon ishlatiladi, lekin faqat kirish uchun. `EImzoService` PKCS#7 imzosini tekshiradi.
- Hujjatni imzolash uchun quyidagilar kerak:
  - yaratilgan PDF ning xeshini imzolash;
  - imzoni va sertifikat ma'lumotini saqlash;
  - hujjatga QR kod va tekshirish sahifasini qo'shish.
- Ikkala taraf imzolashi kerak: Kompaniya va investor. Investor imzosi qanday olinadi, buni aniqlash kerak: shaxsiy kabinet, my.gov.uz yoki qog'oz nusxa.
