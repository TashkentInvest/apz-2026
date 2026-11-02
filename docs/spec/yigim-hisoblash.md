# Spetsifikatsiya: ART qo'shimcha yig'imini hisoblash

**Manba:** Xalq deputatlari Toshkent shahar Kengashining 2024-yil 2-iyuldagi VI-104-94-14-0-K/24-son qarori, 2-ilova ("Mexanizm"). Matn: [qaror-VI-104-94-14-0-K24.txt](../materials/qaror-VI-104-94-14-0-K24.txt).
**Amal qilish:** 2024-yil 25-martdan boshlab yuzaga kelgan huquqiy munosabatlarga (qaror 3-band).
**Qo'shimcha manba:** 2026-11-02 dagi to'ldirilgan shartnoma namunasi S-2026, talabnoma namunasi T-1 va qo'shimcha kelishuv namunasi KK-1, [shartnoma-hujjatlari.md](shartnoma-hujjatlari.md) ga qarang. Namunalar repoga kiritilmaydi.
**Holat:** Qoralama. "Ochiq savollar" bo'limidagi bandlar huquqshunos tasdig'ini kutmoqda.

## 1. Formula

```
Ti = Bh × ((Hb + Hyu) − (Ha + Ht + Hu)) × K
K  = Kt × Ko × Kz × Kj
```

| Belgi | Ma'nosi | Birlik | Manba hujjat |
|---|---|---|---|
| Ti | Yig'im miqdori | so'm | hisoblanadi |
| Bh | Bazaviy hisoblash miqdorining (BHM) bir barobari | so'm | BHM tarixi jadvali, sana bo'yicha |
| Hb | Shaharsozlik normalariga muvofiq binoning qurilish hajmi | m³ | ekspertiza xulosasi |
| Hyu | Ruxsat etilgan qavatlar sonidan yuqori qurilish hajmi | m³ | ekspertiza xulosasi |
| Ha | Avtoturargoh qismi umumiy hajmi | m³ | ekspertiza xulosasi |
| Ht | Texnik qavatlar, inshootlar va xonalar hajmi | m³ | ekspertiza xulosasi |
| Hu | Turar joy binosining umumiy foydalanishdagi qismi hajmi | m³ | ekspertiza xulosasi |
| Kt, Ko, Kz, Kj | Qurilish turi, obyekt turi, hududiy zona, joylashuv koeffitsiyentlari | — | 2-bo'lim jadvallari |

Qaror 3-band: hajmlar loyiha-smeta hujjatlari ekspertiza xulosasida qayd etilgan o'lchamlar bo'yicha olinadi.

## 2. Koeffitsiyent jadvallari

### Kt — qurilish turi
| Kod | Holat | Kt |
|---|---|---|
| `new` | Yangi kapital qurilish | 1 |
| `reconstruction_added` | Rekonstruksiya, koeffitsiyent qo'shilgan hajmga | 1 |
| `reconstruction_no_expertise` | Ekspertiza talab etilmaydigan obyekt rekonstruksiyasi | 0 |
| `reconstruction_same_volume` | Hajmni o'zgartirmagan rekonstruksiya | 0 |

### Ko — obyekt turi
| Kod | Holat | Ko |
|---|---|---|
| `social_tourism` | Alohida turgan xususiy ijtimoiy infratuzilma va turizm obyektlari: maktab, MTT, sport, madaniy, diniy, mehmonxona, mehmon uyi | 0,5 |
| `state_share_gt50` | Davlat ulushi 50% dan ortiq loyihalar obyektlari | 0,5 |
| `production_aux` | Ishlab chiqarish korxonalarining ovqatlanish joylari, sport zallari, ofislari va turar joylari | 0,5 |
| `warehouse_2m` | Har qavati 2 m balandlikdan oshmaydigan omborxonalar, ma'muriy-xo'jalik binolari bundan mustasno | 0,5 |
| `other` | Yuqoridagilarga kirmaydigan boshqa obyektlar | 1 |

### Kz — hududiy zona
| Zona | 1 | 2 | 3 | 4 | 5 |
|---|---|---|---|---|---|
| Kz | 1,40 | 1,25 | 1,00 | 0,75 | 0,50 |

Zonalar Toshkent shahar hokimi qarori bilan tasdiqlanadi. Mahalla va ko'cha bo'yicha zona ro'yxati tizimga ma'lumotnoma sifatida kiritilishi kerak.

### Kj — obyekt joylashuvi
| Holat | Kj |
|---|---|
| Qaror matni: metro chiqish joyidan obyekt chegarasigacha 200 m radiusdan **tashqarida** joylashgan bino | 0,6 |
| Boshqa obyektlar | 1 |

Bu yerda qaror va shartnoma shabloni bir-biriga zid. 1-ochiq savolga qarang.

Qaror 2-ilova, IV jadval, 1-qator: "Metro bekatidan chiqish joyidan obyekt chegarasigacha 200 metr radius oralig‘i hududlardan boshqa hududlarda joylashgan loyihaviy binolar (inshootlar)" uchun 0,6. Joriy 5 ta shablonning 2.1-bandida esa 0,6 "200 metr radius oralig‘idagi hududlar uchun" deb yozilgan.

**2026-11-02 dagi dalil.** To'ldirilgan shartnoma namunasi S-2026 ([shartnoma-hujjatlari.md](shartnoma-hujjatlari.md), 1.2-bo'lim) 2.1-bandda xuddi shu shablon izohini takrorlaydi. Namunada 0,6 qo'llanilgan: Kt = 1, Ko = 1, Kz = 1 (3-zona), Kj = 0,6, demak K = 0,6. Shunday qilib, amaliyot shablon matniga ergashadi. Lekin namunadan obyektning metrodan qancha uzoqligi ko'rinmaydi, shuning uchun bu ziddiyatni hal qilmaydi. Summaga ta'siri: S-2026 obyekti 200 m ichida bo'lsa va qaror talqini to'g'ri bo'lsa, Kj = 1 bo'lardi. Unda yig'im 13 218 797 520,00 bo'ladi, namunadagi 7 931 278 512,00 o'rniga, farqi 5 287 519 008,00.

## 3. Yakuniy koeffitsiyent qoidalari

K quyidagi tartibda hisoblanadi:

1. `K = Kt × Ko × Kz × Kj`.
2. K = 0 bo'lsa, K = 0, ya'ni yig'im undirilmaydi.
3. 0 < K < 0,5 bo'lsa, K = 0,5.
4. K > 2,0 bo'lsa, K = 2,0.
5. Natija har doim `K = 0` yoki `0,5 ≤ K ≤ 2,0` oralig'ida bo'ladi.

Qo'shimcha qoidalar:
- **Ht cheklovi:** Ht sanoat maqsadidagi ishlab chiqarish kapital binolariga, shu jumladan omborxonalarga qo'llanilmaydi.
- **Qo'shimcha qavat:** Mavjud binoga qavat qo'shilsa, faqat qo'shimcha hajm hisoblanadi. Undan (Ha + Ht + Hu) ayiriladi va K qo'llaniladi.
- **Renovatsiya hududi:** Hududdagi qurilish hajmi uchun 0,5 koeffitsiyent asosida yig'im undiriladi. Kompensatsiya va ijtimoiy majburiyat hajmlari bundan mustasno.
- **Hajm manfiy bo'lmaydi:** `(Hb + Hyu) − (Ha + Ht + Hu)` manfiy chiqsa, bu kiritishdagi xato. Tizim hisoblashni to'xtatib, xato ko'rsatishi kerak.
- **Yig'im hajmi shartnomada alohida yoziladi.** S-2026 shartnomasining 2.1-bandida "Obyekt yig'imining umumiy hajmi" yozilgan, ya'ni ayirmalardan keyingi hajm. Ma'lumot varag'ida esa Hb, Hyu, Ha, Ht va Hu alohida beriladi. Tizim ikkala qiymatni ham saqlaydi va ular orasidagi farqni ko'rsatadi. Qaysi biri yig'im uchun asos bo'lishi 6-ochiq savolda.

## 4. Yig'im undirilmaydigan holatlar

Quyidagi holatlarda Ti = 0. Har bir istisno uchun asos hujjati majburiy saqlanadi.

1. Renovatsiya hududida buzilgan bino egalariga kompensatsiya sifatida beriladigan hajm va investorning ijtimoiy majburiyatlari doirasidagi hajm.
2. Davlatga beg'araz topshiriladigan bino yoki uning topshiriladigan qismi.
3. Loyihaviy hajmning 50% va undan ko'prog'i davlatga beg'araz topshiriladigan bino. Bu holatda butun hajm ozod.
4. Fuqarolarning o'zini o'zi boshqarish organlari binolari, to'liq hajmda.
5. Muhandislik-kommunikatsiya va transport infratuzilmasi obyektlari.
6. Sanoat maqsadidagi ishlab chiqarish kapital binolari. Ma'muriy-xo'jalik binolari, ovqatlanish joylari, sport zallari, ofislar va turar joylar ozod emas.
7. Diniy maqsadlardagi bino va inshootlar.

## 5. BHM qaysi sana bo'yicha olinadi

- **Asosiy shartnoma:** shartnoma sanasida amalda bo'lgan BHM. Bu qoida mavjud ma'lumotlar bilan tasdiqlangan, 6-bo'limga qarang.
- **Qo'shimcha kelishuv, asl hajm:** asl shartnoma sanasidagi BHM. Asl qism qayta hisoblanmaydi. Manba: KK-1, 2.1 yangi tahriri, 1-tire.
- **Qo'shimcha kelishuv, qo'shilgan hajm:** shablonning 5.1-bandiga ko'ra kelishuv imzolanadigan kundagi BHM. KK-1 da esa investor xati sanasidagi BHM olingan. Bu ziddiyat 9-ochiq savolda.
- **BHM tarixi** tizimda alohida jadvalda saqlanadi: `amal_boshlanish_sanasi` va `qiymat`. Kod ichiga yozilmaydi.

| Amal qilish boshlanishi | BHM, so'm | Holat |
|---|---|---|
| 2023-06-01 | 340 000 | ma'lumotlar bilan tasdiqlangan |
| 2024-10-01 | 375 000 | rasmiy manba bilan tasdiqlash kerak |
| 2025-08-01 | 412 000 | rasmiy manba bilan tasdiqlash kerak |
| 2026-08-29 va 2026-09-03 oralig'ida | 440 000 | ma'lumotlardan aniqlangan, aniq sana va rasmiy manba kerak |

2026-11-02 dagi namunalar ham bu qiymatlarga mos, lekin rasmiy manba o'rnini bosmaydi:
- 2024-10-18 dagi shartnomada (T-1 talabnomasi) 375 000 ishlatilgan. Formula natijasi aynan teng, 2-etalonga qarang.
- 2026-07-30 dagi shartnomada (S-2026) 412 000 ishlatilgan, 3-etalonga qarang.
- KK-1 da qo'shilgan hajm uchun 2026-09-03 holatiga 440 000 ishlatilgan, 4-etalonga qarang.

**440 000 ni CSV bilan tekshirish.** `grafik_apz.csv` da `Shartnoma qiymati / (hajm × koeffitsiyent)` hisoblandi. 412 000 oxirgi marta 2026-08-28 dagi shartnomada uchraydi. 2026-09-10 dan 2026-10-14 gacha 19 ta shartnomadan 14 tasida 440 000 chiqadi: 11 tasida aynan, 3 tasida 0,01% gacha farq bilan. Qolgan 5 tasida 440 000 ham, 412 000 ham chiqmaydi, ulardan 4 tasida 366 667, ya'ni 440 000 × 5/6. Bu koeffitsiyent ustuni noto'g'ri bo'lishi mumkin, 4-ochiq savolga qarang. Bu davrda 412 000 bo'yicha hisoblangan shartnoma yo'q. KK-1 dagi 2026-09-03 sanasi esa BHM o'zgarishining eng kech chegarasini beradi.

### 5.1. Qo'shimcha kelishuvda qayta hisoblash

Manba: shablon 5.1 va 2.6; KK-1, 2.1 yangi tahriri va 2-, 3-bandlar. Kelishuv qoidalari: [shartnoma-hujjatlari.md](shartnoma-hujjatlari.md), 6-bo'lim, KK-05 – KK-10.

```
Ti_asl   = V_asl × Bh(asl shartnoma sanasi) × K_asl     asl snapshot'dan olinadi, qayta hisoblanmaydi
ΔV       = V_yangi − V_asl                               V_yangi yangi ekspert xulosasidagi yig'im hajmi
Ti_qo'sh = ΔV × Bh(X) × K_qo'sh                          X: 9-ochiq savol
Ti_yangi = Ti_asl + Ti_qo'sh
Qolgan   = Ti_yangi − To'langan(H)                       H: hisob sanasi, penya to'lovlari kirmaydi
Jadval   = Penya(H) + Qolgan
```

- **YH-KK1.** `V_asl + ΔV = V_yangi` va `Ti_asl + Ti_qo'sh = Ti_yangi` tengliklari saqlashdan oldin tekshiriladi. Hisoblash BCMath'da olib boriladi.
- **YH-KK2.** `ΔV` 1-bo'limdagi formula bo'yicha olinadi: `(Hb + Hyu) − (Ha + Ht + Hu)` yangi va asl qiymatlar farqi. KK-1 da faqat jami hajmlar berilgan, komponentlar yo'q. Mavjud binoga qavat qo'shilganda faqat qo'shimcha hajm hisoblanadi (3-bo'lim).
- **YH-KK3.** `K_qo'sh` kelishuv hisoblanadigan sanadagi qaror versiyasi bo'yicha 3-bo'limdagi tartibda hisoblanadi. Snapshot'da Kt, Ko, Kz, Kj alohida saqlanadi. KK-1 da K_asl = K_qo'sh = 0,5, shuning uchun K versiyasi masalasini tekshirib bo'lmaydi.
- **YH-KK4.** `ΔV ≤ 0` bo'lsa yoki K o'zgargan bo'lsa, tizim hisoblamaydi. 10-ochiq savolga qarang.
- **YH-KK5.** Kelishuv hisobi alohida snapshot sifatida saqlanadi: asl snapshot'ga havola, ΔV, X sanasi va BHM, K tarkibi, H, to'langan summa, penya va natijalar (7-bo'lim).

## 6. Mavjud ma'lumotlar bilan tekshiruv, 2026-11-01

Soddalashtirilgan formula ishlatildi: `Ti = hajm × BHM(shartnoma sanasi) × koeffitsiyent`. Bu yerda hajm `build_volume`, koeffitsiyent esa `coefficient` ustunidan olindi.

| Natija | Shartnomalar soni |
|---|---|
| Aynan mos | 208 |
| 5% gacha farq | 4 |
| Nomuvofiq | 50 |
| Jami tekshirilgan | 262 |

Nomuvofiq shartnomalar ro'yxati: `docs/materials/private/formula-nomuvofiq-shartnomalar.csv`. Bu fayl repoga kiritilmaydi.

**Etalon misol, test uchun:**
- **1-shartnoma.** Sana 2024-07-12, hajm 33 818,40 m³, K = 0,5, BHM 340 000. Natija: 33 818,40 × 340 000 × 0,5 = **5 749 128 000** so'm, bazadagi qiymat bilan aynan teng.
- **2-shartnoma, T-1 namunasi.** Sana 2024-10-18, Yakkasaroy, 4-zona. Hajm 12 630,1 m³. Kt = 1, Ko = 1, Kz = 0,75, Kj = 1, demak K = 0,75. BHM 375 000. Natija: 12 630,1 × 375 000 × 0,75 = **3 552 215 625,00** so'm. CSV dagi qiymat bilan aynan teng. 20% = 710 443 125,00. 80% = 2 841 772 500,00, bu talabnomadagi summa bilan teng. Badal 8 × 355 221 562,50.
- **3-shartnoma, S-2026 namunasi.** Sana 2026-07-30, 3-zona, yangi qurilish, turar-joy. Ma'lumot varag'i bo'yicha: Hb = 48 386,95, Hyu = 0, Ha = 4 909,19, Ht = 0, Hu = 11 393,31. Kt = 1, Ko = 1, Kz = 1, Kj = 0,6, demak K = 0,6. BHM 412 000.
  - Hujjatdagi hajm 32 084,46 bo'yicha: 32 084,46 × 412 000 × 0,6 = **7 931 278 512,00** so'm. Bu shartnoma va CSV bilan aynan teng. 20% = 1 586 255 702,40. 80% = 6 345 022 809,60.
  - Komponentlardan hisoblangan hajm boshqacha: 48 386,95 − 4 909,19 − 11 393,31 = **32 084,45**. Bu bo'yicha natija 7 931 276 040,00, farqi 2 472,00 so'm. Test hozircha hujjatdagi hajmni kirish sifatida oladi. Komponentlardan hisoblash testi 6-ochiq savol yopilgandan keyin qo'shiladi.
- **4-shartnoma, KK-1 namunasi, qo'shimcha kelishuv.** Asl shartnoma 2024-yil noyabrda tuzilgan. Asl qism: 2 358,18 × 375 000 × 0,5 = **442 158 750,00**, CSV bilan aynan teng. Qo'shilgan qism: 648,54 × 440 000 × 0,5 = **142 678 800,00**. Jami hajm 3 006,72, jami yig'im **584 837 550,00**. To'langan 265 295 250,00, qolgan 319 542 300,00, ya'ni 2 × 159 771 150,00. KK-1 dagi barcha summalar formulaga aynan mos. To'liq etalonlar: [shartnoma-hujjatlari.md](shartnoma-hujjatlari.md), E-KK1 – E-KK6.

## 7. Yaxlitlash va saqlash

- Pul summalari `DECIMAL(20,2)` turida saqlanadi. Hisoblashda `float` emas, BCMath yoki butun tiyin ishlatiladi.
- Har bir hisoblash "snapshot" sifatida saqlanadi: barcha kirish qiymatlari, tanlangan koeffitsiyent kodlari, BHM qiymati va sanasi, natija va qaror versiyasi. Keyin qaror yoki BHM o'zgarsa ham eski shartnoma qiymati qayta hisoblanmaydi.
- Qaror har chorakda o'zgarishi mumkin. Koeffitsiyent jadvallari qaror versiyasi va amal qilish sanasi bilan saqlanadi.

## 8. Ochiq savollar

Bu savollarga huquqshunos javob berishi kerak.

1. **Kj qoidasi.** Qarorga ko'ra 0,6 metrodan 200 m radiusdan **tashqaridagi** obyektlarga qo'llaniladi. Shartnoma shabloni 2.1-bandida esa 0,6 radius **ichidagi** hududlar uchun yozilgan. Qaysi biri to'g'ri? 2026-11-02 dagi S-2026 namunasi shablon talqinini takrorlaydi, lekin savolni yopmaydi. Summaga ta'siri 2-bo'limda.
2. **Ko jadvali.** Qaror "1–5-qatorlarda ko'rsatilmagan" deydi, lekin jadvalda faqat 4 ta qator bor. Beshinchi toifa tushib qolganmi?
3. **BHM tarixi.** 375 000 va 412 000 qiymatlarining amal qilish sanalarini rasmiy manba bilan tasdiqlash kerak. Namunalar 2024-10-18 da 375 000, 2026-07-30 da 412 000 ishlatilganini ko'rsatadi, lekin aniq boshlanish sanasini bermaydi.
4. **Nomuvofiq shartnomalar.** 50 ta shartnoma uchun sabab nima: hajm ayirmalari, boshqa sanadagi BHM yoki kiritishdagi xato?
5. **Ish kunlari.** "3 ish kuni" va "10 ish kuni" muddatlari uchun rasmiy bayram kunlari kalendari kerak.
6. **Yig'im hajmining manbai.** S-2026 da shartnomadagi hajm 32 084,46, komponentlardan hisoblangani esa 32 084,45. Qaysi biri asos bo'ladi? A: ekspertiza xulosasidagi jami hajm, ya'ni shartnomaga yozilgani. B: Hb, Hyu, Ha, Ht va Hu dan formula bo'yicha hisoblangani. Farq 0,01 m³ uchun 2 472,00 so'm. Bir nechta binoli obyektda komponentlar yaxlitlangan holda yig'ilgan bo'lishi mumkin. Ruxsat etilgan farq qancha?
7. **Aralash obyekt va Ko.** S-2026 obyekti ko'p qavatli turar-joy va xususiy maktabgacha ta'lim muassasasidan iborat. Butun hajmga Ko = 1 qo'llanilgan. Qaror Ko = 0,5 ni faqat "alohida turgan" ijtimoiy obyektlarga beradi. MTT alohida binoda bo'lsa, uning hajmiga 0,5 qo'llaniladimi? Ta'siri: MTT hajmi × BHM × Kz × Kj × 0,5. MTT hajmi namunada alohida berilmagan.
8. **Avtoturargoh binosi va Ha.** KK-1 obyekti to'liq avtoturargoh binosi. Yig'im butun hajmga olingan, ma'lumot varag'ida Ha bo'sh. Qaror Ha ni "binoning (inshootning) avtoturargoh qismi umumiy hajmi" deb ta'riflaydi va formulada ayiradi. Alohida avtoturargoh binosida butun hajm Ha hisoblanadimi? A: ha, Ti = 0. B: yo'q, Ha faqat aralash binodagi avtoturargoh qismi, KK-1 dagidek 584 837 550,00.
9. **Qo'shimcha kelishuvda BHM sanasi.** 5.1-band: kelishuv imzolanadigan kun. KK-1: investor xati sanasi. KK-1 da ikkala sanada BHM 440 000, shuning uchun farq yo'q. BHM ular orasida o'zgarsa farq paydo bo'ladi: 648,54 m³ va K 0,5 uchun 412 000 va 440 000 orasida 9 079 560,00. [shartnoma-hujjatlari.md](shartnoma-hujjatlari.md), Q-17.
10. **Hajm kamayishi va koeffitsiyent o'zgarishi.** Kelishuvda hajm kamaysa yoki Kengash qarori bilan K o'zgarsa, qayta hisob qanday bo'ladi? [shartnoma-hujjatlari.md](shartnoma-hujjatlari.md), Q-20.
11. **KK-1 da zona.** 2.1-banddagi jami qatorda 5-zona, qo'shilgan qismda 2-zona. 2-zonada K = 0,5 faqat Kt × Ko × Kj ≤ 0,4 bo'lganda chiqadi. [shartnoma-hujjatlari.md](shartnoma-hujjatlari.md), Q-23.
