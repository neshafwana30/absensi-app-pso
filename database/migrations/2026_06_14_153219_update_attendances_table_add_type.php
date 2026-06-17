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
        Schema::table('attendances', function (Blueprint $table) {
            // Menambahkan kolom type untuk kategori absensi
            // defaultnya 'kantor' agar data lama tidak error
            $table->string('type')->default('kantor')->after('description');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Menghapus kolom type jika migration di-rollback
            $table->dropColumn('type');
        });
    }
};