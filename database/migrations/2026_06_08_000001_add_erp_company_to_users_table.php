<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'erp_company')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('erp_company', 20)->default('darwin')->after('organization');
            });
        }

        DB::table('users')
            ->where(function ($q) {
                $q->where('organization', 'like', '%Гудвин%')
                  ->orWhere('organization', 'like', '%Goodwin%')
                  ->orWhere('group', 'like', '%Гудв%')
                  ->orWhere('group', 'like', '%Goodw%');
            })
            ->update(['erp_company' => 'goodwin']);

        DB::table('users')
            ->where(function ($q) {
                $q->whereNull('erp_company')->orWhere('erp_company', '');
            })
            ->update(['erp_company' => 'darwin']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'erp_company')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('erp_company');
            });
        }
    }
};
