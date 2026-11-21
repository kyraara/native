<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Per-service order fields (options, prices, conditional visibility). Null = generic form only.
            $table->json('form_fields')->nullable()->after('base_price');
            // Cheapest valid combination of form_fields, recomputed on save; shown as "Mulai Rp …" in the catalog.
            $table->decimal('starting_price', 12, 2)->nullable()->after('form_fields');
        });

        Schema::table('orders', function (Blueprint $table) {
            // Snapshot of the answers + estimate breakdown at order time, so later price edits never rewrite history.
            $table->json('service_details')->nullable()->after('description');
            // "Estimasi mulai dari" — always recomputed server-side. Never the agreed price; that is price_final.
            $table->decimal('price_estimate', 12, 2)->nullable()->after('budget');
            $table->timestamp('price_finalized_at')->nullable()->after('price_final');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['service_details', 'price_estimate', 'price_finalized_at']);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['form_fields', 'starting_price']);
        });
    }
};
