<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('territory_assignment_audits', function (Blueprint $table) {
            $table->id();
            $table->string('action', 20);
            $table->string('role_type', 20);
            $table->string('state_code', 2);
            $table->string('region', 50)->nullable();

            $table->foreignId('sales_team_member_id')->nullable()->constrained()->nullOnDelete();
            $table->string('member_name', 100)->nullable();

            $table->foreignId('previous_member_id')->nullable()->constrained('sales_team_members')->nullOnDelete();
            $table->string('previous_member_name', 100)->nullable();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name', 100)->nullable();

            $table->timestamps();

            $table->index(['role_type', 'state_code']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('territory_assignment_audits');
    }
};
