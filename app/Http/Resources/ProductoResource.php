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
            'porque_elegirnos' => $this->porque_elegirnos,
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

            'producto_imagenes' => ProductoImagenResource::collection(
                (function() {
                    $imagenes = $this->imagenes;
                    
                    // Virtualizar WhatsApp
                    $whatsappPasos = $this->relationLoaded('whatsappPasos') ? $this->whatsappPasos : $this->whatsappPasos()->get();
                    if ($whatsappPasos && $whatsappPasos->count() > 0) {
                        $paso1 = $whatsappPasos->firstWhere('paso', 1);
                        $paso2 = $whatsappPasos->firstWhere('paso', 2);
                        $paso3 = $whatsappPasos->firstWhere('paso', 3);

                        // Crear modelo virtual de ProductoImagen para retrocompatibilidad en el frontend
                        $virtualWhatsapp = new \App\Models\ProductoImagen([
                            'id' => null,
                            'url_imagen' => $paso1?->imagen_url ?? '',
                            'texto_alt_SEO' => '',
                            'tipo' => 'whatsapp',
                        ]);
                        
                        $virtualWhatsapp->whatsapp_mensaje = $paso1?->mensaje;
                        $virtualWhatsapp->whatsapp_mensaje_2 = $paso2?->mensaje;
                        $virtualWhatsapp->whatsapp_mensaje_3 = $paso3?->mensaje;
                        $virtualWhatsapp->whatsapp_time_1 = $paso1?->delay_minutos ?? 0;
                        $virtualWhatsapp->whatsapp_time_2 = $paso2?->delay_minutos ?? 0;
                        $virtualWhatsapp->whatsapp_time_3 = $paso3?->delay_minutos ?? 0;
                        $virtualWhatsapp->whatsapp_image_url_2 = $paso2?->imagen_url;
                        $virtualWhatsapp->whatsapp_image_url_3 = $paso3?->imagen_url;

                        $imagenes = $imagenes->concat([$virtualWhatsapp]);
                    }

                    // Virtualizar Email
                    $emailPasos = $this->relationLoaded('emailPasos') ? $this->emailPasos : $this->emailPasos()->get();
                    if ($emailPasos && $emailPasos->count() > 0) {
                        foreach ($emailPasos as $paso) {
                            $virtualEmail = new \App\Models\ProductoImagen([
                                'id' => null,
                                'url_imagen' => $paso->imagen_url ?? '',
                                'texto_alt_SEO' => '',
                                'tipo' => "email{$paso->paso}",
                            ]);
                            
                            $virtualEmail->asunto = $paso->asunto;
                            $virtualEmail->email_mensaje = $paso->mensaje;
                            $virtualEmail->email_btn_text = $paso->btn_text;
                            $virtualEmail->email_btn_link = $paso->btn_link;
                            $virtualEmail->email_btn_bg_color = $paso->btn_bg_color;
                            $virtualEmail->email_btn_text_color = $paso->btn_text_color;
                            $virtualEmail->delay_minutes = $paso->delay_minutos ?? 0;
                            $virtualEmail->email_time = $paso->delay_minutos ?? 0;
                            $virtualEmail->slot_index = $paso->paso;

                            $imagenes = $imagenes->concat([$virtualEmail]);
                        }
                    }

                    return $imagenes;
                })()
            ),

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
                'product_popup_delay_seconds' => $this->etiqueta->product_popup_delay_seconds ?? 30,
            ] : null,

            'email_templates' => (function() {
                $emailPasos = $this->relationLoaded('emailPasos') ? $this->emailPasos : $this->emailPasos()->get();
                if (!$emailPasos) {
                    return [];
                }
                return $emailPasos->sortBy('paso')->values()->map(function ($paso) {
                    $imageUrl = $paso->imagen_url;
                    if (!empty($imageUrl) && !preg_match('/^https?:\/\//', $imageUrl)) {
                        $imageUrl = url($imageUrl);
                    }

                    return [
                        'slot_index' => $paso->paso,
                        'image_url' => $imageUrl,
                        'subject' => $paso->asunto,
                        'body_html' => $paso->mensaje,
                        'btn_text' => $paso->btn_text,
                        'btn_link' => $paso->btn_link,
                        'btn_bg_color' => $paso->btn_bg_color,
                        'btn_text_color' => $paso->btn_text_color,
                        'delay_minutes' => $paso->delay_minutos ?? 0,
                        'email_time' => $paso->delay_minutos ?? 0,
                    ];
                })->toArray();
            })(),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
