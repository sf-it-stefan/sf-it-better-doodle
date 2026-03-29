<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_entries', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('data');
            $table->index(['form_id', 'ip_address']);
        });
    }

    public function down(): void
    {
        Schema::table('form_entries', function (Blueprint $table) {
            $table->dropIndex(['form_id', 'ip_address']);
            $table->dropColumn('ip_address');
        });
    }
};
