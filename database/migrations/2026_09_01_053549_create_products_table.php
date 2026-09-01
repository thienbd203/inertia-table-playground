<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained();
            $table->string('name');
            $table->string('sku')->unique();
            $table->text('description')->nullable();
            $table->string('status')->index();
            $table->decimal('price', 12, 2);
            $table->unsignedInteger('stock');
            $table->decimal('rating', 2, 1);
            $table->boolean('featured')->default(false)->index();
            $table->date('launched_at');
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
