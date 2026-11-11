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
        Schema::create('employees', function (Blueprint $table) {
            $table->mediumIncrements('id');
            $table->string('code')->nullable();

            $table->unsignedInteger('branch_id');
            $table->foreign('branch_id')
                ->references('id')
                ->on('branches')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->string('name_kh');
            $table->string('name_en')->nullable();

            $table->unsignedTinyInteger('department_id')->nullable();
            $table->unsignedTinyInteger('position_id')->nullable();

            $table->enum('gender', ['male', 'female'])->default('male');
            $table->date('dob')->nullable();

            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->default('single');
            $table->unsignedTinyInteger('dependent_children')->default(0);
            $table->boolean('spouse_is_dependent')->default(false);

            $table->string('contact')->nullable();
            $table->string('parent_contact')->nullable();

            $table->string('national_id_number')->nullable();
            $table->date('national_id_issue_date')->nullable();

            $table->string('bank_account_number')->nullable();

            $table->string('house_no')->nullable();
            $table->string('street')->nullable();
            $table->integer('village_code')->nullable();

            $table->date('hired_date')->nullable();
            $table->date('terminated_date')->nullable();
            $table->date('last_working_date')->nullable();

            $table->enum('employee_type', ['full-time', 'part-time', 'shift-worker', 'freelance'])->default('full-time');
            $table->enum('contract_type', ['FDC', 'UDC'])->default('FDC');
            $table->tinyInteger('work_period_id')->nullable();
            $table->date('probation_end_date')->nullable();

            $table->string('work_email')->unique()->nullable();

            $table->unsignedMediumInteger('under_employee_id')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_path_document')->nullable();

            $table->boolean('is_home_visited')->default(false);
            $table->date('home_visited_date')->nullable();

            $table->enum('termination_type', [
                'resignation',
                'dismissal',
                'end_of_contract',
                'retirement',
                'mutual_agreement',
                'other',
            ])->nullable();
            $table->text('termination_reason')->nullable();

            $table->boolean('is_active')->default(true);

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
        Schema::dropIfExists('employees');
    }
};
