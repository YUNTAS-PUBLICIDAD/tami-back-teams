<?php

namespace App\Services;

use App\Models\Producto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Events\ProductoCreado;
use App\Events\ProductoActualizado;
use App\Events\ProductoEliminado;

class ProductoService
{
    public function __construct(
        private ProductoImageService $imageService,
        private KeywordProcessorService $keywordService
    ) {}
/**
     * Crea un nuevo producto con todas sus relaciones.
     *
     * @param array $datosValidados Datos validados del producto
     * @param \Illuminate\Http\Request $request Request original con archivos
     * @return Producto
     * @throws \Exception
     */

    public function createProducto(array $datosValidados, $request): Producto
    {
        DB::beginTransaction();
        try {
            $producto = $this->createBaseProducto($datosValidados);

            if ($request->has('etiqueta')) {
                $this->createEtiqueta($producto, $datosValidados, $request);
            }

            $producto->productosRelacionados()->sync($datosValidados['relacionados'] ?? []);

            if (isset($datosValidados['imagenes'])) {
                $this->imageService->saveGalleryImages(
                    $producto,
                    $datosValidados['imagenes'],
                    $datosValidados['textos_alt'] ?? []
                );
            }

            $this->saveSpecialImages($producto, $request);
            $this->syncEspecificaciones($producto, $datosValidados['especificaciones'] ?? null);

            if (isset($datosValidados['dimensiones']) && is_array($datosValidados['dimensiones'])) {
                $this->createDimensiones($producto, $datosValidados['dimensiones']);
            }

            DB::commit();
            event(new ProductoCreado($producto));
            return $producto;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear producto: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw $e;
        }
    }

     /**
     * Actualiza un producto existente con todas sus relaciones.
     *
     * @param Producto $producto Instancia del producto a actualizar
     * @param array $datosValidados Datos validados
     * @param \Illuminate\Http\Request $request Request con archivos
     * @return Producto
     * @throws \Exception
     */
    public function updateProducto(Producto $producto, array $datosValidados, $request): Producto
    {
        DB::beginTransaction();
        try {

            $this->updateBaseProducto($producto, $datosValidados);

            if ($request->has('etiqueta')) {
                $this->updateEtiqueta($producto, $datosValidados, $request);
            }

            // ============================================
            // MANEJO INTELIGENTE DE IMÁGENES DE LA GALERÍA
            // ============================================

            $imagenesNuevas = $request->file('imagenes_nuevas', []);
            $imagenesExistentes = $request->input('imagenes_existentes', []);
            $imagenesEditadasDatos = $request->input('imagenes_editadas', []);
            $imagenesEditadasArchivos = $request->file('imagenes_editadas', []);

            $idsAConservar = [];

            foreach ($imagenesExistentes as $imgData) {
                if (!empty($imgData['id'])) {
                    $idsAConservar[] = $imgData['id'];
                } elseif (!empty($imgData['url'])) {
                    $match = $producto->imagenes()->where('url_imagen', $imgData['url'])->first();
                    if ($match) $idsAConservar[] = $match->id;
                }
            }

            foreach ($imagenesEditadasDatos as $editData) {
                if (!empty($editData['id'])) {
                    $idsAConservar[] = $editData['id'];
                }
            }

            $idsAConservar = array_unique($idsAConservar);

            $producto->imagenes()
                ->where(function ($q) {
                    $q->where('tipo', 'galeria')->orWhereNull('tipo');
                })
                ->whereNotIn('id', $idsAConservar)
                ->get()
                ->each(function ($img) {
                    $this->imageService->deleteImageFromStorage($img->url_imagen);
                    $img->delete();
                });

            if (!empty($imagenesEditadasDatos)) {
                foreach ($imagenesEditadasDatos as $index => $data) {

                    if (isset($imagenesEditadasArchivos[$index]['file'])) {

                        $file = $imagenesEditadasArchivos[$index]['file'];
                        $id = $data['id'];
                        $alt = $data['alt'] ?? '';

                        $imagenDb = $producto->imagenes()->find($id);

                        if ($imagenDb) {

                            $this->imageService->deleteImageFromStorage($imagenDb->url_imagen);
                            $nuevaRuta = $this->imageService->guardarImagen($file);

                            $imagenDb->update([
                                'url_imagen' => $nuevaRuta,
                                'texto_alt_SEO' => $alt
                            ]);
                        }
                    }
                }
            }

            foreach ($imagenesExistentes as $imgData) {

                if (isset($imgData['id']) && isset($imgData['alt'])) {

                    $producto->imagenes()
                        ->where('id', $imgData['id'])
                        ->update(['texto_alt_SEO' => $imgData['alt']]);
                }
            }

            if (!empty($imagenesNuevas)) {

                $altTextsNuevos = $request->input('imagenes_nuevas_alt', []);

                foreach ($imagenesNuevas as $index => $file) {

                    $ruta = $this->imageService->guardarImagen($file);
                    $altText = $altTextsNuevos[$index] ?? "";

                    $producto->imagenes()->create([
                        'url_imagen' => $ruta,
                        'texto_alt_SEO' => $altText,
                        'tipo' => 'galeria'
                    ]);
                }
            }

            $this->saveSpecialImages($producto, $request);

            if (isset($datosValidados['especificaciones'])) {
                $producto->especificaciones()->delete();
                $this->syncEspecificaciones($producto, $datosValidados['especificaciones']);
            }

            if (isset($datosValidados['dimensiones'])) {
                $producto->dimensiones()->updateOrCreate(
                    ['id_producto' => $producto->id],
                    $datosValidados['dimensiones']
                );
            }

            if (isset($datosValidados['relacionados'])) {
                $producto->productosRelacionados()->sync($datosValidados['relacionados']);
            }

            DB::commit();

            event(new ProductoActualizado($producto));
            return $producto->fresh();
        } catch (\Exception $e) {

            DB::rollBack();
            throw $e;
        }
    }

    public function deleteProductoCompleto(Producto $producto): void
    {
        DB::beginTransaction();
        try {

            $this->imageService->deleteAllImagesFromStorage($producto);

            $producto->imagenes()->delete();
            $producto->especificaciones()->delete();
            $producto->etiqueta()->delete();
            $producto->dimensiones()->delete();
            $producto->productosRelacionados()->detach();

            $producto->delete();

            DB::commit();
            event(new ProductoEliminado($producto));
        } catch (\Exception $e) {

            DB::rollBack();
            Log::error("Error al eliminar producto {$producto->id}: " . $e->getMessage());
            throw $e;
        }
    }

    private function createBaseProducto(array $datos): Producto
    {
        return Producto::create([
            'nombre' => $datos['nombre'] ?? null,
            'link' => $datos['link'] ?? null,
            'titulo' => $datos['titulo'] ?? null,
            'subtitulo' => $datos['subtitulo'] ?? null,
            'stock' => $datos['stock'] ?? null,
            'precio' => $datos['precio'] ?? null,
            'seccion' => $datos['seccion'] ?? null,
            'descripcion' => $datos['descripcion'] ?? null,
            'video_url' => $datos['video_url'] ?? null,
        ]);
    }

    private function updateBaseProducto(Producto $producto, array $datos): void
    {
        $camposPermitidos = [
            'nombre',
            'link',
            'titulo',
            'subtitulo',
            'stock',
            'precio',
            'seccion',
            'descripcion',
            'video_url'
        ];

        $camposActualizar = array_filter(
            $datos,
            fn($key) => in_array($key, $camposPermitidos) && array_key_exists($key, $datos),
            ARRAY_FILTER_USE_KEY
        );

        if (!empty($camposActualizar)) {
            $producto->update($camposActualizar);
        }
    }

    private function createEtiqueta(Producto $producto, array $datos, $request): void
    {
        $keywords = $this->keywordService->processKeywordsFromJson($datos['keywords'] ?? null);

        $producto->etiqueta()->create([
            'meta_titulo' => $request->etiqueta['meta_titulo'] ?? null,
            'meta_descripcion' => $request->etiqueta['meta_descripcion'] ?? null,
            'keywords' => $keywords,
            'popup_estilo' => $request->etiqueta['popup_estilo'] ?? null,
            'popup3_sin_fondo' => filter_var(
                $request->etiqueta['popup3_sin_fondo'] ?? false,
                FILTER_VALIDATE_BOOLEAN
            ),
            // NUEVOS CAMPOS
            'popup_button_color' => $request->etiqueta['popup_button_color'] ?? null,
            'popup_text_color' => $request->etiqueta['popup_text_color'] ?? null,
            'popup_button_text' => $request->etiqueta['popup_button_text'] ?? null,
        ]);
    }

    private function updateEtiqueta(Producto $producto, array $datos, $request): void
    {
        $keywords = $this->keywordService->processKeywordsFromJson($datos['keywords'] ?? null);

        $producto->etiqueta()->updateOrCreate(
            ['producto_id' => $producto->id],
            [
                'meta_titulo' => $request->etiqueta['meta_titulo'] ?? null,
                'meta_descripcion' => $request->etiqueta['meta_descripcion'] ?? null,
                'keywords' => $keywords,
                'popup_estilo' => $request->etiqueta['popup_estilo'] ?? null,
                'popup3_sin_fondo' => filter_var(
                    $request->etiqueta['popup3_sin_fondo'] ?? false,
                    FILTER_VALIDATE_BOOLEAN
                ),
                // NUEVOS CAMPOS
                'popup_button_color' => $request->etiqueta['popup_button_color'] ?? null,
                'popup_text_color' => $request->etiqueta['popup_text_color'] ?? null,
                'popup_button_text' => $request->etiqueta['popup_button_text'] ?? null,
            ]
        );
    }

    private function saveSpecialImages(Producto $producto, $request): void
    {
        $tiposImagenes = [
            ['popup', 'imagen_popup', 'texto_alt_popup'],
            ['email', 'imagen_email', 'asunto'],
            ['whatsapp', 'imagen_whatsapp', 'texto_alt_whatsapp'],
        ];

        foreach ($tiposImagenes as [$tipo, $imagenKey, $textoKey]) {
            $this->imageService->handleSpecialImage(
                $producto,
                $request->file($imagenKey),
                $tipo,
                $request->input($textoKey)
            );
        }
    }

    private function syncEspecificaciones(Producto $producto, ?string $especificacionesJson): void
    {
        if (!$especificacionesJson) return;

        $especificaciones = json_decode($especificacionesJson, true);

        if (!is_array($especificaciones)) return;

        foreach ($especificaciones as $valor) {
            $producto->especificaciones()->create(['valor' => $valor]);
        }
    }

    private function createDimensiones(Producto $producto, array $dimensiones): void
    {
        $producto->dimensiones()->create([
            'alto' => $dimensiones['alto'] ?? null,
            'largo' => $dimensiones['largo'] ?? null,
            'ancho' => $dimensiones['ancho'] ?? null,
        ]);
    }
}
