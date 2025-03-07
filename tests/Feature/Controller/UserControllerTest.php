<?php

namespace Tests\Feature\Controller;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear el rol 'ADMIN' en la base de datos antes de cada prueba
        Role::create(['name' => 'ADMIN']);
    }
    
    #[Test]
    public function user_update_test()
    {
        // Crear usuario con todos los campos requeridos
        $user = User::factory()->create([
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'celular' => '987654321',
            'password' => bcrypt('password'),
        ]);

        // Asignar rol usando Spatie
        $user->assignRole('ADMIN');

        $this->actingAs($user, 'sanctum');

        $data = [
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'celular' => '987654321',
            'password' => bcrypt('password'),
        ];

        $response = $this->putJson(route('users.update', ['id' => $user->id]), $data);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => "Usuario actualizado correctamente."
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'celular' => '987654321',
            'password' => bcrypt('password'),
        ]);
    }

    #[Test]
    public function user_update_not_found_test()
    {
        // Crear usuario con los campos obligatorios
        $user = User::factory()->create([
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'celular' => '987654321',
            'password' => bcrypt('password'),
        ]);

        // Asignar rol usando Spatie
        $user->assignRole('ADMIN');

        $this->actingAs($user, 'sanctum');

        $data = [
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'celular' => '987654321',
            'password' => bcrypt('password'),
        ];

        // ID inexistente
        $response = $this->putJson(route('users.update', ['id' => 999]), $data);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => "Ocurrió un problema al actualizar al usuario"
            ]);
    }

    #[Test]
    public function user_not_update_if_data_is_the_same_test()
    {
        // Crear usuario con datos correctos
        $user = User::factory()->create([
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'celular' => '987654321',
            'password' => bcrypt('password'),
        ]);

        // Asignar rol usando Spatie
        $user->assignRole('ADMIN');

        $this->actingAs($user, 'sanctum');

        $data = [
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'celular' => '987654321',
            'password' => bcrypt('password'),
        ];

        $response = $this->putJson(route('users.update', ['id' => $user->id]), $data);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => "No hubo cambios en los datos del usuario."
            ]);
    }
}
