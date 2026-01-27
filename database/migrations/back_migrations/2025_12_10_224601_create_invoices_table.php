<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique(); // INV-...
            $table->unsignedBigInteger('student_id');
            $table->string('description')->nullable();
            $table->integer('amount'); // in smallest currency unit (e.g. IDR)
            $table->enum('status', ['UNPAID','PENDING','PAID','EXPIRED'])->default('UNPAID');
            $table->string('snap_token')->nullable();
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
