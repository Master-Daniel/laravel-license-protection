<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->text('license_key'); // Encrypted license key
            $table->string('domain')->nullable(); // Domain this license is bound to
            $table->string('server_ip')->nullable(); // Server IP this license is bound to
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_validated_at')->nullable();
            $table->unsignedBigInteger('validation_count')->default(0);
            $table->timestamps();

            $table->index('is_active');
            $table->index(['domain', 'server_ip']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};

