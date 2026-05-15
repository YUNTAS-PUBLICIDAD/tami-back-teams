<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\ProductoImagen;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class ProductoImageService
{
    /**
     * Maneja la creación o actualización de una imagen especial (popup, email, whatsapp).
     * Elimina la imagen anterior del mismo tipo si existe.
     *
     * @param Producto $producto Producto al que pertenece la imagen
     * @param UploadedFile|null $file Archivo de imagen
     * @param string $tipo Tipo de imagen: 'popup', 'email' o 'whatsapp'
     * @param string|null $altText Texto alternativo para SEO
     * @return ProductoImagen|null
     */
    public function handleSpecialImage(Producto $producto, ?UploadedFile $file, string $tipo, ?string $textValue = null, array $extraData = []): ?ProductoImagen
    {
        $query = $producto->imagenes()->where(function ($query) use ($tipo) {
            if ($tipo === 'email1' || $tipo === 'email') {
                $query->whereIn('tipo', ['email1', 'email']);
            } else {
                $query->where('tipo', $tipo);
            }
        });

        $imagenExistente = $query->first();
        $data = $extraData;

        if (str_starts_with($tipo, 'email')) {
            $slotIndex = $extraData['slot_index'] ?? ($tipo === 'email' ? 1 : (int) filter_var($tipo, FILTER_SANITIZE_NUMBER_INT));
            $data['asunto'] = $textValue ?? '';
            $data['texto_alt_SEO'] = \Illuminate\Support\Str::limit($textValue ?? '', 120);
            $data['slot_index'] = $slotIndex;
            $data['delay_minutes'] = $extraData['delay_minutes'] ?? 0;
        } elseif ($tipo === 'whatsapp') {
            $data['whatsapp_mensaje'] = $extraData['whatsapp_mensaje'] ?? ($textValue ?? '');
            $data['whatsapp_mensaje_2'] = $extraData['whatsapp_mensaje_2'] ?? null;
            $data['whatsapp_mensaje_3'] = $extraData['whatsapp_mensaje_3'] ?? null;
            $data['whatsapp_time_1'] = $extraData['whatsapp_time_1'] ?? 0;
            $data['whatsapp_time_2'] = $extraData['whatsapp_time_2'] ?? 0;
            $data['whatsapp_time_3'] = $extraData['whatsapp_time_3'] ?? 0;
            $data['texto_alt_SEO'] = \Illuminate\Support\Str::limit($textValue ?? '', 120);

            if (isset($extraData['whatsapp_image_2']) && $extraData['whatsapp_image_2'] instanceof UploadedFile) {
                if ($imagenExistente && !empty($imagenExistente->whatsapp_image_url_2)) {
                    $this->deleteImageFromStorage($imagenExistente->whatsapp_image_url_2);
                }
                $data['whatsapp_image_url_2'] = $this->guardarImagen($extraData['whatsapp_image_2']);
            }
            if (isset($extraData['whatsapp_image_3']) && $extraData['whatsapp_image_3'] instanceof UploadedFile) {
                if ($imagenExistente && !empty($imagenExistente->whatsapp_image_url_3)) {
                    $this->deleteImageFromStorage($imagenExistente->whatsapp_image_url_3);
                }
                $data['whatsapp_image_url_3'] = $this->guardarImagen($extraData['whatsapp_image_3']);
            }
        } else {
            $data['texto_alt_SEO'] = \Illuminate\Support\Str::limit($textValue ?? '', 120);
        }

        if ($file) {
            if ($imagenExistente) {
                $this->deleteExistingImageByType($producto, $tipo);
            }
            return $this->saveImage($producto, $file, $tipo, $data);
        } elseif ($imagenExistente) {
            $imagenExistente->update($data);
            return $imagenExistente;
        } elseif (!empty($textValue) || !empty($data['email_mensaje']) || !empty($data['whatsapp_mensaje'])) {
            $data['url_imagen'] = $data['url_imagen'] ?? '';
            $data['tipo'] = $tipo;
            return $producto->imagenes()->create($data);
        }

        return null;
    }

    /**
     * Guarda un archivo de imagen en storage/imagenes.
     *
     * @param UploadedFile $archivo Archivo a guardar
     * @return string URL pública de la imagen (/storage/imagenes/nombre.ext)
     */
    public function guardarImagen(UploadedFile $archivo): string
    {
        $nombre = uniqid() . '_' . time() . '.' . $archivo->getClientOriginalExtension();
        $archivo->storeAs("imagenes", $nombre, "public");
        return "/storage/imagenes/" . $nombre;
    }

    /**
     * Elimina todas las imágenes de galería de un producto.
     * No elimina imágenes especiales (popup, email, whatsapp).
     *
     * @param Producto $producto Producto cuyas imágenes de galería se eliminarán
     * @return void
     */
    public function deleteGalleryImages(Producto $producto): void
    {
        $imagenes = $producto->imagenes()
            ->where(fn($query) => $query->where('tipo', 'galeria')->orWhereNull('tipo'))
            ->get();

        foreach ($imagenes as $imagen) {
            $this->deleteImageFromStorage($imagen->url_imagen);
        }

        $producto->imagenes()
            ->where(fn($query) => $query->where('tipo', 'galeria')->orWhereNull('tipo'))
            ->delete();
    }

    /**
     * Guarda múltiples imágenes de galería.
     *
     * @param Producto $producto Producto al que se agregarán las imágenes
     * @param array $imagenes Array de UploadedFile
     * @param array $altTexts Array de textos alternativos (opcional)
     * @return void
     */
    public function saveGalleryImages(Producto $producto, array $imagenes, array $altTexts = []): void
    {
        foreach ($imagenes as $i => $imagen) {
            $ruta = $this->guardarImagen($imagen);
            $producto->imagenes()->create([
                'url_imagen' => $ruta,
                'texto_alt_SEO' => $altTexts[$i] ?? null,
                'tipo' => 'galeria'
            ]);
        }
    }

    /**
     * Elimina todas las imágenes de un producto del storage.
     *
     * @param Producto $producto Producto cuyas imágenes se eliminarán
     * @return void
     */
    public function deleteAllImagesFromStorage(Producto $producto): void
    {
        foreach ($producto->imagenes as $imagen) {
            $this->deleteImageFromStorage($imagen->url_imagen);
        }
    }

    public function deleteExistingImageByType(Producto $producto, string $tipo): void
    {
        $query = $producto->imagenes()->where(function ($query) use ($tipo) {
            if ($tipo === 'email1' || $tipo === 'email') {
                $query->whereIn('tipo', ['email1', 'email']);
            } else {
                $query->where('tipo', $tipo);
            }
        });

        $imagenAnterior = $query->first();

        if ($imagenAnterior) {
            $this->deleteImageFromStorage($imagenAnterior->url_imagen);
            $imagenAnterior->delete();
        }
    }

    private function saveImage(Producto $producto, UploadedFile $file, string $tipo, array $data): ProductoImagen
    {
        $url = $this->guardarImagen($file);

        $payload = array_merge($data, [
            'url_imagen' => $url,
            'tipo' => $tipo
        ]);

        return $producto->imagenes()->create($payload);
    }

    public function deleteImageFromStorage(string $path): void
    {
        $path = str_replace('/storage/', '', $path);

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
