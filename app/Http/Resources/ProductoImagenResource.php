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
            'email_mensaje' => $this->email_mensaje,
            'email_btn_text' => $this->email_btn_text,
            'email_btn_link' => $this->email_btn_link,
            'email_btn_bg_color' => $this->email_btn_bg_color,
            'email_btn_text_color' => $this->email_btn_text_color,
            'tipo' => $this->tipo ?? 'galeria',
        ];
    }
}
