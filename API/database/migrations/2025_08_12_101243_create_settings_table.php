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
        Schema::create('settings', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('name');
            $table->string('key');
            $table->enum('type', ['string', 'int', 'bool', 'decimal', 'date', 'time', 'json', 'enum'])->default('string');
            $table->json('enum_value')->nullable();
            $table->text('value');
            $table->string('scope_type')->nullable();
            $table->timestamps();

            $table->unique(['key']);
            $table->index(['key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
