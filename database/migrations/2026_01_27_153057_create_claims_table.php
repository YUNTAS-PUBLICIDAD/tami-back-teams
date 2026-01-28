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
        Schema::create('claims', function (Blueprint $table) {
    $table->id();
    $table->string('first_name', 150);
    $table->string('last_name', 150);
    $table->unsignedBigInteger('document_type_id');
    $table->string('document_number', 20);
    $table->string('email', 191);
    $table->string('phone', 20)->nullable();
    $table->date('purchase_date')->nullable();
    $table->unsignedBigInteger('producto_id')->nullable();
    $table->unsignedBigInteger('claim_type_id');
    $table->text('detail')->nullable();
    $table->decimal('claimed_amount', 10, 2)->nullable();
    $table->unsignedBigInteger('claim_status_id');
    $table->timestamps();

    $table->foreign('document_type_id')->references('id')->on('document_types');
    $table->foreign('claim_type_id')->references('id')->on('claim_types');
    $table->foreign('claim_status_id')->references('id')->on('claim_statuses');
    $table->foreign('producto_id')->references('id')->on('productos')->onDelete('set null');

    $table->index('producto_id');
    $table->index('document_type_id');
    $table->index('claim_type_id');
    $table->index('document_number');
    $table->index('email');
    $table->index('claim_status_id');
    $table->index('created_at');
});
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
};
