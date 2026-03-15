<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('custom_fields')) {
            return;
        }

        Schema::create('custom_fields', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // 3-tier scoping:
            //   owner_type=null, owner_id=null  → global (all models)
            //   owner_type=ModelClass, owner_id=null → type-scoped (all records of that model)
            //   owner_type=OwnerClass, owner_id=uuid → instance-scoped (records owned by this instance)
            $table->string('owner_type')->nullable()->index();
            $table->string('owner_id')->nullable()->index();

            $table->json('name');
            $table->json('description')->nullable();
            $table->string('type')->default('text');
            $table->json('data')->nullable();         // e.g. {"options": ["A","B","C"]} for select
            $table->json('metadata')->nullable();     // developer-defined extra data

            $table->unsignedInteger('order')->nullable();
            $table->string('default_value')->nullable();
            $table->unsignedInteger('max_chars')->nullable();

            $table->boolean('is_mandatory')->default(false);
            $table->boolean('is_unique')->default(false);
            $table->boolean('is_printable')->default(true);
            $table->boolean('is_fixed')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_fields');
    }
};
