<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DocumentType;
use App\Models\ClaimStatus;
use App\Models\ClaimType;
class Claims extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Tipos de Documento (Siguiendo tu migración: code y label)
        DocumentType::firstOrCreate(['code' => 'DNI'], ['label' => 'Documento Nacional de Identidad']);
        DocumentType::firstOrCreate(['code' => 'CE'], ['label' => 'Carnet de Extranjería']);
        DocumentType::firstOrCreate(['code' => 'PAS'], ['label' => 'Pasaporte']);

// 2. Estados del Reclamo
        ClaimStatus::firstOrCreate(['name' => 'Pendiente']);
        ClaimStatus::firstOrCreate(['name' => 'En proceso']);
        ClaimStatus::firstOrCreate(['name' => 'Atendido']);

// 3. Tipos de Reclamo
        ClaimType::firstOrCreate(['name' => 'Reclamo']);
        ClaimType::firstOrCreate(['name' => 'Queja']);
    }
}
