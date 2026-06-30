<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the orders table.
     * Each order belongs to a customer and is sent to Salesforce via RabbitMQ.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete(); // Link to customers table
            $table->string('product');           // Product name
            $table->integer('quantity');         // Quantity ordered
            $table->decimal('unit_price', 10, 2); // Price per unit
            $table->text('notes')->nullable();   // Optional notes from the salesperson
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending'); // Queue status
            $table->string('salesforce_id')->nullable(); // Salesforce Opportunity ID after sync
            $table->timestamps();
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
