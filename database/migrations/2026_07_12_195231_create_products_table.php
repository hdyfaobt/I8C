<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the products table — a simple catalog managers use to keep
     * track of what can be ordered, with a price and a unique article
     * number. Not linked to order_items (yet): orders still store their
     * own product/quantity/unit_price snapshot per line, so changing a
     * catalog price later doesn't silently change old orders.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('article_number')->unique();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
