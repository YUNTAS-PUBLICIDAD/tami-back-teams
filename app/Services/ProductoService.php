<?php

namespace App\Services;

use App\Models\Producto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

            if (isset($datosValidados['imagenes'])) {
                $this->imageService->deleteGalleryImages($producto);
                $this->imageService->saveGalleryImages(
                    $producto,
                    $request->file('imagenes', []),
                    $datosValidados['textos_alt'] ?? []
                );
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
            return $producto->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al actualizar producto {$producto->id}: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw $e;
        }
    }

    /**
     * Elimina un producto completamente incluyendo todas sus relaciones y archivos.
     *
     * @param Producto $producto Instancia del producto a eliminar
     * @return void
     * @throws \Exception
     */
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
            'nombre', 'link', 'titulo', 'subtitulo', 
            'stock', 'precio', 'seccion', 'descripcion', 'video_url'
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
        if (!$especificacionesJson) {
            return;
        }

        $especificaciones = json_decode($especificacionesJson, true);

        if (!is_array($especificaciones)) {
            return;
        }

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
