<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Memastikan kolom 'type' belum ada sebelum menambahkannya
        if (!Schema::hasColumn('attendances', 'type')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->string('type')->default('kantor')->after('description');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Memastikan kolom 'type' ada sebelum menghapusnya
        if (Schema::hasColumn('attendances', 'type')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }
};