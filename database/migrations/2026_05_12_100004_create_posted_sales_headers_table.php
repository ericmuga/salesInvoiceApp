<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posted_sales_headers', function (Blueprint $table) {
            $table->id();
            $table->string('posted_invoice_no')->unique();
            $table->string('source_invoice_no')->index();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('customer_no');
            $table->string('customer_name');
            $table->date('invoice_date');
            $table->date('posting_date');
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posted_sales_headers');
    }
};
