<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('apz_contracts', function (Blueprint $table) {
            $table->string('demand_letter_number', 100)->nullable()->after('installments_count');
            $table->date('demand_letter_date')->nullable()->after('demand_letter_number');
        });

        Schema::create('apz_contract_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('contract_id');
            $table->string('file_role', 20);
            $table->string('stored_path', 500);
            $table->string('original_name', 255);
            $table->string('mime', 120)->nullable();
            $table->unsignedInteger('size')->default(0);
            $table->timestamps();

            $table->index('contract_id');
            $table->index(['contract_id', 'file_role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apz_contract_attachments');

        Schema::table('apz_contracts', function (Blueprint $table) {
            $table->dropColumn(['demand_letter_number', 'demand_letter_date']);
        });
    }
};
