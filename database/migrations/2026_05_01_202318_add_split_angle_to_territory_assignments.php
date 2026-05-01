<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('territory_assignments', function (Blueprint $table) {
            $table->unsignedSmallInteger('split_angle')->nullable()->after('split_direction');
        });
    }

    public function down(): void
    {
        Schema::table('territory_assignments', function (Blueprint $table) {
            $table->dropColumn('split_angle');
        });
    }
};
