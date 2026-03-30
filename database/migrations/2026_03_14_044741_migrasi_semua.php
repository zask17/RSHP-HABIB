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
        // 1. Tabel User
        Schema::create('user', function (Blueprint $table) {
            $table->id('iduser');
            $table->string('nama', 500);
            $table->string('email', 200)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->timestamp('deleted_at')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
        });

        // 2. Tabel Role
        Schema::create('role', function (Blueprint $table) {
            $table->id('idrole');
            $table->string('nama_role');
        });

        // 3. Tabel Role User
        Schema::create('role_user', function (Blueprint $table) {
            $table->id('idrole_user');
            $table->unsignedBigInteger('iduser');
            $table->unsignedBigInteger('idrole');
            $table->boolean('status')->default(true);

            $table->foreign('iduser')->references('iduser')->on('user')->onDelete('cascade');
            $table->foreign('idrole')->references('idrole')->on('role')->onDelete('cascade');
        });

        // 4. Tabel Dokter
        Schema::create('dokter', function (Blueprint $table) {
            $table->id('iddokter');
            $table->unsignedBigInteger('iduser')->unique();
            $table->string('alamat', 100);
            $table->string('no_hp', 45);
            $table->string('bidang_dokter', 100);
            $table->enum('jenis_kelamin', ['M', 'F']);
            $table->timestamps();

            $table->foreign('iduser')->references('iduser')->on('user')->onDelete('cascade');
        });

        // 5. Tabel Perawat
        Schema::create('perawat', function (Blueprint $table) {
            $table->id('idperawat');
            $table->unsignedBigInteger('iduser')->unique();
            $table->string('alamat', 100);
            $table->string('no_hp', 45);
            $table->string('pendidikan', 100);
            $table->enum('jenis_kelamin', ['M', 'F']);
            $table->timestamps();

            $table->foreign('iduser')->references('iduser')->on('user')->onDelete('cascade');
        });

        // 6. Tabel Pemilik
        Schema::create('pemilik', function (Blueprint $table) {
            $table->id('idpemilik');
            $table->unsignedBigInteger('iduser')->unique();
            $table->string('no_wa', 45)->nullable();
            $table->string('alamat', 100)->nullable();
            $table->timestamps();

            $table->foreign('iduser')->references('iduser')->on('user')->onDelete('cascade');
        });

        // 7. Tabel Jenis Hewan
        Schema::create('jenis_hewan', function (Blueprint $table) {
            $table->integer('idjenis_hewan')->autoIncrement();
            $table->string('nama_jenis_hewan', 100)->nullable();
        });

        // 8. Tabel Ras Hewan
        Schema::create('ras_hewan', function (Blueprint $table) {
            $table->integer('idras_hewan')->autoIncrement();
            $table->string('nama_ras', 100)->nullable();
            $table->integer('idjenis_hewan');

            $table->foreign('idjenis_hewan')->references('idjenis_hewan')->on('jenis_hewan')->onDelete('cascade');
        });

        // 9. Tabel Pet (Hewan Peliharaan)
        Schema::create('pet', function (Blueprint $table) {
            $table->integer('idpet')->autoIncrement();
            $table->string('nama', 100)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('warna_tanda', 45)->nullable();
            $table->char('jenis_kelamin', 1)->nullable();
            $table->unsignedBigInteger('idpemilik');
            $table->integer('idras_hewan');

            $table->timestamp('deleted_at')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();

            $table->foreign('idpemilik')->references('idpemilik')->on('pemilik')->onDelete('restrict');
            $table->foreign('idras_hewan')->references('idras_hewan')->on('ras_hewan')->onDelete('restrict');
            $table->foreign('deleted_by')->references('iduser')->on('user')->onDelete('restrict');
        });

        // 10. Tabel Temu Dokter
        Schema::create('temu_dokter', function (Blueprint $table) {
            $table->id('idreservasi_dokter');
            $table->unsignedBigInteger('idrole_user');
            $table->dateTime('waktu_daftar');
            $table->integer('no_urut')->nullable();
            $table->enum('status', ['0', '1', '2'])->default('0'); // 0=Menunggu, 1=Selesai, 2=Batal
            $table->timestamps();

            $table->foreign('idrole_user')->references('idrole_user')->on('role_user')->onDelete('cascade');
            $table->index(['idrole_user', 'waktu_daftar']);
        });

        // 11. Tabel Kategori
        Schema::create('kategori', function (Blueprint $table) {
            $table->id('idkategori');
            $table->string('nama_kategori', 100);
        });

        // 12. Tabel Kategori Klinis
        Schema::create('kategori_klinis', function (Blueprint $table) {
            $table->id('idkategori_klinis');
            $table->string('nama_kategori_klinis', 100);
        });

        // 13. Tabel Kode Tindakan Terapi
        Schema::create('kode_tindakan_terapi', function (Blueprint $table) {
            $table->id('idkode_tindakan_terapi');
            $table->string('kode', 20)->unique();
            $table->text('deskripsi_tindakan_terapi');
            $table->unsignedBigInteger('idkategori');
            $table->unsignedBigInteger('idkategori_klinis');

            $table->foreign('idkategori')->references('idkategori')->on('kategori');
            $table->foreign('idkategori_klinis')->references('idkategori_klinis')->on('kategori_klinis');
        });

        // 14. Tabel Rekam Medis
        Schema::create('rekam_medis', function (Blueprint $table) {
            $table->id('idrekam_medis');
            $table->unsignedBigInteger('idreservasi_dokter');
            $table->integer('idpet');
            $table->unsignedBigInteger('dokter_pemeriksa'); // Relasi ke idrole_user
            $table->text('anamnesa');
            $table->text('temuan_klinis');
            $table->text('diagnosa');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('idreservasi_dokter')->references('idreservasi_dokter')->on('temu_dokter')->onDelete('cascade');
            $table->foreign('idpet')->references('idpet')->on('pet')->onDelete('restrict');
            $table->foreign('dokter_pemeriksa')->references('idrole_user')->on('role_user')->onDelete('restrict');
        });

        // 15. Tabel Detail Rekam Medis
        Schema::create('detail_rekam_medis', function (Blueprint $table) {
            $table->id('iddetail_rekam_medis');
            $table->unsignedBigInteger('idrekam_medis');
            $table->unsignedBigInteger('idkode_tindakan_terapi');
            $table->text('detail')->nullable();

            $table->foreign('idrekam_medis')->references('idrekam_medis')->on('rekam_medis')->onDelete('cascade');
            $table->foreign('idkode_tindakan_terapi')->references('idkode_tindakan_terapi')->on('kode_tindakan_terapi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_rekam_medis');
        Schema::dropIfExists('rekam_medis');
        Schema::dropIfExists('kode_tindakan_terapi');
        Schema::dropIfExists('kategori_klinis');
        Schema::dropIfExists('kategori');
        Schema::dropIfExists('temu_dokter');
        Schema::dropIfExists('pet');
        Schema::dropIfExists('ras_hewan');
        Schema::dropIfExists('jenis_hewan');
        Schema::dropIfExists('pemilik');
        Schema::dropIfExists('perawat');
        Schema::dropIfExists('dokter');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('role');
        Schema::dropIfExists('user');
    }
};