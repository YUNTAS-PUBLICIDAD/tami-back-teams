<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductoImagenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url_imagen' => $this->url_imagen,
            'texto_alt_SEO' => $this->texto_alt_SEO,
            'asunto' => $this->asunto,
            'whatsapp_mensaje' => $this->whatsapp_mensaje,
            'whatsapp_mensaje_2' => $this->whatsapp_mensaje_2,
            'whatsapp_mensaje_3' => $this->whatsapp_mensaje_3,
            'whatsapp_time_1' => $this->whatsapp_time_1,
            'whatsapp_time_2' => $this->whatsapp_time_2,
            'whatsapp_time_3' => $this->whatsapp_time_3,
            'whatsapp_image_url_2' => $this->whatsapp_image_url_2 ? url($this->whatsapp_image_url_2) : null,
            'whatsapp_image_url_3' => $this->whatsapp_image_url_3 ? url($this->whatsapp_image_url_3) : null,
            'email_mensaje' => $this->email_mensaje,
            'email_btn_text' => $this->email_btn_text,
            'email_btn_link' => $this->email_btn_link,
            'email_btn_bg_color' => $this->email_btn_bg_color,
            'email_btn_text_color' => $this->email_btn_text_color,
            'delay_minutes' => $this->delay_minutes ?? 0,
            'slot_index' => $this->slot_index ?? ($this->tipo === 'email' ? 1 : (str_starts_with($this->tipo ?? '', 'email') ? (int) filter_var($this->tipo, FILTER_SANITIZE_NUMBER_INT) : null)),
            'tipo' => $this->tipo ?? 'galeria',
        ];
    }
}
