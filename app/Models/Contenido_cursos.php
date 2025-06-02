<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contenido_cursos extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_cursos',
        'titulo',
        'mensaje',
        'archivo',
        'tipo_archivo',
        'nombre_archivo_original'
    ];

    public $timestamps = false;

    /**
     * Get the Contenido_cursos that owns the cursos
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function cursos()
    {
        return $this->belongsTo('App\Models\Curso', 'id_cursos');
    }
}
