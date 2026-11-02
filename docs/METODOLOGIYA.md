# Ish metodologiyasi: APZ-2026 ni rivojlantirish

## 1. Asosiy tamoyillar

Loyihada pul hisob-kitobi va yuridik hujjatlar bor. Shuning uchun har bir o'zgarish quyidagi zanjirdan o'tadi:

```
Manba hujjat → Spetsifikatsiya → Test → Kod → Tekshiruv → Joylash
(qaror, shablon)  (docs/spec)    (etalon misollar)       (spec + real data)  (art2)
```

1. **Spetsifikatsiya birinchi.** Kod faqat `docs/spec/` dagi spetsifikatsiyaga asoslanadi. Spetsifikatsiyaning har bir qoidasi manba hujjatning aniq bandiga havola beradi.
2. **Test koddan oldin.** Hisoblash qoidalari uchun etalon misollar avval test sifatida yoziladi. Etalonlar qarordagi jadvallar va haqiqiy shartnomalardan olinadi.
3. **Ma'lumotnomalar kodda emas.** BHM, koeffitsiyentlar, zonalar, rekvizitlar va imzolovchilar bazadagi versiyalangan ma'lumotnomalarda saqlanadi.
4. **Hujjat o'zgarmaydi.** Imzolangan shartnoma tahrirlanmaydi. O'zgarish faqat qo'shimcha kelishuv orqali kiritiladi va har bir harakat audit jurnaliga yoziladi.
5. **Noaniqlik taxmin bilan yopilmaydi.** Huquqiy noaniqlik spetsifikatsiyaning "Ochiq savollar" bo'limiga yoziladi va mas'ul shaxs javobini kutadi.

## 2. Agentlar va ularning vazifasi

Agentlar `.claude/agents/` papkasida joylashgan. Claude Code ularni vazifaga qarab avtomatik chaqiradi, yoki ularni nomi bilan chaqirish mumkin.

| Agent | Vazifasi | Nimani o'zgartiradi |
|---|---|---|
| `apz-domain-analyst` | Qaror, shablon va yangi materiallarni tahlil qiladi. Spetsifikatsiya va ochiq savollarni yozadi | Faqat `docs/` |
| `apz-fee-engineer` | Yig'im formulasi, koeffitsiyentlar, BHM tarixi, reja-jadval va penya hisobini yozadi | Hisoblash xizmatlari, ularning testlari, ma'lumotnoma migratsiyalari |
| `apz-document-engineer` | Shartnoma, qo'shimcha kelishuv, talabnoma va dalolatnomani shablondan yaratadi. Raqamlash va E-IMZO bilan imzolash ham shu agentda | Hujjat xizmatlari, shablonlar, hujjat ekranlari |
| `apz-laravel-engineer` | Arxitektura, migratsiyalar, ruxsatlar, audit jurnali, nazoratchini bo'lish va ma'lumotlarni tozalash | Asosiy ilova kodi |
| `apz-qa-reviewer` | O'zgarishni spetsifikatsiya va haqiqiy ma'lumotlar bilan solishtiradi, testlarni ishga tushiradi | Hech narsani o'zgartirmaydi, faqat hisobot beradi |
| `apz-deployer` | art2.toshkentinvest.uz serveriga xavfsiz joylaydi va tekshiradi | Faqat server |

**Ish ketma-ketligi:**

1. Yangi talab yoki material kelsa, `apz-domain-analyst` spetsifikatsiyani yangilaydi.
2. Ochiq savollar yopilgach, `apz-fee-engineer` yoki `apz-document-engineer` testlar bilan birga kod yozadi. Umumiy infratuzilma kerak bo'lsa, avval `apz-laravel-engineer` uni tayyorlaydi.
3. `apz-qa-reviewer` natijani tekshiradi. Kamchilik topilsa, ish muallif agentga qaytariladi.
4. Foydalanuvchi tasdiqlagach, `apz-deployer` serverga joylaydi.

## 3. "Tayyor" mezoni

O'zgarish quyidagi shartlarning hammasi bajarilganda tayyor hisoblanadi:

- spetsifikatsiyaga havola bor, ochiq savollar qolmagan;
- etalon testlar o'tadi, mavjud testlar buzilmagan;
- pul summalari BCMath yoki butun tiyinda hisoblanadi, `float` ishlatilmaydi;
- migratsiya `down()` bilan qaytariladi va production ma'lumotlarini o'chirmaydi;
- ruxsatlar rol orqali tekshiriladi, email yoki ism bilan emas;
- `apz-qa-reviewer` hisobotida jiddiy kamchilik yo'q;
- interfeys matnlari o'zbek tilida, mavjud ekranlar bilan bir xil yozuvda.

## 4. Yo'l xaritasi

Kamchiliklar ro'yxati: [KAMCHILIKLAR.md](KAMCHILIKLAR.md).

| Bosqich | Maqsad | Asosiy ishlar | Bog'liqlik |
|---|---|---|---|
| 0. Poydevor | Xavfsiz va testlanadigan asos | Rollar va ruxsatlar (C1), audit jurnali (C4), holat enumi (B4), MySQL test bazasi va birinchi testlar (D2), ochiq repodagi maxfiy ma'lumotlar (C2, C3) | — |
| 1. Yig'im kalkulyatori | Ma'lumot kiritilganda yig'im avtomatik hisoblansin | Ma'lumotnomalar: BHM, koeffitsiyentlar, zonalar. Sof hisoblash xizmati va etalon testlar. Kiritish formasi va jonli hisob. Hisob snapshot'i. 50 ta nomuvofiq shartnomani ko'rib chiqish | 0 |
| 2. Elektron shartnoma | Shartnoma tizimda tuzilsin | Investor va obyekt kartochkasi. Shablon tanlash. DOCX yoki PDF yaratish. Raqamlash. Reja-jadvalni avtomatik tuzish. Summani so'z bilan yozish | 1 |
| 3. Qo'shimcha kelishuv | Shartnoma versiyalari | Kelishuv obyekti, qayta hisoblash, yangi BHM bo'yicha farq, yangi jadval, versiyalar tarixi | 2 |
| 4. Talabnoma va dalolatnoma | Muddat buzilishini avtomatik kuzatish | Kechikishni aniqlash, talabnoma yaratish, 3 ish kunlik muddatni kuzatish, penya, dalolatnoma, bekor qilish va sudga oshirish | 2 |
| 5. Elektron imzo | Hujjatlarni E-IMZO bilan imzolash | PDF xeshini imzolash, imzoni tekshirish, QR kod va tekshirish sahifasi | 2 |

## 5. Foydalanuvchidan kerakli materiallar

Quyidagi materiallarsiz tegishli bosqichni boshlab bo'lmaydi:

| # | Material | Kerak bo'ladigan bosqich |
|---|---|---|
| 1 | Kj qoidasining huquqiy talqini: 0,6 metrodan 200 m ichkarida qo'llaniladimi yoki tashqarida? | 1 |
| 2 | Penya chegarasining talqini: 15% kechiktirilgan summadanmi yoki shartnoma qiymatidanmi? | 4 |
| 3 | BHM tarixi, rasmiy sanalari bilan | 1 |
| 4 | Hududiy zonalar ro'yxati, tuman, mahalla va ko'cha bo'yicha, hokim qarori asosida | 1 |
| 5 | 50 ta nomuvofiq shartnoma bo'yicha izoh | 1 |
| 6 | Shartnoma raqamlash qoidasi: `ART-2026-А-___-______` ning har bir qismi nimani bildiradi. 2026-11-02: tuman kodi va tartib raqami ma'lumotlardan aniqlandi, harfning ma'nosi ochiq | 2 |
| 7 | Qo'shimcha kelishuv, talabnoma, dalolatnoma va bekor qilish xati shablonlari. 2026-11-02: yuridik shaxs uchun bitta talabnoma namunasi olindi, qolganlari yo'q | 3, 4 |
| 8 | Ish kunlari kalendari: rasmiy bayram va qo'shimcha dam olish kunlari | 2, 4 |
| 9 | Investor hujjatni qanday imzolashi: tizimda E-IMZO bilan yoki qog'ozda | 5 |
| 10 | Bir yo'la to'lanadigan, ishonchnoma bo'yicha jismoniy shaxs shabloni kerakmi | 2 |

## 6. Materiallar qayerda saqlanadi

- **`docs/materials/`** — ochiq hujjatlar, masalan qaror matni. Repoga kiritiladi.
- **`docs/materials/private/`** — shartnoma shablonlari va investor ma'lumotlari bor fayllar. `.gitignore` orqali repodan chiqarilgan, chunki repo hammaga ochiq.
- **`docs/spec/`** — spetsifikatsiyalar. Agentlar kodni shular asosida yozadi.
