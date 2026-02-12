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
            Log::info('🔄 Iniciando actualización de producto', ['id' => $producto->id]);
            
            $this->updateBaseProducto($producto, $datosValidados);

            if ($request->has('etiqueta')) {
                $this->updateEtiqueta($producto, $datosValidados, $request);
            }

            // ==========================================
            // MANEJO INTELIGENTE DE IMÁGENES DE GALERÍA
            // ==========================================
            
            $imagenesNuevas = $request->file('imagenes_nuevas', []);
            $imagenesExistentes = $request->input('imagenes_existentes', []); 
            
            $hayImagenesNuevas = !empty($imagenesNuevas) && count($imagenesNuevas) > 0;
            $hayImagenesExistentes = !empty($imagenesExistentes) && count($imagenesExistentes) > 0;
            
            if ($hayImagenesNuevas || $hayImagenesExistentes) {
                
                Log::info('✅ Procesando cambios en imágenes de galería');

                // Obtener imágenes actuales de la BD PRIMERO para poder comparar URLs
                $imagenesActuales = $producto->imagenes()
                    ->where(function($query) {
                        $query->where('tipo', 'galeria')
                            ->orWhereNull('tipo');
                    })
                    ->get();

                // Recopilar IDs de imágenes a CONSERVAR (Buscando por ID o por URL)
                $idsAConservar = [];
                
                // Iteramos por lo que manda el frontend 
                foreach ($imagenesExistentes as $key => $imgData) {
                    $encontrada = false;

                    // Si viene con ID, lo usamos
                    if (!empty($imgData['id'])) {
                        $idsAConservar[] = $imgData['id'];
                        $encontrada = true;
                    } 
                    // Si NO viene ID pero viene URL, buscamos en la BD cuál coincide
                    elseif (!empty($imgData['url'])) {
                        $match = $imagenesActuales->first(function ($dbImg) use ($imgData) {
                            return $dbImg->url_imagen === $imgData['url'];
                        });

                        if ($match) {
                            $idsAConservar[] = $match->id;
                            // Inyectamos el ID encontrado al array del request para poder actualizar el ALT después
                            $imagenesExistentes[$key]['id'] = $match->id;
                            Log::info("🔍 Imagen recuperada por URL: {$imgData['url']} -> ID: {$match->id}");
                            $encontrada = true;
                        }
                    }

                    if (!$encontrada) {
                        Log::warning("⚠️ Imagen existente enviada por frontend no encontrada en BD: " . ($imgData['url'] ?? 'Sin URL'));
                    }
                }
                
                // Limpiamos duplicados
                $idsAConservar = array_unique($idsAConservar);

                Log::info('🔐 IDs confirmados a conservar:', $idsAConservar);
                Log::info('📋 Imágenes actuales en BD antes de borrar: ' . $imagenesActuales->count());

                //  Eliminar imágenes que NO están en la lista de conservar
                $contadorEliminadas = 0;
                foreach ($imagenesActuales as $imagen) {
                    if (!in_array($imagen->id, $idsAConservar)) {
                        Log::info("🗑️ ELIMINANDO imagen ID: {$imagen->id} | URL: {$imagen->url_imagen}");
                        
                        $this->imageService->deleteImageFromStorage($imagen->url_imagen);
                        $imagen->delete();
                        $contadorEliminadas++;
                    } else {
                         Log::info("🛡️ CONSERVANDO imagen ID: {$imagen->id}");
                    }
                }
                
                Log::info("✅ Total de imágenes eliminadas: {$contadorEliminadas}");

                //  Actualizar textos ALT de imágenes conservadas
                $contadorActualizadas = 0;
                foreach ($imagenesExistentes as $imgData) {
                    
                    if (isset($imgData['id']) && isset($imgData['alt'])) {
                        $updated = $producto->imagenes()
                            ->where('id', $imgData['id'])
                            ->update(['texto_alt_SEO' => $imgData['alt']]);
                        
                        if ($updated) {
                            $contadorActualizadas++;
                        }
                    }
                }

                //  Guardar imágenes NUEVAS
                if ($hayImagenesNuevas) {
                    $altTextsNuevos = $request->input('imagenes_nuevas_alt', []);
                    $contadorNuevas = 0;
                    foreach ($imagenesNuevas as $index => $file) {
                        $ruta = $this->imageService->guardarImagen($file);
                        $altText = $altTextsNuevos[$index] ?? "Imagen {$index}";
                        
                        $producto->imagenes()->create([
                            'url_imagen' => $ruta,
                            'texto_alt_SEO' => $altText,
                            'tipo' => 'galeria'
                        ]);
                        $contadorNuevas++;
                    }
                }
            } else {
                Log::info('⏭️ NO se detectaron cambios en imágenes - conservando las existentes');
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
            Log::error("Error al actualizar producto {$producto->id}: " . $e->getMessage());
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
