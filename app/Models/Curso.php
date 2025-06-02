<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'imagen',
        'descripcion',
        'color_fondo',
        'color_texto',
        'id_usuario',
        'tipo_archivo'
    ];

    public $timestamps = false;

    /**
     * Get all of the contenido_cursos for the Curso
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function contenido_cursos()
    {
        return $this->hasMany('App\Models\Contenido_cursos', 'id_cursos', 'id');
    }

    protected static function boot()
    {
        parent::boot();
        static::deleting(function ($curso) {
            // This will delete related contenido_cursos when a curso is deleted.
            // Ensure this is the desired behavior.
            $curso->contenido_cursos()->delete();
        });
    }
}
