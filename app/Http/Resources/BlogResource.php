<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'titulo'          => $this->titulo,
            'nombre_producto' => $this->producto ? $this->producto->nombre : null,
            'producto_id' => $this->producto_id,
            'link'            => $this->link,
            'subtitulo1'      => $this->subtitulo1,
            'subtitulo2'      => $this->subtitulo2,
            'video_id'        => self::obtenerIdVideoYoutube($this->video_url),
            'video_url'       => $this->video_url,
            'video_titulo'    => $this->video_titulo,
            'miniatura'       => $this->miniatura,
            'imagenes'        => BlogImagenResource::collection($this->imagenes),
            'parrafos'        => BlogParrafoResource::collection($this->parrafos),
            'etiqueta'        => $this->etiqueta ? [
                'meta_titulo'      => $this->etiqueta->meta_titulo,
                'meta_descripcion' => $this->etiqueta->meta_descripcion,
            ] : null,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
        ];
    }

    /**
     * Método helper para extraer el ID de un video de YouTube.
     */
    private static function obtenerIdVideoYoutube($url)
    {
        $pattern = '%(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|v/|shorts/))([^\s&?]+)%';
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
