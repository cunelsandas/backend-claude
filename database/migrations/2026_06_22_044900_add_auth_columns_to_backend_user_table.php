<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds Laravel auth columns to the legacy `backend_user` table.
 *
 * - `password_hash`  : bcrypt hash, written on first successful login from
 *                     the legacy plain-text `hr_employee.employee_password`.
 * - `remember_token` : standard Laravel "remember me" token.
 *
 * Both are nullable so legacy code that writes to `backend_user`
 * (without knowing about these columns) keeps working.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backend_user', function (Blueprint $table) {
            if (! Schema::hasColumn('backend_user', 'password_hash')) {
                $table->string('password_hash', 255)->nullable()->after('token');
            }
            if (! Schema::hasColumn('backend_user', 'remember_token')) {
                $table->string('remember_token', 100)->nullable()->after('password_hash');
            }
        });
    }

    public function down(): void
    {
        Schema::table('backend_user', function (Blueprint $table) {
            $table->dropColumn(['password_hash', 'remember_token']);
        });
    }
};
