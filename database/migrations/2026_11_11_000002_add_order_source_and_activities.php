<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Where the order came from: web (public form) or added by an admin (whatsapp, instagram, ...).
            $table->string('source', 20)->default('web')->after('status');
            // Orders added by an admin may not have an email; WhatsApp stays required for tracking verification.
            $table->string('client_email')->nullable()->change();
        });

        Schema::create('order_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 40);
            $table->string('description');
            $table->json('changes')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_activities');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('source');
            $table->string('client_email')->nullable(false)->change();
        });
    }
};
