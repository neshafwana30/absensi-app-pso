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
            // Menambahkan kolom type dan activity_date untuk membedakan absensi
            $table->enum('type', ['harian', 'kegiatan'])->default('harian')->after('description');
            $table->date('activity_date')->nullable()->after('type'); 
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
            // Menghapus kolom jika migration di-rollback
            $table->dropColumn(['type', 'activity_date']);
        });
    }
};