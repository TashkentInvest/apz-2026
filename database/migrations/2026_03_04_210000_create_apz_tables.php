<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── APZ Contracts (grafik_apz.csv) ──────────────────────────────────
        Schema::create('apz_contracts', function (Blueprint $table) {
            $table->id();
            $table->integer('contract_id')->nullable();          // ID
            $table->string('district', 100)->nullable();         // Туман
            $table->string('mfy', 255)->nullable();              // МФЙ
            $table->text('address')->nullable();                 // манзил тўлиқ
            $table->decimal('build_volume', 18, 2)->nullable();  // Қурилиш ҳажми (метр.куп)
            $table->string('coefficient', 50)->nullable();       // Коэффицент
            $table->string('zone', 50)->nullable();              // Зона
            $table->string('permit', 100)->nullable();           // Рухсатнома
            $table->string('apz_number', 100)->nullable();       // АПЗ номер
            $table->string('council_decision', 255)->nullable(); // Кенгаш хулосаси
            $table->string('expertise', 255)->nullable();        // Йиғма экспертиза хулосаси
            $table->string('construction_issues', 255)->nullable(); // қурилишда муамолари ҳолати
            $table->string('object_type', 255)->nullable();      // Объект тури
            $table->string('client_type', 255)->nullable();      // БУЮРТМАЧИ ТУРИ
            $table->string('investor_name', 255)->nullable();    // Инвестор номи
            $table->string('inn', 50)->nullable();               // ИНН/ПИНФЛ
            $table->string('phone', 50)->nullable();             // телефон номер
            $table->text('investor_address')->nullable();        // инвестор манзили
            $table->string('contract_number', 100)->nullable();  // шартнома номер
            $table->date('contract_date')->nullable();           // шартнома санаси
            $table->string('contract_status', 100)->nullable();  // Шартнома ҳолати
            $table->decimal('contract_value', 20, 2)->nullable(); // Шартнома қиймати
            $table->string('payment_terms', 50)->nullable();     // Тўлов шарти
            $table->integer('installments_count')->nullable();   // reja-jadval
            // Monthly payment schedule stored as JSON (columns 24-104: date => amount)
            $table->json('payment_schedule')->nullable();
            $table->timestamps();

            $table->index('district');
            $table->index('contract_status');
            $table->index('contract_number');
            $table->index('inn');
        });

        // ── APZ Payments (fakt_apz.csv) ─────────────────────────────────────
        Schema::create('apz_payments', function (Blueprint $table) {
            $table->id();
            $table->date('payment_date')->nullable();            // Дата
            $table->integer('contract_id')->nullable();          // ID
            $table->string('inn', 50)->nullable();               // ИНН
            $table->decimal('debit_amount', 20, 2)->default(0);  // Сумма дебет
            $table->decimal('credit_amount', 20, 2)->default(0); // Сумма кредит
            $table->text('payment_purpose')->nullable();         // Назначение платежа
            $table->string('flow', 20)->nullable();              // Поток (Приход/Расход)
            $table->string('month', 50)->nullable();             // Месяц
            $table->decimal('amount', 20, 2)->default(0);        // Cумма
            $table->string('district', 100)->nullable();         // Район
            $table->string('type', 100)->nullable();             // Тип (АПЗ тўлови, Пеня тўлови...)
            $table->integer('year')->nullable();                 // ГОД
            $table->string('company_name', 255)->nullable();     // Корхона номи
            $table->timestamps();

            $table->index('payment_date');
            $table->index('district');
            $table->index('year');
            $table->index('month');
            $table->index('type');
            $table->index('flow');
            $table->index('contract_id');
            $table->index('inn');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apz_payments');
        Schema::dropIfExists('apz_contracts');
    }
};
