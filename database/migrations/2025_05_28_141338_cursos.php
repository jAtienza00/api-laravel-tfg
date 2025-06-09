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
        Schema::create('cursos', function (Blueprint $table) {
            $table->bigIncrements('id'); // PK
            $table->unsignedBigInteger('id_usuario'); // FK
            $table->string('nombre', 100); // not null
            $table->text('imagen')->nullable(); // longblob, renamed from data
            $table->string('tipo_archivo', 100)->nullable();
            $table->text('descripcion'); // not null
            $table->string('color_fondo', 100)->nullable()->default('#000');
            $table->string('color_texto', 100)->nullable()->default('#fff');
            $table->foreign('id_usuario')->references('id')->on('usuarios')->onDelete('cascade');
        });

        Schema::create('contenido_cursos', function (Blueprint $table) {
            $table->bigIncrements('id'); // PK
            $table->unsignedBigInteger('id_cursos'); // FK
            $table->string('titulo', 100)->default('Mensaje'); // not null
            $table->text('mensaje')->nullable();
            $table->text('archivo')->nullable(); // longblob
            $table->string('tipo_archivo', 100)->nullable();
            $table->string('nombre_archivo_original', 255)->nullable();
            $table->foreign('id_cursos')->references('id')->on('cursos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contenido_cursos'); // Drop this first due to FK
        Schema::dropIfExists('cursos');
    }
};
