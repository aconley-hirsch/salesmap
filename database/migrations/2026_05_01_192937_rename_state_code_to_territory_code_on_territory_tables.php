<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('territory_assignments', function (Blueprint $table) {
            $table->renameColumn('state_code', 'territory_code');
        });

        Schema::table('territory_assignment_audits', function (Blueprint $table) {
            $table->renameColumn('state_code', 'territory_code');
        });

        DB::table('territory_assignments')
            ->select(['id', 'territory_code'])
            ->orderBy('id')
            ->get()
            ->each(function (object $assignment): void {
                if (strlen($assignment->territory_code) === 2) {
                    DB::table('territory_assignments')
                        ->where('id', $assignment->id)
                        ->update(['territory_code' => 'US-'.$assignment->territory_code]);
                }
            });

        DB::table('territory_assignment_audits')
            ->select(['id', 'territory_code'])
            ->orderBy('id')
            ->get()
            ->each(function (object $audit): void {
                if (strlen($audit->territory_code) === 2) {
                    DB::table('territory_assignment_audits')
                        ->where('id', $audit->id)
                        ->update(['territory_code' => 'US-'.$audit->territory_code]);
                }
            });

        Schema::table('territory_assignments', function (Blueprint $table) {
            $table->dropUnique('territory_assignments_sales_team_member_id_role_type_state_code_unique');
            $table->unique(['sales_team_member_id', 'role_type', 'territory_code'], 'territory_assignments_member_role_territory_unique');
        });

        Schema::table('territory_assignment_audits', function (Blueprint $table) {
            $table->dropIndex('territory_assignment_audits_role_type_state_code_index');
            $table->index(['role_type', 'territory_code'], 'territory_assignment_audits_role_territory_index');
        });
    }

    public function down(): void
    {
        Schema::table('territory_assignments', function (Blueprint $table) {
            $table->dropUnique('territory_assignments_member_role_territory_unique');
            $table->unique(['sales_team_member_id', 'role_type', 'territory_code'], 'territory_assignments_sales_team_member_id_role_type_state_code_unique');
        });

        Schema::table('territory_assignment_audits', function (Blueprint $table) {
            $table->dropIndex('territory_assignment_audits_role_territory_index');
            $table->index(['role_type', 'territory_code'], 'territory_assignment_audits_role_type_state_code_index');
        });

        DB::table('territory_assignments')
            ->select(['id', 'territory_code'])
            ->where('territory_code', 'like', 'US-%')
            ->orderBy('id')
            ->get()
            ->each(function (object $assignment): void {
                DB::table('territory_assignments')
                    ->where('id', $assignment->id)
                    ->update(['territory_code' => substr($assignment->territory_code, 3)]);
            });

        DB::table('territory_assignment_audits')
            ->select(['id', 'territory_code'])
            ->where('territory_code', 'like', 'US-%')
            ->orderBy('id')
            ->get()
            ->each(function (object $audit): void {
                DB::table('territory_assignment_audits')
                    ->where('id', $audit->id)
                    ->update(['territory_code' => substr($audit->territory_code, 3)]);
            });

        Schema::table('territory_assignments', function (Blueprint $table) {
            $table->renameColumn('territory_code', 'state_code');
        });

        Schema::table('territory_assignment_audits', function (Blueprint $table) {
            $table->renameColumn('territory_code', 'state_code');
        });
    }
};
