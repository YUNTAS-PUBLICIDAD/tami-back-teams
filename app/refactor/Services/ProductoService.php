<?php
namespace App\Refactor\Services;

use App\Refactor\Models\Producto;

class ProductoService
{
    public function create(array $data): Producto
    {
        return Producto::create($data);
    }

    public function update(Producto $producto, array $data): Producto
    {
        $producto->update($data);
        return $producto;
    }

    public function delete(Producto $producto): void
    {
        $producto->delete();
    }
}
