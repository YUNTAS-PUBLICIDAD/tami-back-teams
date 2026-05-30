<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\ProductoImagen;
use App\Models\Dimension;
use App\Models\WhatsappTemplate;

class Producto extends Model
{
    protected $fillable = [
        'nombre',
        'link',
        'titulo',
        'detalle_titulo_tamano',
        'detalle_titulo_color',
        'detalle_titulo_estilo',
        'subtitulo',
        'stock',
        'precio',
        'seccion',
        'descripcion',
        'video_url',
        // 'imagenes'
    ];

    public $timestamps = true;
    protected $appends = [
        'popup_mobile_image_count',
        'popup_mobile_image_url',
        'popup_mobile_image2_url',
    ];

    public function dimensiones()
    {
        return $this->hasOne(Dimension::class, 'id_producto');
    }

    public function imagenes()
    {
        return $this->hasMany(ProductoImagen::class, 'producto_id');
    }

    public function imagenPopup()
    {
        return $this->hasOne(ProductoImagen::class, 'producto_id')->where('tipo', 'popup');
    }
    public function imagenPopup2()
    {
        return $this->hasOne(ProductoImagen::class, 'producto_id')->where('tipo', 'popup2');
    }
      public function imagenWhatsapp()
    {
        return $this->hasOne(ProductoImagen::class, 'producto_id')->where('tipo', 'whatsapp');
    }
    public function productosRelacionados()
    {
        return $this->belongsToMany(Producto::class, 'producto_relacionados', 'id_producto', 'id_relacionado');
    }

    public function blogs()
    {
        return $this->hasMany(Blog::class, 'producto_id', 'id');
    }
    public function especificaciones(): HasMany
    {
        return $this->hasMany(Especificacion::class, 'producto_id');
    }

    public function etiqueta()
    {
        return $this->hasOne(ProductoEtiqueta::class);
    }
    public function productoImagenes()
    {
        return $this->hasMany(ProductoImagen::class, 'producto_id', 'id');
    }

    public function getPopupMobileImageCountAttribute(): int
    {
        return collect([
            $this->popup_mobile_image_url,
            $this->popup_mobile_image2_url,
        ])->filter()->count();
    }

    public function getPopupMobileImageUrlAttribute(): ?string
    {
        return $this->resolveSpecialImageUrl(['popup_mobile', 'popup_mobile_image', 'popup_mobile_1']);
    }

    public function getPopupMobileImage2UrlAttribute(): ?string
    {
        return $this->resolveSpecialImageUrl(['popup_mobile2', 'popup_mobile_image2', 'popup_mobile_2']);
    }

    private function resolveSpecialImageUrl(array $types): ?string
    {
        $imagenes = $this->relationLoaded('imagenes') ? $this->imagenes : $this->imagenes()->get();

        $imagen = $imagenes
            ->whereIn('tipo', $types)
            ->filter(fn ($item) => !empty($item->url_imagen))
            ->sortByDesc('id')
            ->first();

        if (!$imagen || empty($imagen->url_imagen)) {
            return null;
        }

        return preg_match('/^https?:\/\//', $imagen->url_imagen)
            ? $imagen->url_imagen
            : url($imagen->url_imagen);
    }

    public function whatsappTemplate()
    {
        return $this->hasOne(WhatsappTemplate::class, 'producto_id');
    }
}
