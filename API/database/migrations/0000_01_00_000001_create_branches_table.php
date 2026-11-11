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
        Schema::create('branches', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name_kh');
            $table->string('name_en')->nullable();
            $table->char('abbr', 3);
            $table->string('contact')->nullable();
            $table->string('house_no')->nullable();
            $table->string('street')->nullable();

            $table->integer('village_code')->nullable();
            $table->integer('commune_code')->nullable();
            $table->integer('district_code')->nullable();
            $table->integer('province_code')->nullable();

            $table->string('location')->nullable();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedSmallInteger('check_in_radius_m')->default(150);
            $table->boolean('require_check_in_area')->default(true);

            $table->date('start_date')->nullable();

            $table->unsignedInteger('company_id');
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->index('company_id');
            $table->unique(['company_id', 'abbr']);

            $table->unsignedTinyInteger('region')->nullable();
            $table->boolean('is_head')->default(false)->comment('True if this branch is the head office');
            $table->boolean('is_active')->default(true)->comment('True if the branch is active');


            $table->unsignedMediumInteger('created_by')->nullable();
            $table->unsignedMediumInteger('updated_by')->nullable();
            $table->unsignedMediumInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
