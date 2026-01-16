<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductoRelacionadoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'link' => $this->link,
            'titulo' => $this->titulo,
            'subtitulo' => $this->subtitulo,
            'stock' => $this->stock,
            'precio' => $this->precio,
            'seccion' => $this->seccion,
            'descripcion' => $this->descripcion,
            
            'imagenes' => ProductoImagenResource::collection($this->imagenes->filter(function($img) {
                return $img->tipo === 'galeria' || $img->tipo === null;
            })),
        ];
    }
}