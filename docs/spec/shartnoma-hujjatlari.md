# Spetsifikatsiya: elektron shartnoma va unga bog'liq hujjatlar

**Manbalar.** Hammasi repoga kiritilmaydi:
- `docs/materials/private/shablonlar/договор янги/` dagi 5 ta bo'sh shartnoma shabloni. Bu hujjatda "joriy shablonlar" deb ataladi.
- `docs/materials/private/shablonlar/2026-11-02/dog-shablon.doc`. Bu to'ldirilgan shartnoma namunasi, bu hujjatda **S-2026** deb ataladi.
- `docs/materials/private/shablonlar/2026-11-02/talabnoma.doc`. Bu talabnoma namunasi, bu hujjatda **T-1** deb ataladi.
- `storage/dataset-apz/grafik_apz.csv` va `fakt_apz.csv`, ya'ni reja-jadval va to'lovlar fakti.
- [yigim-hisoblash.md](yigim-hisoblash.md).

**Maxfiylik.** Bu faylda investor nomi, F.I.Sh., STIR, JShShIR, telefon, hisob raqami va namunadagi shartnoma raqamlari yozilmaydi. Kompaniya va Jamg'arma rekvizitlari ochiq bo'lsa ham bu yerga va kodga yozilmaydi. Ular versiyalangan ma'lumotnomada saqlanadi. Aniq qiymatlar private papkadagi namunalarda bor.

**Holat:** Qoralama. Talabnomaning bitta namunasi bor: yuridik shaxs, bo'lib-bo'lib to'lash. Qo'shimcha kelishuv, dalolatnoma, bekor qilish xati va SMS matni shablonlari hali taqdim etilmagan. Ochiq savollar 13-bo'limda.

**Qoida identifikatorlari.** `SH` shablon va maydonlar, `RQ` raqamlash, `RJ` reja-jadval, `TN` talabnoma, `PN` penya.

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

### 1.1. Shablon versiyalari va band xaritasi

Shablon matni vaqt o'tishi bilan o'zgaradi va band raqamlari ham siljiydi. Dalil: T-1 talabnomasi 2024-yilgi shartnomaga tayanadi va qolgan 80% ni reja-jadval asosida to'lash shartini **"2.4-bandning uchinchi xatboshisi"** deb keltiradi. Joriy bo'lib-bo'lib shablonlarida bu shart **2.5-bandda**. 2.4-band esa uchinchi shaxslar orqali to'lov haqida.

- **SH-01.** Har bir shartnoma qaysi shablon va qaysi versiya bilan tuzilganini saqlaydi: `template_code` va `template_version`. Imzolangandan keyin bu qiymat o'zgarmaydi.
- **SH-02.** Har bir shablon versiyasi uchun band xaritasi ma'lumotnomada saqlanadi. Talabnoma, dalolatnoma va boshqa hujjatlar band raqamini shu xaritadan oladi, kodga yozmaydi. Manba: T-1 namunasi va joriy shablonlar.

Joriy shablonlar uchun band xaritasi:

| Mazmun | Bir yo'la, yuridik va jismoniy | Bo'lib-bo'lib, yuridik | Bo'lib-bo'lib, jismoniy (ikkala shablon) |
|---|---|---|---|
| Yig'im miqdori | 2.1 | 2.1 | 2.1 |
| Jamg'arma rekvizitlari | 2.2 | 2.2 | 2.2 |
| To'lov maqsadi | 2.3 | 2.3 | 2.3 |
| To'lov muddati | 2.5: 100%, 3 ish kuni | 2.5, 1-tire: 20%, 3 ish kuni. 2.5, 2-tire va keyingi xatboshi: 80%, reja-jadval | 2.5, xuddi shunday |
| Talabnoma yuborish | 4.2 | 4.2 | 4.2 |
| Talabnomani bajarish muddati | 4.3 (birinchi) | 4.3 (birinchi) | 4.3 |
| Dalolatnoma | 4.3 (ikkinchi, raqam takrorlangan) | 4.3 (ikkinchi, raqam takrorlangan) | 4.4 |
| Bir tomonlama bekor qilish | 5.3.1 | 5.3.1 | 5.3.1 |
| Penya | 6.1 | 6.1 | 6.1 |
| Sudga murojaat | 6.3 | 6.3 | 6.3 |
| Aloqa manzili va rekvizitlar | XII-bo'lim | XII-bo'lim | XII-bo'lim |

2024- va 2025-yillardagi shablon versiyalarining matni yo'q. Ularning band xaritasini tuzish uchun matnlar kerak (Q-12).

### 1.2. S-2026 namunasini joriy shablonlar bilan solishtirish

S-2026 bo'lib-bo'lib to'lanadigan yuridik shaxs shablonining to'ldirilgan nusxasi. So'zlar bo'yicha o'xshashlik: shu shablon bilan 89%, qolgan to'rttasi bilan 86–88%. To'ldirilgan maydonlardan tashqari quyidagi farqlar bor:

| # | Joy | Joriy shablon | S-2026 | Tizim uchun xulosa |
|---|---|---|---|---|
| 1 | 2.1, 1-xatboshi | "Obyektning umumiy hajmi" | "Obyekt yig'imining umumiy hajmi" | Bu joyga yalpi hajm emas, yig'im olinadigan hajm yoziladi: `(Hb + Hyu) − (Ha + Ht + Hu)` |
| 2 | 2.1, 2-xatboshi | Bitta hujjat: yig'ma ekspert xulosasi. Shablonda oy "dekabr" deb qotirib yozilgan | To'rtta hujjat: ART, Arxitektura va shaharsozlik kengashi ishchi organining xulosasi, yig'ma ekspert xulosasi, bosh loyihachi | Maydonlar kengayadi, 2-bo'limga qarang. Qotirilgan oy ishlatilmaydi, sana to'liq maydondan olinadi |
| 3 | 9.3 | "sud tartibida" | Sud nomi aniq ko'rsatilgan: Toshkent tumanlararo iqtisodiy sudi | Huquqiy matn o'zgargan. Barcha shablonlarga tegishlimi, noma'lum (Q-15) |
| 4 | XII, Kompaniya bank STIR | 9 ta raqam | Qiymat ichida kirill harfi bor | Qo'lda terish xatosi. Qiymat ma'lumotnomadan olinadi va STIR 9 raqam formatida tekshiriladi |
| 5 | Ilova sarlavhasidagi sana | Oy "yanvar" deb qotirilgan | Oy bo'sh qoldirilgan | Sana shartnoma sanasidan to'liq qo'yiladi |
| 6 | Ilova, reja-jadval | 4 qator, har chorakning 20-sanasi, tartib raqami ustuni yo'q | 8 qator, har chorakning 29-sanasi, "T/r" ustuni bor | Qatorlar soni muddatga bog'liq. Sana qoidasi aniq emas (Q-6) |
| 7 | Sahifalar tartibi | "Quruvchi va qurilish obyekti bo'yicha ma'lumotlar" varag'i ilovadan oldin | Ilova oldin, varaq keyin | Tartibni tasdiqlash kerak. Matnga ta'siri yo'q |
| 8 | Summa formati | `,00` | Bir joyda `,0`, boshqa joylarda `,00` | Tizim har doim 2 xona kasr chiqaradi |
| 9 | 6.1 penya, 2.1 dagi Kj izohi, barcha muddatlar | — | O'zgarmagan | KAMCHILIKLAR B2 va B3 bu namuna bilan yopilmaydi |

Joriy shablonlardagi muddatlar S-2026 da ham bir xil: 2.5 bo'yicha 3 ish kuni, 2.7 bo'yicha 5 ish kuni, 3.4.3 bo'yicha 2 ish kuni, 4.3 bo'yicha 3 ish kuni, 5.3.1 bo'yicha 10 ish kuni, 5.3.2 bo'yicha 6 oy, 6.3 bo'yicha 3 oy, 8.2 bo'yicha 3 ish kuni, 8.3 va 9.3 bo'yicha 30 ish kuni.

**Shablonlardagi texnik xatolar.** Bular joriy shablonlarda ham, S-2026 da ham bor. Huquqiy matn bo'lgani uchun tizim ularni o'zgartirmaydi, faqat huquqshunosga ma'lum qilinadi:
- 3.2.3 raqami ikki marta ishlatilgan, barcha 5 shablonda.
- 4.3 raqami ikki marta ishlatilgan: bir yo'la shablonlarda va bo'lib-bo'lib yuridik shaxs shablonida.
- 3.4.3-band bo'lib-bo'lib to'lashni "2.2-bandga muvofiq" deb keltiradi. Aslida 2.2 rekvizitlar, bo'lib-bo'lib to'lash esa 2.5-bandda.

### 1.3. S-2026 ni ma'lumotlar bilan tekshirish

S-2026 `grafik_apz.csv` dagi bitta shartnomaga mos keladi: 2026-yil 30-iyul, Chilonzor tumani, 3-zona. Shartnoma raqami private namunada.

| Tekshiruv | S-2026 | CSV | Natija |
|---|---|---|---|
| Yig'im summasi | 7 931 278 512,00 | 7 931 278 512,00 | mos |
| Hajm, K, zona | 32 084,46; 0,6; 3 | 32 084,46; 0,6; 3 | mos |
| To'lov sharti | 20/80, 2 yil | 20/80, 8 badal | mos |
| 20% summasi | 1 586 255 702,40 | 1586255702 | CSV da tiyin yo'qolgan. Bu Excel buzilishi, KAMCHILIKLAR B6 ga o'xshash |
| Reja-jadval oylari | 10.2026, 01.2027, 04.2027, **10.2027**, 01.2028, 04.2028, 07.2028, **10.2028** | 10.2026, 01.2027, 04.2027, **07.2027**, 10.2027, 01.2028, 04.2028, 07.2028 | **mos emas** |

S-2026 ilovasida 2027-yil iyul choragi tushib qolgan va oxirgi badal 2028-yil oktyabrga qo'yilgan. Shartnoma 30.07.2026 da tuzilgan bo'lsa, 2.5-banddagi "eng ko'pi bilan 2 yil" muddati 30.07.2028 da tugaydi. Oxirgi badal undan keyin keladi. Imzolangan hujjatda qaysi jadval borligi noma'lum (Q-7).

S-2026 dagi "Quruvchi va qurilish obyekti bo'yicha ma'lumotlar" varag'ida ham xatolar bor:
- "Obyekt turi" qatoriga qurilish turi yozilgan.
- Investor bankining nomi XII-bo'limda boshqa, ma'lumot varag'ida boshqa. MFO esa ikkala joyda bir xil.

- **SH-03.** Har bir ma'lumot tizimga bir marta kiritiladi va hujjatning barcha joylariga shu maydondan qo'yiladi. Masalan, obyekt manzili preambula, 2.3, 2.10 va ma'lumot varag'iga, investor banki esa XII-bo'lim va ma'lumot varag'iga. Manba: yuqoridagi S-2026 nomuvofiqliklari.

## 2. Shablon maydonlari

Har bir `____` bo'sh joy tizimdagi aniq maydonga bog'lanadi. Qo'lda yoziladigan matn qolmasligi kerak.

| Guruh | Maydonlar | Manba band | Qayerdan olinadi |
|---|---|---|---|
| Rekvizitlar | Shartnoma raqami, sana, joy | Sarlavha | 3-bo'limga qarang |
| Kompaniya | Imzolovchi F.I.Sh. va lavozimi, manzil, telefon, STIR, bank, MFO, bank STIR | Preambula, XII | `company_requisites` va `signatories` ma'lumotnomalari, shartnoma sanasidagi versiya |
| Jamg'arma | STIR, bank, g'aznachilik hisob raqami, bank STIR, MFO, shaxsiy hisob raqami | 2.2 | `fund_requisites` ma'lumotnomasi. Talabnoma ham shu manbadan oladi |
| Yuridik investor | Nomi, tashkiliy shakli, direktor F.I.Sh., manzil, hisob raqami, STIR, MFO, OKED, bank, telefon | Preambula, XII | Investor kartochkasi |
| Jismoniy investor | F.I.Sh., pasport yoki ID karta seriyasi va raqami, berilgan sana, JShShIR, manzil | Preambula, 2.3, XII | Investor kartochkasi |
| Vakil | Ishonchnoma raqami va sanasi, vakil F.I.Sh. | Preambula | Shartnoma kartochkasi |
| Murojaat | Investor xatining sanasi va raqami | Preambula, 2.1 | Shartnoma kartochkasi |
| Obyekt | Obyekt tavsifi, ya'ni nomi va qavatliligi, manzil, tuman, obyekt turi | Preambula, 2.3, 2.9, 2.10 | Obyekt kartochkasi |
| Ruxsat hujjatlari | ART: bergan organ, sana, raqam. Kengash ishchi organining xulosasi: sana, raqam. Yig'ma ekspert xulosasi: tashkilot, sana, raqam. Bosh loyihachi: nomi va tashkiliy shakli | 2.1, 2-xatboshi (S-2026) | Obyekt kartochkasi. CSV da `APZ nomer`, `Kengash xulosasi`, `Yigʼma ekspertiza xulosasi` ustunlari bor |
| Hisob-kitob | Hb, Hyu, Ha, Ht, Hu, yig'im hajmi, zona, Kt, Ko, Kz, Kj, K, BHM, yig'im summasi raqam va so'z bilan | 2.1, ma'lumot varag'i | Hisob snapshot'i, [yigim-hisoblash.md](yigim-hisoblash.md) |
| To'lov | Usul, oldindan to'lov foizi va summasi, qolgan qism, muddat yillarda | 2.5 | Shartnoma kartochkasi |
| Ilova | Reja-jadval: tartib raqami, sana, summa, jami | Ilova | 4-bo'lim |
| Ma'lumot varag'i | Rahbar F.I.Sh., yuridik manzil, kompaniya nomi, IFUT, lavozim, bank kodi, bank nomi, STIR, ariza raqami, loyiha nomi, obyekt turi, obyekt manzili, Hb, Hyu, Ha, Ht, Hu, qurilish turi, K, zona, yig'im hajmi, yig'im summasi, oldindan to'lov foizi | "Quruvchi va qurilish obyekti bo'yicha ma'lumotlar" | Yuqoridagi maydonlar. Yangi maydon kiritilmaydi |

- **SH-04.** Kompaniya va Jamg'arma rekvizitlari, imzolovchi va uning lavozimi kodga yozilmaydi. Ular amal qilish sanasi bilan versiyalangan ma'lumotnomada saqlanadi. Hujjat yaratilganda uning sanasidagi versiya olinadi va hujjat snapshot'iga yoziladi. Manba: CLAUDE.md; S-2026 dagi bank STIR xatosi va T-1 shablonidagi qotirilgan yil buni talab qiladi.
- **SH-05.** Summa har doim ikki xona kasr bilan chiqariladi, minglar bo'sh joy bilan ajratiladi, kasr vergul bilan yoziladi: `7 931 278 512,00`. Manba: joriy shablonlar. S-2026 dagi `,0` xato hisoblanadi.

**Summa va sonlarni so'z bilan yozish.** Ikki xil uslub uchraydi:

| Hujjat | Yozuv | Kasr qismi | Namuna |
|---|---|---|---|
| Shartnoma, S-2026, 2.1 va 2.5 | Lotin | "butun yuzdan" yoki "butun o'ndan" | `1 586 255 702,40 (... yetti yuz ikki butun yuzdan qirq) so'm`, hajm `32 084,46 (... butun yuzdan qirq olti)`, koeffitsiyent `0,6 (nol butun o'ndan olti)` |
| Talabnoma, T-1 | Kirill | "so'm ... tiyin" | `1 776 107 812,50 (... o'n ikki so'm ellik tiyin) so'm`. Bu yerda namuna lotinga o'girib berilgan |

- **SH-06.** So'zga aylantirish funksiyasi uch parametr oladi: yozuv (lotin yoki kirill), kasr uslubi (`butun` yoki `tiyin`) va kasr xonalari soni. Kasr qism nol bo'lsa, so'z bilan yozilmaydi. Masalan, T-1 da `2 841 772 500,00 (... besh yuz) so'm`. Manba: S-2026 2.1 va 2.5, T-1.
- O' va g' harflaridagi tutuq belgisi S-2026 da aralash ishlatilgan: `‘` (U+2018) va `ʻ` (U+02BB). Tizim bitta belgini ishlatadi, uni Q-15 bilan birga tasdiqlash kerak. Etalon testlar belgini normallashtirib solishtiradi.

## 3. Shartnomani raqamlash

Shablondagi format: `ART-{yil}-А-{___}-{______}`. `grafik_apz.csv` dagi 308 ta shartnoma raqami shu formatda tahlil qilindi.

| Qism | Ma'nosi | Dalil |
|---|---|---|
| `ART` | Doimiy prefiks | Shablon. T-1 da kirillcha `АРТ` |
| `{yil}` | Shartnoma yili | 308 tadan 305 tasida shartnoma sanasidagi yil bilan mos yoki sana bo'sh. 3 tasida raqam 2024-yilniki, sana esa 2025 yoki 2026-yil |
| Harf | `A` yoki `K`. Ma'nosi noma'lum | 2024-yilda ikkalasi ham bor, 2025–2026 yillarda faqat `A`. Barcha shablonlarda bu harf kirillcha `А` (U+0410), CSV da 200 ta lotin `A` va 37 ta kirill `А` |
| `{tuman}` | Tuman kodi, 2 xona | 308 tadan 308 tasida tuman bilan aynan mos, quyidagi jadvalga qarang |
| `{tartib}` | Tartib raqami, 5 xona, oldiga nol qo'shiladi | Yil bo'yicha qayta boshlanmaydi: 2024 → 187–341, 2025 → 342–397, 2026 → 398–505. 2024-yilda `A` va `K` bitta ketma-ketlikdan raqam olgan. Bitta takror raqam bor: 2024, `A`, 240 |

| Kod | Tuman | Kod | Tuman | Kod | Tuman |
|---|---|---|---|---|---|
| 01 | Uchtepa | 05 | Yakkasaroy | 09 | Mirzo Ulug'bek |
| 02 | Bektemir | 06 | Sergeli | 10 | Shayxontohur |
| 03 | Chilonzor | 07 | Yunusobod | 11 | Mirobod |
| 04 | Yashnobod | 08 | Olmazor | 12 | Yangihayot |

- **RQ-01.** Tuman kodi `districts` ma'lumotnomasidan olinadi va kodga yozilmaydi. Yuqoridagi jadval ma'lumotnomaning boshlang'ich qiymati. Manba: CSV tahlili, 308/308.
- **RQ-02.** Tartib raqami bitta umumiy hisoblagichdan beriladi, yil bo'yicha qayta boshlanmaydi va takrorlanmaydi. Bazada unique cheklov bo'ladi, raqam tranzaksiya ichida qulf bilan beriladi. Manba: CSV tahlili. Rasmiy qoida tasdiqlanishi kerak (Q-14).
- **RQ-03.** Raqam shartnoma tasdiqlanganda beriladi va keyin o'zgarmaydi. Eski formatdagi raqamlar (`APT-99/99`, `АРТ 999/99` va boshqa, jami 173 ta) import uchun o'zgarishsiz saqlanadi.
- Harfning ma'nosi va qaysi alifboda yozilishi ochiq savol (Q-14).

## 4. To'lov shartlari va reja-jadval

| Shart | Bir yo'la | Bo'lib-bo'lib |
|---|---|---|
| Oldindan to'lov | 100%, imzolangandan keyin 3 ish kuni ichida (2.5) | 20%, imzolangandan keyin 3 ish kuni ichida (2.5, 1-tire) |
| Qolgan qism | — | 80%, har chorakda teng ulushlarda, eng ko'pi N yil (2.5) |
| Oxirgi muddat | Foydalanishga ruxsatnoma sanasigacha | Foydalanishga ruxsatnoma sanasigacha (2.5) |
| Oldindan to'lov o'tkazilmasa | — | Shartnoma xabarnomasiz haqiqiy emas hisoblanadi (2.5, oxirgi xatboshi) |
| Kafolat | — | Bank kafolati yoki sug'urta polisi, olingandan keyin 2 ish kuni ichida (3.4.3) |

Mavjud bazada boshqa nisbatlar ham uchraydi: 40/60, 50/50, 30/70, 68.1/31.9. Shuning uchun oldindan to'lov foizi o'zgaruvchan parametr bo'lishi kerak, standart qiymat 20%.

- **RJ-01.** Badallar soni = N × 4, bu yerda N 2.5-banddagi yillar soni. Manba: 2.5 ("har chorakda teng miqdorda"). S-2026: N = 2, 8 badal.
- **RJ-02.** Badal summasi = qolgan qism / badallar soni. Hisoblash BCMath'da tiyingacha. S-2026: 6 345 022 809,60 / 8 = 793 127 851,20, qoldiqsiz. Teng bo'linmasa qoldiq qaysi badalga qo'shilishi noma'lum (Q-6).
- **RJ-03.** Tekshiruv: badallar yig'indisi qolgan qismga teng, oxirgi badal sanasi shartnoma sanasi + N yildan oshmaydi. Shart bajarilmasa, jadval saqlanmaydi. Manba: 2.5. S-2026 dagi xato (1.3-bo'lim) shu tekshiruv bilan ushlanadi.
- **RJ-04.** Badal sanalari qoidasi aniq emas: joriy shablonda har chorakning 20-sanasi, S-2026 da 29-sanasi (Q-6).
- **RJ-05.** Imzolangan reja-jadval shartnoma bilan birga o'zgarmaydi. Talabnoma va penya faqat imzolangan jadval bo'yicha hisoblanadi. Jadval faqat qo'shimcha kelishuv bilan o'zgaradi. Manba: 5.2-band.

## 5. Shartnoma holatlari

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

## 6. Qo'shimcha kelishuv

**Asoslar.** Shablonning 2.6-bandi bo'yicha:
- Kengash qarori bilan koeffitsiyentlar o'zgarganda;
- shartnoma parametrlari ekspertiza xulosasidan farq qilganda;
- ekspertiza parametrlari foydalanishga qabul qilingan bino parametrlaridan farq qilganda.

**Jarayon:**
1. Investor o'zgarish aniqlangan kundan boshlab 5 ish kuni ichida kelishuv loyihasi bilan murojaat qiladi (2.7). Kechikkan har bir kun uchun penya to'lanadi (6.1, 2-tire). 6.1-bandning 2-tiresida kunlik asos "Yig'im summasi", chegara esa "to'lovi kechiktirilgan Yig'im summasining 15 foizi" deb yozilgan. Bu holatda kechiktirilgan to'lov bo'lmaydi, shuning uchun chegara asosi aniq emas (Q-3).
2. Tizim yangi parametrlar bo'yicha qayta hisoblaydi. Hajm oshgan bo'lsa, qo'shimcha summa **kelishuv imzolanadigan kundagi BHM** bo'yicha olinadi (5.1).
3. Yangi reja-jadval tuziladi. To'langan summalar saqlanib qoladi.
4. Kelishuv raqamlanadi, chop etiladi va imzolanadi. Shartnomaning yangi versiyasi faol bo'ladi.

**Kerakli material:** qo'shimcha kelishuv shabloni hali yo'q.

## 7. Talabnoma

### 7.1. Huquqiy asos

| Band | Mazmuni |
|---|---|
| 3.2.1 | Kompaniya shartnoma shartlari buzilsa, buzilishni bartaraf etishni talab qilish huquqiga ega |
| 4.1 | To'lov monitoringi Jamg'arma faoliyatiga tegishli bank va buxgalteriya hujjatlari asosida olib boriladi |
| 4.2 | To'lov muddati yoki tartibida kamchilik aniqlansa, Kompaniya XII-bo'limdagi aloqa manzillariga talabnoma, ya'ni ogohlantirish xati, va SMS xabarnoma yuboradi |
| 4.3 (birinchi) | Investor talabnomani 3 ish kunidan kechiktirmasdan qanoatlantirishi kerak |
| 4.3 (ikkinchi) yoki 4.4 | To'lov muddati buzilganligi Kompaniya tuzgan dalolatnoma bilan tasdiqlanadi |
| 5.3.1 | Reja-jadval muddati 10 ish kunidan ortiq buzilsa, Kompaniya shartnomani bir tomonlama bekor qilish huquqiga ega |
| 6.1 | Penya |
| 6.3 | Kechikish 3 oydan oshsa, Kompaniya shartnomani bekor qilish talabi bilan sudga murojaat qiladi |
| 9.2–9.3 | Nizo bo'yicha talabnoma xati yuboriladi. Nizo 30 ish kuni ichida hal bo'lmasa, sudga o'tadi |

T-1 matnida 4-bo'lim, ya'ni monitoring, 2.4-band (2024-yilgi versiya) va 6.1-band keltirilgan.

### 7.2. Hujjat tuzilishi, T-1 bo'yicha

T-1 o'zbek tilida, **kirill** yozuvida. Shartnomalar esa lotin yozuvida.

| # | Blok | Mazmuni | Maydonlar va manba |
|---|---|---|---|
| B1 | Blank | Kompaniya nomi, manzil, pochta indeksi, telefon, elektron pochta | `company_requisites` ma'lumotnomasi |
| B2 | Rekvizit | Talabnoma sanasi va raqami, `"__" ______ yil` va `____-son` ko'rinishida | Tizim (TN-10). T-1 shablonida yil "2025" deb qotirilgan |
| B3 | Adresat | Investor nomi jo'nalish kelishigida, STIR, manzil, telefon | Investor kartochkasi, XII-bo'lim (4.2) |
| B4 | Sarlavha | "TALABNOMA (to'lov muddatlari buzilganligi yuzasidan)" | Doimiy matn |
| B5 | Kirish | Taraflar, shartnoma sanasi, raqami va nomi | Shartnoma kartochkasi |
| B6 | Monitoring | Shartnoma 4-bo'limi bo'yicha monitoringda muddatlar buzilgani aniqlangan | Band xaritasi (SH-02) |
| B7 | Majburiyat | Qolgan 80% ni reja-jadval asosida to'lash majburiyati. Band raqami, foiz raqam va so'z bilan, summa raqam va so'z bilan | Band xaritasi, shartnomadagi 80% summa |
| B8 | Penya asosi | 6.1-band matni qisqa bayonda | Band xaritasi. Matndagi farq uchun Q-2 ga qarang |
| B9 | Talab | 3 ish kuni ichida to'lash. Hisob sanasi, asosiy qarz raqam va so'z bilan, penya raqam va so'z bilan, Jamg'arma rekvizitlari | TN-02, TN-03, PN, `fund_requisites` |
| B10 | Ogohlantirish | To'lanmasa, sudga da'vo va vakolatli organlarga Kengash xulosasi hamda ARTni qayta ko'rib chiqish haqida murojaat | Doimiy matn |
| B11 | Ilova | Shartnoma sanasi va raqami bo'yicha hisob-kitoblar | TN-12 |
| B12 | Imzo | "Hurmat bilan", lavozim, imzolovchi "I. Familiya" shaklida | `signatories` ma'lumotnomasi |

### 7.3. Qoidalar

- **TN-01. Qachon tuziladi.** Hisob sanasida muddati o'tgan va to'liq to'lanmagan badal bo'lsa, shartnoma talabnoma nomzodlari ro'yxatiga tushadi. Bu oldindan to'lovga ham, reja-jadval badaliga ham tegishli. Manba: 4.2. Talabnoma necha kun kechikkandan keyin yuboriladi, avtomatik yaratiladimi yoki xodim tasdiqlaydimi, noma'lum (Q-16).
- **TN-02. Hisob sanasi.** Talabnomada hisob sanasi alohida maydon (`as_of_date`) bo'ladi. U talabnoma sanasidan farq qilishi mumkin. Barcha summalar shu sana bo'yicha hisoblanadi. Manba: T-1, B9 da hisob sanasi alohida ko'rsatilgan.
- **TN-03. Asosiy qarz.** Asosiy qarz = hisob sanasigacha muddati kelgan badallar yig'indisi − shu sanagacha tushgan asosiy to'lovlar. To'lovlar eng eski badaldan boshlab yopiladi (FIFO). Penya to'lovlari asosiy qarzni kamaytirmaydi. Manba: T-1, B9: "to'lov grafigiga asosan saqlanib kelayotgan asosiy qarz". Muddati aynan hisob sanasiga to'g'ri kelgan badal hisobga kiradimi, noma'lum (Q-5).
- **TN-04. Penya.** Hisob sanasigacha 8-bo'lim bo'yicha hisoblanadi. Talabnomaga kiritilgan summa snapshot sifatida qotiriladi. Hisob sanasidan keyin penya to'xtamaydi.
- **TN-05. Majburiyat summasi.** B7 blokida shartnoma bo'yicha qolgan qism summasi keltiriladi, qarz summasi emas. T-1: 2 841 772 500,00, bu shartnomaning 80% i. Bir yo'la to'lanadigan shartnoma uchun B7 matni namunasi yo'q (Q-12).
- **TN-06. Bajarish muddati.** Muddat 3 ish kuni (4.3). Tizim muddat tugash sanasini ish kunlari kalendari bo'yicha hisoblaydi va `response_due_date` maydonida saqlaydi. Muddat qaysi kundan boshlanadi, noma'lum: talabnoma sanasidan, yuborilgan kundan yoki investor olgan kundan (Q-4). T-1 matnida aniq sana yozilmagan.
- **TN-07. To'lov rekvizitlari.** B9 dagi Jamg'arma rekvizitlari shartnoma 2.2-bandidagi bilan bir manbadan, ya'ni `fund_requisites` ma'lumotnomasidan olinadi. Talabnoma sanasidagi versiya ishlatiladi. Manba: T-1 va S-2026 2.2 da qiymatlar bir xil.
- **TN-08. Adresat.** Ma'lumotlar investor kartochkasidan, XII-bo'limdagi aloqa manzili bo'yicha olinadi (4.2). Jismoniy shaxs uchun talabnoma namunasi yo'q (Q-11).
- **TN-09. Yetkazish.** Talabnoma XII-bo'limdagi manzilga yuboriladi va SMS xabarnoma jo'natiladi (4.2). Yuborilgan sana, usul, SMS holati va yetkazilganlik dalili saqlanadi. SMS matni hali berilmagan (Q-16).
- **TN-10. Raqam va sana.** Talabnoma raqami butun son, `N-son` ko'rinishida. CSV dagi 69 ta talabnoma raqami 637 dan 1053 gacha, takrorsiz. Ular 2025-yil avgust va 2026-yil mart oralig'ida berilgan va yil boshida qayta boshlanmagan. Bu Kompaniyaning umumiy chiquvchi xatlar hisoblagichiga o'xshaydi. Raqamni kim beradi, noma'lum: tizimmi yoki devonxonami (Q-10). Tizimda raqam unique bo'ladi.
- **TN-11. Imzolovchi.** T-1 da Boshqaruv raisi imzolaydi. Lavozim va ism `signatories` ma'lumotnomasidan talabnoma sanasi bo'yicha olinadi va "I. Familiya" shaklida yoziladi. Manba: T-1, B12.
- **TN-12. Ilova.** Ilova shartnoma bo'yicha hisob-kitoblar. Uning shakli taqdim etilmagan (Q-13). Tizim kamida har bir badal uchun quyidagilarni chiqara olishi kerak: muddat, summa, to'langan qism, qarz, kechikish kunlari, penya. Bu PN qoidalaridan kelib chiqadi.
- **TN-13. O'zgarmaslik va tarix.** Yaratilgan talabnoma o'zgartirilmaydi: raqam, sana, hisob sanasi, summalar, penya tafsiloti, band xaritasi versiyasi va PDF. Xato bo'lsa, talabnoma bekor qilinadi va yangisi chiqariladi. Bitta shartnomada bir nechta talabnoma bo'lishi mumkin, shuning uchun ular alohida jadvalda saqlanadi. Hozirgi `demand_letter_number` va `demand_letter_date` ustunlari faqat eski ma'lumotni ko'chirish uchun ishlatiladi. Manba: CLAUDE.md "hujjat o'zgarmaydi" tamoyili, KAMCHILIKLAR A5.
- **TN-14. Yozuv.** T-1 kirill yozuvida. Talabnoma faqat kirillda chiqadimi yoki lotinda ham, noma'lum (Q-11). So'zga aylantirish ikkala yozuvni qo'llaydi (SH-06).
- **TN-15. Keyingi qadamlar.** Talabnoma muddati o'tsa, dalolatnoma tuziladi (4.3 ikkinchi yoki 4.4). Badal 10 ish kunidan ko'p kechiksa, bir tomonlama bekor qilish huquqi paydo bo'ladi (5.3.1). Kechikish 3 oydan oshsa, sudga murojaat qilinadi (6.3). Tizim bu sanalarni har bir badal uchun hisoblaydi va ko'rsatadi, lekin harakatni avtomatik bajarmaydi. 9.3-banddagi 30 ish kunlik muddat to'lov talabnomasiga ham tegishlimi, noma'lum (Q-9).
- **TN-16. Ruxsatlar.** Talabnomani yaratish, raqamlash, imzolash va bekor qilish rol orqali tekshiriladi. Har bir harakat audit jurnaliga yoziladi. Manba: CLAUDE.md.

**Talabnoma holatlari:**

```
qoralama → raqamlangan va imzolangan → yuborilgan → bajarildi (qarz yopildi)
                                                  └→ muddati o'tdi → dalolatnoma
qoralama yoki imzolangan → bekor qilingan (sabab bilan)
```

### 7.4. T-1 ni ma'lumotlar bilan tekshirish

T-1 2024-yil oktyabrdagi bitta shartnomaga tegishli: Yakkasaroy, 4-zona, bo'lib-bo'lib, 20/80, 8 badal. Shartnoma raqami private namunada.

| Tekshiruv | T-1 | Ma'lumotlar | Natija |
|---|---|---|---|
| Yig'im summasi | 80% = 2 841 772 500,00 | CSV: 3 552 215 625,00. 12 630,1 × 375 000 × 0,75 = 3 552 215 625,00 | mos |
| Badal | — | 2 841 772 500,00 / 8 = 355 221 562,50 | — |
| Asosiy qarz, 28.09.2026 holatiga | 1 776 107 812,50 = 5 badal | CSV jadvali: 2025-yil yanvardan 2026-yil oktyabrgacha har chorakda. 28.09.2026 gacha 7 ta badal muddati kelgan, 1 tasi to'langan, demak 6 badal = 2 131 329 375,00 | **mos emas, 1 badal farq** (Q-8) |
| Penya | 44 261 051,00 | Quyidagi tahlilga qarang | Asos tasdiqlandi |
| Talabnoma CSV da | Bor | `Talabnoma nomer` va `Talabnoma sana` bo'sh | Tizimga kiritilmagan |

**Penya tahlili.** 44 261 051,00 aynan quyidagi ko'rinishda ifodalanadi:

```
0,0001 × (1246 × 355 221 562,50 + 1 × 4 443 125,00) = 44 261 051,00
```

- Bu yerda 355 221 562,50 bitta badal. 4 443 125,00 esa oldindan to'lovning keyingi kuni to'langan qoldig'i. Oldindan to'lov `fakt_apz.csv` bo'yicha ikki qismda tushgan: 706 000 000,00 va bir kundan keyin 4 443 125,00.
- Agar kunlik asos to'liq Yig'im summasi bo'lganda edi, bir kunlik penya 355 221,56 bo'lardi. 44 261 051,00 / 355 221,5625 = 124,6 kun chiqadi, ya'ni butun son emas. Shartnomaning 80% i ham butun kun bermaydi.
- **Xulosa.** Amalda penya **kechiktirilgan summa × kalendar kunlar × 0,01%** formulasi bilan hisoblangan. Oldindan to'lovning kechikishi ham penyaga kiritilgan. Badallarning aniq sanalari noma'lum, shuning uchun 1246 badal-kunni sanalarga ajratib bo'lmaydi.
- 15% chegarasi bu holatda hech qaysi talqinda ishlamaydi. Bitta badalning 15% i 53 283 234,375, bu esa butun penyadan katta. Shuning uchun T-1 B2 masalasini hal qilmaydi.

## 8. Penya hisoblash

- **PN-01. Kunlik stavka va asos.** Penya kuniga 0,01%, ya'ni 0,0001. Asos to'lovi kechiktirilgan summa. Manba: 6.1, 1-tire. Amaliyot bilan tasdiqlangan: 7.4-bo'lim. Mavjud kod ham shu asosni ishlatadi (`CONTRACT_PENALTY_DAILY_RATE`).
- **PN-02. Qismlar bo'yicha.** Har bir kechikkan badal yoki uning qolgan qismi uchun penya alohida hisoblanadi: qolgan summa × kunlar × 0,0001. Qisman to'lov tushgan kundan boshlab penya kamaygan qoldiqqa hisoblanadi. Manba: 6.1; T-1 dagi 4 443 125,00 qoldig'iga 1 kun ortiq hisoblangan.
- **PN-03. Oldindan to'lov.** T-1 da kechikkan oldindan to'lovga penya hisoblangan. Lekin 2.5-bandning oxirgi xatboshisiga ko'ra, oldindan to'lov muddati buzilsa, shartnoma xabarnomasiz haqiqiy emas hisoblanadi. Qaysi qoida qo'llanishi noma'lum (Q-1).
- **PN-04. Kunlar.** Kunlar kalendar kunlarda sanaladi, chunki 6.1 da "har bir kun" deyilgan. Ikki to'lov sanasi orasidagi farq kunlar farqiga teng (T-1). Muddat kunining o'zi va to'lov kuni hisobga kiradimi, noma'lum (Q-5).
- **PN-05. Chegara.** 15% chegarasining asosi noma'lum (Q-2, KAMCHILIKLAR B2).
- **PN-06. Aniqlik.** Hisoblash BCMath bilan, kamida 4 xona kasr bilan olib boriladi. Yakuniy summa qanday yaxlitlanishi noma'lum (Q-5). T-1 natijasi tiyinsiz butun son bo'lgani uchun dalil bermaydi.

## 9. Dalolatnoma va bekor qilish

| Hodisa | Tizim harakati | Manba |
|---|---|---|
| Talabnoma muddati o'tdi | Dalolatnoma tuziladi | 4.3 (ikkinchi) yoki 4.4 |
| Badal 10 ish kunidan ortiq kechikdi | Kompaniya bir tomonlama bekor qilish huquqiga ega bo'ladi | 5.3.1 |
| Kechikish 3 oydan oshdi | Bekor qilish talabi bilan sudga murojaat qilinadi | 6.3 |

**Kerakli materiallar:** dalolatnoma va bekor qilish xati shablonlari.

## 10. Elektron imzo

- Tizimda E-IMZO allaqachon ishlatiladi, lekin faqat kirish uchun. `EImzoService` PKCS#7 imzosini tekshiradi.
- Hujjatni imzolash uchun quyidagilar kerak:
  - yaratilgan PDF ning xeshini imzolash;
  - imzoni va sertifikat ma'lumotini saqlash;
  - hujjatga QR kod va tekshirish sahifasini qo'shish.
- Shartnomani ikkala taraf imzolaydi: Kompaniya va investor. Talabnomani faqat Kompaniya imzolaydi (T-1). Investor imzosi qanday olinadi, buni aniqlash kerak: shaxsiy kabinet, my.gov.uz yoki qog'oz nusxa.

## 11. Ma'lumotnomalar

| Ma'lumotnoma | Tarkibi | Kim ishlatadi |
|---|---|---|
| `company_requisites` | Nomi, manzil, indeks, telefon, elektron pochta, STIR, bank, MFO, bank STIR, amal qilish sanasi | Shartnoma XII, talabnoma B1 |
| `fund_requisites` | Jamg'arma STIR, bank, hisob raqami, bank STIR, MFO, shaxsiy hisob raqami, amal qilish sanasi | Shartnoma 2.2, talabnoma B9 |
| `signatories` | F.I.Sh., qisqa shakli, lavozimi, vakolat asosi, amal qilish davri | Shartnoma preambulasi va XII, talabnoma B12 |
| `districts` | Kod va nomi | Raqamlash (RQ-01), to'lov maqsadi (2.3) |
| `template_versions` | Shablon kodi, versiya, fayl, amal qilish sanasi, band xaritasi | SH-01, SH-02 |
| `working_calendar` | Bayram va qo'shimcha dam olish kunlari | TN-06, 2.5, 5.3.1 |

Barcha qiymatlar migratsiya yoki admin ekrani orqali kiritiladi, kodda konstanta bo'lmaydi.

## 12. Etalon misollar, test uchun

| # | Kirish | Kutilgan natija | Manba |
|---|---|---|---|
| E-RJ1 | Qolgan qism 6 345 022 809,60, N = 2 | 8 badal, har biri 793 127 851,20, yig'indisi 6 345 022 809,60 | S-2026, RJ-01, RJ-02 |
| E-RJ2 | Shartnoma 30.07.2026, N = 2, oxirgi badal 29.10.2028 | Tekshiruv xato beradi: oxirgi sana 30.07.2028 dan keyin | RJ-03, 1.3-bo'lim |
| E-TN1 | 8 badal × 355 221 562,50. Hisob sanasigacha 6 badal muddati kelgan, 1 badal to'langan | Asosiy qarz 1 776 107 812,50 | TN-03, T-1 |
| E-PN1 | Badal 355 221 562,50, 8 kun kechikkan | Penya 284 177,25 | PN-01 |
| E-PN2 | Oldindan to'lov 710 443 125,00, muddati D. 706 000 000,00 D+6 kuni, 4 443 125,00 D+7 kuni to'langan | Yaxlitlashdan oldin 426 710,1875. Yaxlitlash Q-5 hal bo'lgach qotiriladi | PN-02 |
| E-PN3 | Badal-kunlar: 1246 × 355 221 562,50 va 1 × 4 443 125,00 | 44 261 051,00 | T-1, 7.4-bo'lim |
| E-PN4 | Shartnoma qiymati 5 000 000,00, kechikkan badal 1 000 000,00, 2000 kun | Chegarasiz 200 000,00. A talqin, kechiktirilgan summaning 15% i: **150 000,00**. B talqin, shartnoma qiymatining 15% i (750 000,00): **200 000,00** | PN-05, Q-2. Test ikkala talqin uchun parametrlangan bo'ladi, javobdan keyin bittasi qoladi |
| E-SW1 | 1 586 255 702,40, lotin, `butun` | "bir milliard besh yuz sakson olti million ikki yuz ellik besh ming yetti yuz ikki butun yuzdan qirq" | S-2026, 2.5 |
| E-SW2 | 0,6, lotin, `butun` | "nol butun o'ndan olti" | S-2026, 2.1 |

## 13. Ochiq savollar

Huquqshunos yoki mas'ul bo'lim javob berishi kerak. Javob kelguncha tizim tegishli qoidani o'zi tanlamaydi.

| # | Savol | Talqinlar va summaga ta'siri | Kim kutadi |
|---|---|---|---|
| Q-1 | **Kechikkan oldindan to'lov.** 2.5-bandning oxirgi xatboshisiga ko'ra shartnoma haqiqiy emas bo'ladimi yoki T-1 dagidek amalda qoladi va penya hisoblanadimi? | A: shartnoma haqiqiy emas, penya yo'q, butun summa qaytadan tuziladi. B: shartnoma amalda, kechikkan 20% ga penya hisoblanadi. T-1 da B qo'llangan: 710 443 125,00 ning kechikishi penyaga kirgan | Talabnoma, holatlar |
| Q-2 | **Penya chegarasi (B2).** 15% nimadan olinadi? T-1 matni ham, 6.1 ham "to'lovi kechiktirilgan Yig'im summasining 15%" deydi, kod esa shartnoma qiymatining 15% ini oladi. Qo'shimcha savol: chegara har bir badal uchun alohidami yoki jami kechikkan summaga? T-1 matnida kunlik stavka "Yig'im summasining 0,01%" deb yozilgan, 6.1 da esa "to'lovi kechiktirilgan Yig'im summasining". Hisob 6.1 bo'yicha qilingan. Talabnoma shablonidagi bu jumla tuzatiladimi? | E-PN4: 150 000,00 yoki 200 000,00. Butun penya shartnoma qiymatining 15% iga yaqinlashganda farq katta bo'ladi | Penya, talabnoma |
| Q-3 | **6.1-band 2-tiresi.** Qo'shimcha kelishuvga kech murojaat qilinganda chegara asosi nima, agar kechikkan to'lov bo'lmasa? | A: Yig'im summasining 15% i. B: chegara yo'q, chunki asos nolga teng | Qo'shimcha kelishuv |
| Q-4 | **3 ish kuni qachondan boshlanadi?** Talabnoma sanasidanmi, yuborilgan kundanmi yoki investor olgan kundanmi? Talabnomaga aniq oxirgi sanani yozish kerakmi? | Dalolatnoma va sudga murojaat sanasi 1–5 kunga siljiydi | Talabnoma |
| Q-5 | **Kun sanash va yaxlitlash.** Muddat kuni va to'lov kuni penyaga kiradimi? Muddati hisob sanasiga teng badal qarzga kiradimi? Penya tiyingacha qanday yaxlitlanadi? | Har bir badal uchun ±1 kun, ya'ni 355 221 562,50 lik badalda ±35 522,16 so'm | Penya |
| Q-6 | **Reja-jadval sanalari.** Badal kuni qanday tanlanadi: joriy shablondagidek 20-sana, S-2026 dagidek 29-sana (shartnoma sanasi CSV da 30.07.2026) yoki shartnoma sanasidagi kun? Birinchi badal shartnomadan 3 oy keyin keladimi? Oy oxiri qanday hisoblanadi? Teng bo'linmagan qoldiq qaysi badalga qo'shiladi? | Penya va talabnoma sanalari o'zgaradi | Reja-jadval |
| Q-7 | **S-2026 jadvali.** Imzolangan hujjatdagi jadval qaysi: ilovadagi, ya'ni 2027-yil iyulsiz va 10.2028 bilan tugaydigan, yoki CSV dagi to'g'ri choraklar? | 793 127 851,20 lik bitta badal muddati 3 oyga, oxirgisi 2.5 dagi 2 yildan tashqariga siljiydi | Ma'lumot, RJ-03 |
| Q-8 | **T-1 asosiy qarzi.** T-1 da 5 badal qarz, CSV jadvalida esa 6 badal. CSV jadvali bir chorak oldinga siljiganmi yoki to'lov fakti to'liq emasmi? | 355 221 562,50 asosiy qarz va unga penya | Ma'lumot, import |
| Q-9 | **9.3-band va sud.** To'lov talabnomasidan keyin sudga 30 ish kunidan oldin murojaat qilish mumkinmi? 6.3 da 3 oy deyilgan, T-1 esa 3 ish kunidan keyin sud bilan ogohlantiradi | Sud jarayoni boshlanadigan eng erta sana | Talabnoma, holatlar |
| Q-10 | **Talabnoma raqami.** Raqamni tizim beradimi yoki devonxonaning umumiy chiquvchi hisoblagichi? Har yili qayta boshlanadimi? | Raqam berish mexanizmi | Talabnoma |
| Q-11 | **Jismoniy shaxsga talabnoma va yozuv.** Adresat blokida nima yoziladi: JShShIR, pasport yoki manzil? Talabnoma faqat kirillda chiqadimi? | Shablon variantlari soni | Talabnoma |
| Q-12 | **Eski shablon versiyalari.** 2024- va 2025-yilgi shartnoma shablonlari matni kerak, chunki band xaritasi ular bo'yicha tuziladi. Bir yo'la to'lanadigan shartnoma uchun talabnoma B7 matni ham kerak | Talabnomada noto'g'ri band ko'rsatilish xavfi | Band xaritasi |
| Q-13 | **Talabnoma ilovasi.** "Hisob-kitoblar" ilovasining shakli qanday? | — | Talabnoma |
| Q-14 | **Shartnoma raqami.** `A` va `K` harflari nimani bildiradi? Harf lotinda yoziladimi yoki kirillda? Tartib raqami haqiqatan ham yil bo'yicha qayta boshlanmaydimi? 2024-`A`-240 raqami nega ikki marta ishlatilgan? | Raqam unique'ligi | Raqamlash |
| Q-15 | **S-2026 rasmiy yangi versiyami?** 9.3 dagi aniq sud nomi va 2.1 dagi kengaytirilgan hujjatlar ro'yxati qolgan to'rt shablonga ham kiritiladimi? Qachondan amal qiladi? | Shablon versiyasi va sanasi | Shablonlar |
| Q-16 | **Talabnoma qachon yuboriladi?** Badal kechikkan kuniyoq yuboriladimi yoki N kundan keyinmi? Avtomatik yaratiladimi yoki xodim tasdiqlaydimi? Takroriy talabnoma bo'ladimi? SMS matni qanday? | Talabnomalar soni va ish yuklamasi | Talabnoma |

Yig'imga tegishli ochiq savollar, jumladan Kj (B3), [yigim-hisoblash.md](yigim-hisoblash.md) ning 8-bo'limida.
