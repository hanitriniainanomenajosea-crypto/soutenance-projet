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
    Schema::create('logs', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
        $table->string('action'); // ex: "Création dossier", "Modification casier"
        $table->text('description'); // ex: "L'agent A a créé le dossier URB-2026-008"
        $table->string('ip_address')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
