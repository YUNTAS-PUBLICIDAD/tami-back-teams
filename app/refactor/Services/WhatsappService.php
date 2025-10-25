<?php
namespace App\Refactor\Services;

use App\Refactor\Models\Whatsapp;

class WhatsappService
{
    public function create(array $data): Whatsapp
    {
        return Whatsapp::create($data);
    }

    public function update(Whatsapp $whatsapp, array $data): Whatsapp
    {
        $whatsapp->update($data);
        return $whatsapp;
    }

    public function delete(Whatsapp $whatsapp): void
    {
        $whatsapp->delete();
    }
}
