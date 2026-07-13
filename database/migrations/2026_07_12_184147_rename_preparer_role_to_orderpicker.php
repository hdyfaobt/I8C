<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Clean up the leftover 'preparer' role.
     *
     * RoleSeeder::run() only ever calls Role::findOrCreate() — it adds
     * roles, it never removes or renames them. So when 'preparer' was
     * renamed to 'orderpicker' in the seeder file, the old 'preparer' row
     * was left behind in the database (still selectable in the account
     * creation dropdown). This migration removes it for real:
     *   1. Make sure 'orderpicker' exists.
     *   2. Move any user who already had 'preparer' over to 'orderpicker'.
     *   3. Delete the leftover 'preparer' role.
     */
    public function up(): void
    {
        $preparer = Role::where('name', 'preparer')->where('guard_name', 'web')->first();

        // Nothing to clean up — 'preparer' was never created on this
        // database (e.g. a fresh install that only ever seeded 'orderpicker').
        if (! $preparer) {
            return;
        }

        $orderpicker = Role::findOrCreate('orderpicker', 'web');

        // Re-point every user who has 'preparer' to 'orderpicker' instead of
        // just deleting the pivot rows, so nobody silently loses their role.
        $assignments = DB::table('model_has_roles')->where('role_id', $preparer->id)->get();

        foreach ($assignments as $assignment) {
            $alreadyHasOrderpicker = DB::table('model_has_roles')
                ->where('role_id', $orderpicker->id)
                ->where('model_id', $assignment->model_id)
                ->where('model_type', $assignment->model_type)
                ->exists();

            if ($alreadyHasOrderpicker) {
                // Already has the new role too — the old pivot row would be
                // a duplicate once moved, just drop it.
                DB::table('model_has_roles')
                    ->where('role_id', $preparer->id)
                    ->where('model_id', $assignment->model_id)
                    ->where('model_type', $assignment->model_type)
                    ->delete();
            } else {
                DB::table('model_has_roles')
                    ->where('role_id', $preparer->id)
                    ->where('model_id', $assignment->model_id)
                    ->where('model_type', $assignment->model_type)
                    ->update(['role_id' => $orderpicker->id]);
            }
        }

        $preparer->delete();

        // Spatie caches the role/permission list — clear it so the dropdown
        // reflects the change immediately instead of showing 'preparer'
        // until the cache naturally expires.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migration.
     * Not meaningfully reversible — we'd need to know which users were
     * originally 'preparer' before they got moved to 'orderpicker'.
     */
    public function down(): void
    {
        //
    }
};
