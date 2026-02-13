<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ProductoRelacionadoResource; 
use App\Http\Resources\ProductoImagenResource;

class ProductoResource extends JsonResource
{
    private bool $withRelacionados;

    public function __construct($resource, $withRelacionados = true)
    {
        parent::__construct($resource);
        $this->withRelacionados = $withRelacionados;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'nombre' => $this->nombre,
            'link' => $this->link,
            'subtitulo' => $this->subtitulo,
            'stock' => $this->stock,
            'precio' => $this->precio,
            'seccion' => $this->seccion,
            'descripcion' => $this->descripcion,
            'video_url' => $this->video_url,
            'especificaciones' => $this->especificaciones ?? [],
            'dimensiones' => $this->dimensiones ? [
                'alto' => $this->dimensiones->alto,
                'largo' => $this->dimensiones->largo,
                'ancho' => $this->dimensiones->ancho,
            ] : null,
            'imagenes' => ProductoImagenResource::collection(
                $this->imagenes->filter(function($img) {
                    return $img->tipo === 'galeria' || $img->tipo === null;
                })->values() 
            ),
            'producto_imagenes' => ProductoImagenResource::collection($this->imagenes),

            'productos_relacionados' => $this->withRelacionados ? ProductoRelacionadoResource::collection($this->productosRelacionados) : $this->productosRelacionados,
            'etiqueta' => $this->etiqueta ? [
                'meta_titulo' => $this->etiqueta->meta_titulo,
                'meta_descripcion' => $this->etiqueta->meta_descripcion,
                'keywords' => $this->etiqueta->keywords,
                'popup_estilo' => $this->etiqueta->popup_estilo,
                'popup3_sin_fondo' => $this->etiqueta->popup3_sin_fondo,

                'titulo_popup_1' => $this->etiqueta->titulo_popup_1,
                'titulo_popup_2' => $this->etiqueta->titulo_popup_2,
                'titulo_popup_3' => $this->etiqueta->titulo_popup_3,
            ] : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
