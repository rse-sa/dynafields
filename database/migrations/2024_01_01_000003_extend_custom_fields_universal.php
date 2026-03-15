<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_fields', function (Blueprint $table) {
            if (! Schema::hasColumn('custom_fields', 'scope')) {
                $table->string('scope')->nullable()->index()->after('owner_id');
            }

            if (! Schema::hasColumn('custom_fields', 'active')) {
                $table->boolean('active')->default(true)->after('is_fixed');
            }

            if (! Schema::hasColumn('custom_fields', 'searchable')) {
                $table->boolean('searchable')->default(false)->after('active');
            }

            if (! Schema::hasColumn('custom_fields', 'depends_on_field_id')) {
                $table->string('depends_on_field_id')->nullable()->index()->after('searchable');
                $table->foreign('depends_on_field_id')
                    ->references('id')
                    ->on('custom_fields')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('custom_fields', 'depends_on_condition')) {
                $table->json('depends_on_condition')->nullable()->after('depends_on_field_id');
            }

            if (! Schema::hasColumn('custom_fields', 'created_by')) {
                $table->string('created_by')->nullable()->after('depends_on_condition');
            }

            if (! Schema::hasColumn('custom_fields', 'updated_by')) {
                $table->string('updated_by')->nullable()->after('created_by');
            }
        });

        Schema::table('custom_field_values', function (Blueprint $table) {
            if (! Schema::hasColumn('custom_field_values', 'extra')) {
                $table->json('extra')->nullable()->after('value');
            }
        });
    }

    public function down(): void
    {
        Schema::table('custom_fields', function (Blueprint $table) {
            $table->dropColumnIfExists('scope');
            $table->dropColumnIfExists('active');
            $table->dropColumnIfExists('searchable');

            if (Schema::hasColumn('custom_fields', 'depends_on_field_id')) {
                $table->dropForeign(['depends_on_field_id']);
                $table->dropColumn('depends_on_field_id');
            }

            $table->dropColumnIfExists('depends_on_condition');
            $table->dropColumnIfExists('created_by');
            $table->dropColumnIfExists('updated_by');
        });

        Schema::table('custom_field_values', function (Blueprint $table) {
            $table->dropColumnIfExists('extra');
        });
    }
};
