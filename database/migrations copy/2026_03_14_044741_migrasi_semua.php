<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
   /**
    * Run the migrations.
    */
   public function up(): void
   {
       Schema::create('user', function (Blueprint $table) {
           $table->id('iduser');
           $table->string('nama', 500);
           $table->string('email', 200)->unique();
           $table->timestamp('email_verified_at')->nullable();
           $table->string('password');
           $table->timestamp('deleted_at')->nullable();
           $table->unsignedBigInteger('deleted_by')->nullable();
       });


       Schema::create('role', function (Blueprint $table) {
           $table->id('idrole');
           $table->string('nama_role');
       });


       Schema::create('role_user', function (Blueprint $table) {
           $table->id('idrole_user');
           $table->unsignedBigInteger('iduser');
           $table->unsignedBigInteger('idrole');
           $table->boolean('status')->default(true);


           $table->foreign('iduser')->references('iduser')->on('user')->onDelete('cascade');
           $table->foreign('idrole')->references('idrole')->on('role')->onDelete('cascade');
       });
   }


   /**
    * Reverse the migrations.
    */
   public function down(): void
   {
       Schema::dropIfExists('user');
   }
};


