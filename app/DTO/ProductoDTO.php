<?php

namespace App\DTO;

class ProductoDTO 
{
    public string $nombre;
    public string $categoria;
    public string $detalles;
    public string $disponibilidad;

    public function __construct($productoEloquent)
    {
        // Limpiamos y transformamos los datos crudos de MySQL
        $this->nombre = strtoupper($productoEloquent->nombre);
        $this->categoria = $productoEloquent->seccion ?? 'General';
        
        // Cortamos descripciones excesivamente largas para ahorrar tokens
        // Si la descripción es muy larga, la acortamos a 150 caracteres
        $textoDescripcion = $productoEloquent->descripcion ?? 'Sin descripción disponible.';
        $this->detalles = mb_strimwidth($textoDescripcion, 0, 400, '...');
        
        // Simplificamos la lógica de stock para la IA
        $this->disponibilidad = 'Disponible para entrega inmediata';
    }

    /**
     * Transforma una colección de productos de Eloquent en un array de DTOs limpios
     */
    public static function transformarColeccion($productos): array
    {
        return collect($productos)->map(fn($p) => new self($p))->toArray();
    }
}