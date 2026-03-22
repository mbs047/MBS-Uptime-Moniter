<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('remote_integrations', function (Blueprint $table): void {
            $table->boolean('tls_verify')
                ->default(true)
                ->after('auth_secret');
            $table->string('tls_ca_path')
                ->nullable()
                ->after('tls_verify');
        });
    }

    public function down(): void
    {
        Schema::table('remote_integrations', function (Blueprint $table): void {
            $table->dropColumn([
                'tls_verify',
                'tls_ca_path',
            ]);
        });
    }
};
