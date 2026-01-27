<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('walimurid_profiles', function (Blueprint $table) {
            $table->string('shortname')->nullable()->after('fullname');
            $table->string('call_name')->nullable()->after('shortname');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('walimurid_profiles', function (Blueprint $table) {
            //
        });
    }
};
