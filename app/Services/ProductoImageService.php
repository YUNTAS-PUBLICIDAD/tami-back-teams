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
    public function handleSpecialImage(Producto $producto, ?UploadedFile $file, string $tipo, ?string $textValue = null): ?ProductoImagen
    {
        $imagenExistente = $producto->imagenes()->where('tipo', $tipo)->first();

        $data = [];
        if ($tipo === 'email') {
            $data['asunto'] = $textValue ?? '';
        } elseif ($tipo === 'whatsapp') {
            
            $data['whatsapp_mensaje'] = $textValue ?? '';
            
        }
         else {
            $data['texto_alt_SEO'] = $textValue ?? '';
        }


        if ($file) {
            if ($imagenExistente) {
                $this->deleteExistingImageByType($producto, $tipo);
            }
            return $this->saveImage($producto, $file, $tipo, $data);
        
        } elseif ($imagenExistente) {
            $imagenExistente->update($data);
            return $imagenExistente;
        
        } else {
            return null; 
        }
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

    private function deleteExistingImageByType(Producto $producto, string $tipo): void
    {
        $imagenAnterior = $producto->imagenes()->where('tipo', $tipo)->first();

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

    private function deleteImageFromStorage(string $url): void
    {
        $rutaRelativa = str_replace('/storage/', '', $url);
        Storage::disk('public')->delete($rutaRelativa);
    }
}
