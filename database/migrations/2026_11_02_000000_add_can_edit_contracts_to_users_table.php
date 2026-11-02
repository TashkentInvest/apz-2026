<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Shartnomani tahrirlash ruxsati (KAMCHILIKLAR C1).
     * role ustuni SSO kirishida qayta yoziladi, shuning uchun ruxsat alohida ustunda saqlanadi
     * va faqat admin panelida o'zgartiriladi.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_edit_contracts')->default(false)->after('status');
        });

        // Avvalgi qoida bo'yicha tahrirlay olgan yagona hisob huquqini saqlab qoladi
        DB::table('users')
            ->whereRaw('LOWER(TRIM(email)) = ?', ['superadmin@example.com'])
            ->whereRaw('LOWER(TRIM(name)) = ?', ['administrator'])
            ->update(['can_edit_contracts' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('can_edit_contracts');
        });
    }
};
