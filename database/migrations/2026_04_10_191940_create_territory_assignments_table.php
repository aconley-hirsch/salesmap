<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('territory_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_team_member_id')->constrained()->cascadeOnDelete();
            $table->string('role_type', 20);
            $table->string('state_code', 2);
            $table->string('region', 50)->nullable();
            $table->string('color', 7);
            $table->timestamps();

            $table->unique(['sales_team_member_id', 'role_type', 'state_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('territory_assignments');
    }
};
