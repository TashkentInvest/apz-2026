# Spetsifikatsiya: ART qo'shimcha yig'imini hisoblash

**Manba:** Xalq deputatlari Toshkent shahar Kengashining 2024-yil 2-iyuldagi VI-104-94-14-0-K/24-son qarori, 2-ilova ("Mexanizm"). Matn: [qaror-VI-104-94-14-0-K24.txt](../materials/qaror-VI-104-94-14-0-K24.txt).
**Amal qilish:** 2024-yil 25-martdan boshlab yuzaga kelgan huquqiy munosabatlarga (qaror 3-band).
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
- **Qo'shimcha kelishuv:** hajm oshganda qo'shimcha yig'im kelishuv imzolanadigan kundagi BHM bo'yicha hisoblanadi. Manba: shartnoma shabloni, 5.1-band.
- **BHM tarixi** tizimda alohida jadvalda saqlanadi: `amal_boshlanish_sanasi` va `qiymat`. Kod ichiga yozilmaydi.

| Amal qilish boshlanishi | BHM, so'm | Holat |
|---|---|---|
| 2023-06-01 | 340 000 | ma'lumotlar bilan tasdiqlangan |
| 2024-10-01 | 375 000 | rasmiy manba bilan tasdiqlash kerak |
| 2025-08-01 | 412 000 | rasmiy manba bilan tasdiqlash kerak |

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

## 7. Yaxlitlash va saqlash

- Pul summalari `DECIMAL(20,2)` turida saqlanadi. Hisoblashda `float` emas, BCMath yoki butun tiyin ishlatiladi.
- Har bir hisoblash "snapshot" sifatida saqlanadi: barcha kirish qiymatlari, tanlangan koeffitsiyent kodlari, BHM qiymati va sanasi, natija va qaror versiyasi. Keyin qaror yoki BHM o'zgarsa ham eski shartnoma qiymati qayta hisoblanmaydi.
- Qaror har chorakda o'zgarishi mumkin. Koeffitsiyent jadvallari qaror versiyasi va amal qilish sanasi bilan saqlanadi.

## 8. Ochiq savollar

Bu savollarga huquqshunos javob berishi kerak.

1. **Kj qoidasi.** Qarorga ko'ra 0,6 metrodan 200 m radiusdan **tashqaridagi** obyektlarga qo'llaniladi. Shartnoma shabloni 2.1-bandida esa 0,6 radius **ichidagi** hududlar uchun yozilgan. Qaysi biri to'g'ri?
2. **Ko jadvali.** Qaror "1–5-qatorlarda ko'rsatilmagan" deydi, lekin jadvalda faqat 4 ta qator bor. Beshinchi toifa tushib qolganmi?
3. **BHM tarixi.** 375 000 va 412 000 qiymatlarining amal qilish sanalarini rasmiy manba bilan tasdiqlash kerak.
4. **Nomuvofiq shartnomalar.** 50 ta shartnoma uchun sabab nima: hajm ayirmalari, boshqa sanadagi BHM yoki kiritishdagi xato?
5. **Ish kunlari.** "3 ish kuni" va "10 ish kuni" muddatlari uchun rasmiy bayram kunlari kalendari kerak.
