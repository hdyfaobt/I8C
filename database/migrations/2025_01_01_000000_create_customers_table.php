<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the customers table.
     * Stores all customer records that can be synced to Salesforce.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');            // Full name of the customer
            $table->string('email')->unique(); // Email — unique per customer
            $table->string('phone')->nullable(); // Phone number (optional)
            $table->string('company')->nullable(); // Company name (optional)
            $table->text('address')->nullable(); // Delivery / billing address
            $table->string('salesforce_id')->nullable(); // Salesforce Account ID after sync
            $table->timestamps();              // created_at & updated_at
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
