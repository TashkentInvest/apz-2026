# E-IMZO Laravel Authentication Demo

Laravel loyihasi E-IMZO elektron raqamli imzo orqali autentifikatsiya va hujjatlarni imzolash.

## Talablar

- PHP 8.1+
- Composer
- E-IMZO dasturi (https://e-imzo.uz) - kompyuteringizda o'rnatilgan bo'lishi kerak
- E-IMZO Server (ixtiyoriy - imzoni tekshirish uchun)

## O'rnatish

```bash
cd e-imzo-app
composer install
php artisan migrate
php artisan db:seed
```

## Ishga tushirish

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Brauzerda ochish: http://127.0.0.1:8000

## Funksiyalar

### 1. E-IMZO bilan kirish
- ERI kalitini tanlash (PFX fayl yoki USB token)
- Challenge imzolash orqali autentifikatsiya
- Avtomatik ro'yxatdan o'tish (yangi foydalanuvchi)

### 2. Hujjatlar
- Yangi hujjat yaratish
- Hujjatni E-IMZO bilan imzolash
- QR kod orqali hujjatni tekshirish

### 3. QR kod tekshirish
- Har bir hujjatga noyob QR kod
- QR kodni skanerlash orqali hujjat ma'lumotlarini ko'rish
- Imzo ma'lumotlarini tekshirish

## E-IMZO Server

Imzoni to'liq tekshirish uchun E-IMZO Server kerak:

```bash
java -Dfile.encoding=UTF-8 -jar e-imzo-server.jar config.properties
```

Server http://127.0.0.1:8080 da ishlaydi.

## Konfiguratsiya

`.env` faylida:

```
EIMZO_SERVER_URL=http://127.0.0.1:8080
```

## API Endpoints

| Method | URL | Tavsif |
|--------|-----|--------|
| GET | /login | Kirish sahifasi |
| GET | /frontend/challenge | Challenge olish |
| POST | /eimzo/authenticate | E-IMZO autentifikatsiya |
| GET | /documents | Hujjatlar ro'yxati |
| POST | /documents | Yangi hujjat |
| POST | /documents/{id}/sign | Hujjatni imzolash |
| GET | /verify/{qrCode} | QR kod tekshirish |

## Texnologiyalar

- Laravel 10
- Bootstrap 5
- E-IMZO JavaScript API
- SQLite (development)
- QR Code Generator

## IMPORT CSV
```
# Recommended — bypass PHP CLI memory limit entirely:
php -d memory_limit=512M artisan transactions:import --fresh

# Or re-seed via seeder:
php -d memory_limit=512M artisan db:seed --class=TransactionsSeeder
```

```
php artisan migrate
a
# Payments-only seeding (keeps existing contracts/schedules; creates only missing contracts from fakt_apz.csv)
php artisan db:seed

# Explicit class (PowerShell-safe)
php artisan db:seed --class='Database\Seeders\DatabaseSeeder'

# Or run importer directly
php artisan apz:import-payments --fresh
```

## EXPORT CSV (dataset-apz)

```
# Export both files to storage/dataset-apz/
php artisan apz:export-datasets

# Export with filters
php artisan apz:export-datasets --district="Olmazor" --month="Апрель" --year=2024
```

Generated files:
- `storage/dataset-apz/fakt-apz.csv`
- `storage/dataset-apz/grafik_apz.csv`

## fact payment seeding
```
 php artisan db:seed --class='Database\Seeders\DatabaseSeeder'
```
    

```
php artisan db:seed --class=ApzPaymentsSeeder - only payments
php artisan db:seed --class=ApzScheduleSeeder - only schedules
```

```
php artisan make:admin --name="Administrator" --email="superadmin@example.com" --password="teamdevs"

```
