<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql'; // DB central

    public function up(): void
    {
        Schema::connection('pgsql')->create('pool_migrations_log', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->string('from_pool');
            $table->string('to_pool');
            $table->string('reason')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('pool_migrations_log');
    }
};