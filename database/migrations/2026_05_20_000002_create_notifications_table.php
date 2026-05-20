<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('idempotency_key', 255)->unique();
            $table->string('subscriber_id', 100);
            $table->enum('channel', ['sms', 'email']);
            $table->text('message');
            $table->enum('status', ['queued', 'sent', 'delivered', 'dropped'])->default('queued');
            $table->json('provider_response')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamps();
            
            $table->index('subscriber_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
