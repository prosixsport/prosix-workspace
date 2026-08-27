<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Make password nullable
        |--------------------------------------------------------------------------
        | Admin-added client ka password pehli login par save hoga.
        */

        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
        });

        /*
        |--------------------------------------------------------------------------
        | Client information fields
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasColumn('users', 'phone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('phone')->nullable()->after('email');
            });
        }

        if (!Schema::hasColumn('users', 'company')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('company')->nullable()->after('phone');
            });
        }

        if (!Schema::hasColumn('users', 'address')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('address')->nullable()->after('company');
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Account approval fields
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasColumn('users', 'account_status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('account_status')
                    ->default('active')
                    ->after('role');
            });
        }

        if (!Schema::hasColumn('users', 'registration_source')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('registration_source')
                    ->default('admin')
                    ->after('account_status');
            });
        }

        if (!Schema::hasColumn('users', 'approved_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('approved_at')
                    ->nullable()
                    ->after('registration_source');
            });
        }

        if (!Schema::hasColumn('users', 'approved_by')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('approved_by')
                    ->nullable()
                    ->after('approved_at');

                $table->foreign('approved_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Remove foreign key first
        |--------------------------------------------------------------------------
        */

        if (Schema::hasColumn('users', 'approved_by')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['approved_by']);
                $table->dropColumn('approved_by');
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Remove only approval-system fields
        |--------------------------------------------------------------------------
        | phone, company aur address ko remove nahi karna, kyun ke ye columns
        | migration se pehle bhi table mein mojood ho sakte hain.
        */

        $columns = [];

        if (Schema::hasColumn('users', 'account_status')) {
            $columns[] = 'account_status';
        }

        if (Schema::hasColumn('users', 'registration_source')) {
            $columns[] = 'registration_source';
        }

        if (Schema::hasColumn('users', 'approved_at')) {
            $columns[] = 'approved_at';
        }

        if (!empty($columns)) {
            Schema::table('users', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Restore password as required
        |--------------------------------------------------------------------------
        | Null password wale users hon to rollback fail hoga. Isliye rollback se
        | pehle un clients ko password dena zaroori hoga.
        */

        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable(false)->change();
        });
    }
};
