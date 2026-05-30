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
        $popupMobileImage = $this->popup_mobile_image_url;
        $popupMobileImage2 = $this->popup_mobile_image2_url;

        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'detalle_titulo_tamano' => $this->detalle_titulo_tamano,
            'detalle_titulo_color' => $this->detalle_titulo_color,
            'detalle_titulo_estilo' => $this->detalle_titulo_estilo,
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
            'popup_mobile_image_count' => $this->popup_mobile_image_count,
            'popup_mobile_image_url' => $popupMobileImage,
            'popup_mobile_image2_url' => $popupMobileImage2,
            'popup_mobile_image_1_url' => $popupMobileImage,
            'popup_mobile_image_2_url' => $popupMobileImage2,
            'popup_mobile_image' => $popupMobileImage,
            'popup_mobile_image2' => $popupMobileImage2,
            'imageMobile' => $popupMobileImage,
            'imageMobile2' => $popupMobileImage2,

            'imagenes' => ProductoImagenResource::collection(
                $this->imagenes->filter(function($img) {
                    return $img->tipo === 'galeria' || $img->tipo === null;
                })->values()
            ),

            'producto_imagenes' => ProductoImagenResource::collection($this->imagenes),

            'productos_relacionados' => $this->withRelacionados
                ? ProductoRelacionadoResource::collection($this->productosRelacionados)
                : $this->productosRelacionados,

            'etiqueta' => $this->etiqueta ? [
                'meta_titulo' => $this->etiqueta->meta_titulo,
                'meta_descripcion' => $this->etiqueta->meta_descripcion,
                'keywords' => $this->etiqueta->keywords,
                'popup_estilo' => $this->etiqueta->popup_estilo,
                'popup3_sin_fondo' => $this->etiqueta->popup3_sin_fondo,

                // NUEVOS CAMPOS
                'popup_button_color' => $this->etiqueta->popup_button_color,
                'popup_text_color' => $this->etiqueta->popup_text_color,
                'popup_button_text' => $this->etiqueta->popup_button_text,
            ] : null,

            'email_templates' => $this->imagenes ? $this->imagenes->filter(function ($img) {
                return !empty($img->tipo) && str_starts_with($img->tipo, 'email');
            })->sortBy(function ($img) {
                if (!empty($img->slot_index)) {
                    return $img->slot_index;
                }
                return $img->tipo === 'email' ? 1 : (int) filter_var($img->tipo, FILTER_SANITIZE_NUMBER_INT);
            })->values()->map(function ($img) {
                $imageUrl = $img->url_imagen;
                if (!empty($imageUrl) && !preg_match('/^https?:\/\//', $imageUrl)) {
                    $imageUrl = url($imageUrl);
                }

                $slotIndex = $img->slot_index ?? ($img->tipo === 'email' ? 1 : (int) filter_var($img->tipo, FILTER_SANITIZE_NUMBER_INT));

                return [
                    'slot_index' => $slotIndex,
                    'image_url' => $imageUrl,
                    'subject' => $img->asunto,
                    'body_html' => $img->email_mensaje,
                    'btn_text' => $img->email_btn_text,
                    'btn_link' => $img->email_btn_link,
                    'btn_bg_color' => $img->email_btn_bg_color,
                    'btn_text_color' => $img->email_btn_text_color,
                    'delay_minutes' => $img->delay_minutes ?? 0,
                    'email_time' => $img->delay_minutes ?? 0,
                ];
            })->toArray() : [],

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
