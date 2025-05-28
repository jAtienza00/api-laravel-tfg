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
            $table->bigIncrements('id');
            $table->bigIncrements('id_usuario');
            $table->string('nombre', 100)->nullable();
            $table->binary('data')->nullable();
            $table->text('descripcion')->nullable();
            $table->string('color_fondo', 100)->nullable()->default('#000');
            $table->string('color_texto', 100)->nullable()->default('#fff');

            $table->foreign('id_usuario')->references('id')->on('usuarios')->onDelete('cascade');
        });

        Schema::create('contenido_cursos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigIncrements('id_cursos');
            $table->string('titulo', 100)->nullable()->default('Mensaje');
            $table->text('mensaje');
            $table->binary('archivo');
            $table->string('tipo_archivo', 100);

            $table->foreign('id_cursos')->references('id')->on('cursos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cursos');
    }
};
