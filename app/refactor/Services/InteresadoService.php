<?php
namespace App\Refactor\Services;

use App\Refactor\Models\Interesado;

class InteresadoService
{
    public function create(array $data): Interesado
    {
        return Interesado::create($data);
    }

    public function update(Interesado $interesado, array $data): Interesado
    {
        $interesado->update($data);
        return $interesado;
    }

    public function delete(Interesado $interesado): void
    {
        $interesado->delete();
    }
}
