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
        Schema::create('positions', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('name_kh');
            $table->string('name_en');
            $table->string('abbr')->unique();
            $table->tinyInteger('level')->unsigned()->default(1);

            $table->unsignedTinyInteger('department_id')->default(1);
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');

            $table->boolean('is_leader')->default(false);
            $table->boolean('is_member')->default(false);

            $table->decimal('insurance_amount', 8, 2)->default(10000);
            $table->decimal('position_fee', 12, 2)->default(0);
            $table->boolean('is_pension_fund')->default(true);


            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
