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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_token', 20)->unique();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('client_name');
            $table->string('client_email');
            $table->string('client_phone');
            $table->string('title');
            $table->text('description');
            $table->date('deadline');
            $table->string('budget')->nullable();
            $table->string('attachment_path')->nullable();
            $table->enum('status', ['pending', 'processing', 'review', 'done', 'cancelled'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->decimal('price_final', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
