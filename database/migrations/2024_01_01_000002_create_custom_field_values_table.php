<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_field_values', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Polymorphic subject (the model that holds field values)
            $table->string('subject_type');
            $table->string('subject_id');
            $table->index(['subject_type', 'subject_id']);

            $table->string('custom_field_id');
            $table->foreign('custom_field_id')
                ->references('id')
                ->on('custom_fields')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('value')->default('');

            $table->timestamps();

            $table->unique(['subject_type', 'subject_id', 'custom_field_id'], 'cfv_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_values');
    }
};
