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
        Schema::create('login_histories', function (Blueprint $table) {
            $table->id();

            $table->unsignedMediumInteger('user_id')->nullable();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->string('username_attempted')->nullable();
            $table->string('failure_reason')->nullable();

            $table->string('ip_address', 45);
            $table->string('country', 2)->nullable();
            $table->string('city')->nullable();
            $table->string('device_type')->nullable();
            $table->string('platform')->nullable();
            $table->string('browser')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('fingerprint_hash', 64)->nullable();
            $table->boolean('successful')->default(true);
            $table->boolean('is_new_device')->default(false);
            $table->timestamp('logged_in_at');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->timestamps();

            $table->index(['logged_in_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_histories');
    }
};
