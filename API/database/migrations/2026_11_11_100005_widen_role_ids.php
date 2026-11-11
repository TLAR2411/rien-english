<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Role ids were TINYINT (max 255). Widen them to SMALLINT (max 65 535).
 * MySQL never rolls back an id counter, so tests that create roles reached 255.
 * No data changes.
 */
return new class extends Migration
{
    /** Tables with a role_id that points to roles.id */
    private const REFERENCING = [
        'users' => 'restrict',
        'role_user' => 'cascade',
        'permission_role' => 'cascade',
    ];

    public function up(): void
    {
        $this->resize(fn (Blueprint $table, string $column) => $table->unsignedSmallInteger($column)->change(), smallId: true);
    }

    public function down(): void
    {
        $this->resize(fn (Blueprint $table, string $column) => $table->unsignedTinyInteger($column)->change(), smallId: false);
    }

    private function resize(Closure $change, bool $smallId): void
    {
        foreach (array_keys(self::REFERENCING) as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropForeign(['role_id']));
        }

        Schema::table('roles', fn (Blueprint $table) => $smallId
            ? $table->smallIncrements('id')->change()
            : $table->tinyIncrements('id')->change());

        foreach (self::REFERENCING as $name => $onDelete) {
            Schema::table($name, function (Blueprint $table) use ($change, $onDelete) {
                $change($table, 'role_id');
                $table->foreign('role_id')->references('id')->on('roles')->cascadeOnUpdate()->onDelete($onDelete);
            });
        }
    }
};
