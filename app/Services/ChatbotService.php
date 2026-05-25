<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Services\ProductoImageService; // <-- Asegúrate de mapear la ruta real de tu servicio de imágenes

use App\Models\ChatbotConfig; // <-- *ASUNCIÓN*: Reemplaza esto por tu modelo real de configuraciones

class ChatbotService
{
    // Inyectamos ProductoImageService mediante propiedad de constructor (Igual que en tu ProductService)
    public function __construct(
        private ProductoImageService $imageService
    ) {}

    /**
     * Sube el nuevo ícono del chatbot y lo actualiza en la base de datos.
     *
     * @param UploadedFile $archivo
     * @return string URL pública del nuevo ícono
     * @throws \Exception
     */
    public function updateIconoChatbot(UploadedFile $archivo): string
    {
        DB::beginTransaction();
        try {
            // 1. Reutilizamos tu función del servicio de imágenes
            $urlPublica = $this->imageService->guardarImagen($archivo);

            // 2. Buscamos el registro de configuración (o creamos uno si está vacío)

            // buscará la fila de configuración en la tabla 'chatbot_configs',
            // y si la tabla está vacía (porque es la primera vez), creará el registro automáticamente.
            $config = ChatbotConfig::firstOrCreate([]); 
            $config->url_icono = $urlPublica; 
            $config->save();

            DB::commit();
            return $urlPublica;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar el ícono del chatbot: ' . $e->getMessage());
            throw $e;
        }
    }
}