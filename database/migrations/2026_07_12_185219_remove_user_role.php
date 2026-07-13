<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Remove the generic 'user' role.
     *
     * The app no longer has a catch-all role — every account is now one of
     * admin, manager, receptionist or orderpicker. Unlike the earlier
     * preparer -> orderpicker rename, there is no direct replacement here:
     * any account that only had 'user' simply ends up with no role at all
     * and should be assigned a real one manually via /admin/users.
     */
    public function up(): void
    {
        $user = Role::where('name', 'user')->where('guard_name', 'web')->first();

        if (! $user) {
            return;
        }

        // Drop the pivot rows instead of leaving them dangling — the role
        // itself is about to be deleted, so these rows would be orphaned.
        DB::table('model_has_roles')->where('role_id', $user->id)->delete();

        $user->delete();

        // Clear Spatie's cached role/permission list so it doesn't keep
        // showing 'user' until the cache naturally expires.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migration.
     * Not meaningfully reversible — we don't know which accounts used to
     * have the 'user' role.
     */
    public function down(): void
    {
        //
    }
};
