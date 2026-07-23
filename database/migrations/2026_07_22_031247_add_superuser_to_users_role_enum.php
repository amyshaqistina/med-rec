<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ROLES_WITH_SUPERUSER = "'technician','pharmacist','physician','nurse','manager','admin','superuser'";

    private const ROLES_WITHOUT_SUPERUSER = "'technician','pharmacist','physician','nurse','manager','admin'";

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            // MODIFY preserves existing row values; SQLite (used in tests) always
            // starts from an empty, freshly migrated database so a drop/recreate is safe.
            DB::statement('ALTER TABLE users MODIFY COLUMN role ENUM('.self::ROLES_WITH_SUPERUSER.") NOT NULL DEFAULT 'technician'");

            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['technician', 'pharmacist', 'physician', 'nurse', 'manager', 'admin', 'superuser'])
                ->default('technician')
                ->after('email');
            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE users MODIFY COLUMN role ENUM('.self::ROLES_WITHOUT_SUPERUSER.") NOT NULL DEFAULT 'technician'");

            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['technician', 'pharmacist', 'physician', 'nurse', 'manager', 'admin'])
                ->default('technician')
                ->after('email');
            $table->index('role');
        });
    }
};
