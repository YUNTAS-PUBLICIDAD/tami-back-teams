<?php

namespace Database\Seeders;

use App\Models\ClienteSource;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClienteSourcesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sources = ['Inicio', 'Producto detalle', 'Administración'];
        
        foreach ($sources as $source) {
            ClienteSource::firstOrCreate(['name' => $source]);
        }
    }
}
