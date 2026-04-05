<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->string('id')->primary();

            // Info de la clínica
            $table->string('clinic_name');
            $table->string('legal_name')->nullable();
            $table->string('nit')->nullable();

            // Contacto
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->default('BOL');

            // Configuración
            $table->string('timezone')->default('America/La_Paz');
            $table->string('currency')->default('BOB');
            $table->string('db_pool')->default('pool_shared_1');
            $table->boolean('is_active')->default(true);
            $table->boolean('onboarding_completed')->default(false);

            // Requerido por stancl/tenancy
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};