<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductoImagenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'url_imagen' => $this->url_imagen,
            'original_name' => $this->original_name,
            'texto_alt_SEO' => $this->texto_alt_SEO,
            'titulo' => $this->titulo,
            'tipo' => $this->tipo ?? 'galeria',
        ];

        if (($this->tipo ?? '') === 'whatsapp') {
            $data['whatsapp_mensaje'] = $this->whatsapp_mensaje ?? null;
            $data['whatsapp_mensaje_2'] = $this->whatsapp_mensaje_2 ?? null;
            $data['whatsapp_mensaje_3'] = $this->whatsapp_mensaje_3 ?? null;
            $data['whatsapp_time_1'] = $this->whatsapp_time_1 ?? 0;
            $data['whatsapp_time_2'] = $this->whatsapp_time_2 ?? 0;
            $data['whatsapp_time_3'] = $this->whatsapp_time_3 ?? 0;
            $data['whatsapp_image_url_2'] = $this->whatsapp_image_url_2 ? url($this->whatsapp_image_url_2) : null;
            $data['whatsapp_image_url_3'] = $this->whatsapp_image_url_3 ? url($this->whatsapp_image_url_3) : null;
        }

        if (str_starts_with($this->tipo ?? '', 'email')) {
            $data['asunto'] = $this->asunto ?? null;
            $data['email_mensaje'] = $this->email_mensaje ?? null;
            $data['email_btn_text'] = $this->email_btn_text ?? null;
            $data['email_btn_link'] = $this->email_btn_link ?? null;
            $data['email_btn_bg_color'] = $this->email_btn_bg_color ?? null;
            $data['email_btn_text_color'] = $this->email_btn_text_color ?? null;
            $data['delay_minutes'] = $this->delay_minutes ?? 0;
            $data['email_time'] = $this->email_time ?? 0;
            $data['slot_index'] = $this->slot_index ?? (int) filter_var($this->tipo, FILTER_SANITIZE_NUMBER_INT);
        }

        return $data;
    }
}
