<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', fn (Blueprint $t) => $t->ulid('id')->primary());
        Schema::create('categories', fn (Blueprint $t) => $t->ulid('id')->primary());
        Schema::create('products', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->string('category_id')->nullable();
        });
        Schema::create('folders', function (Blueprint $t) {
            $t->ulid('id')->primary();
        });
        Schema::create('documents', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->string('folder_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('products');
        Schema::dropIfExists('folders');
        Schema::dropIfExists('documents');
    }
};
