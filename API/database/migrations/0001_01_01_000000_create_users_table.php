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
        Schema::create('users', function (Blueprint $table) {
            $table->mediumIncrements('id');
            $table->string('code')->unique()->nullable();
            $table->index('code');

            $table->unsignedInteger('branch_id');
            $table->foreign('branch_id')
                ->references('id')
                ->on('branches')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->index('branch_id');

            $table->tinyInteger('manage_branch')
                ->default(1)
                ->comment('1:one branch, 2:multiple branch, 3:all branch, 4:exclude branch');
            $table->index('manage_branch');

            $table->string('name_kh');
            $table->string('name_en')->nullable();
            $table->string('username')->unique();
            $table->string('email')->unique()->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_super')->default(false);

            $table->unsignedMediumInteger('under_user_id')->nullable();
            $table->foreign('under_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->index('under_user_id');

            $table->unsignedMediumInteger('employee_id')->nullable();

            $table->rememberToken();

            $table->unsignedTinyInteger('role_id');
            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->index('role_id');

            $table->unsignedSmallInteger('position_id')->nullable();
            $table->foreign('position_id')
                ->references('id')
                ->on('positions')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->index('position_id');

            $table->enum('gender', ['male', 'female'])->default('male')->nullable();
            $table->date('dob')->nullable();
            $table->string('contact')->nullable();

            $table->string('national_id_number')->nullable();
            $table->date('national_id_issue_date')->nullable();

            $table->date('join_date')->nullable();
            $table->integer('village_code')->nullable();

            $table->enum('default_part', ['hr', 'admin'])->default('admin');
            $table->string('image_path')->nullable();
            $table->boolean('is_reset_password')->default(false);

            $table->enum('type', ['main', 'sub'])->default('main');

            $table->unsignedMediumInteger('parent_user_id')->nullable();

            $table->timestamp('last_login')->nullable();

            $table->unsignedMediumInteger('created_by')->nullable();
            $table->unsignedMediumInteger('updated_by')->nullable();
            $table->unsignedMediumInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
