<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('territory_assignments', function (Blueprint $table) {
            $table->string('split_direction', 20)->nullable()->after('region');
            $table->unsignedSmallInteger('split_order')->nullable()->after('split_direction');
            $table->unsignedSmallInteger('split_percent')->nullable()->after('split_order');
        });
    }

    public function down(): void
    {
        Schema::table('territory_assignments', function (Blueprint $table) {
            $table->dropColumn(['split_direction', 'split_order', 'split_percent']);
        });
    }
};
