<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_header_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('line_no');
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->text('description');
            $table->decimal('quantity', 18, 2)->default(0);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('line_amount', 18, 2)->default(0);
            $table->timestamps();

            $table->unique(['sales_header_id', 'line_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_lines');
    }
};
