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
    Schema::create('dossiers', function (Blueprint $table) {
        $table->id();
        $table->string('numero_reference')->unique();
        $table->string('titre');
        $table->text('description')->nullable();
        $table->enum('statut', ['actif', 'archive', 'en_destruction'])->default('actif');
        $table->foreignId('boite_id')->constrained()->onDelete('cascade');
        $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dossiers');
    }


};
