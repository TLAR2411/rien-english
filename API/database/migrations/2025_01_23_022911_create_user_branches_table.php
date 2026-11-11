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
        Schema::create('user_branches', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedMediumInteger('user_id')->nullable();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->unsignedInteger('branch_id'); // Ensure it matches branches.id type
            $table->foreign('branch_id')
                ->references('id')
                ->on('branches')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->timestamps();
            $table->index(['user_id', 'branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_branches');
    }
};
