<?php
namespace App\Refactor\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';
    protected $fillable = [
        'name', 'descripcion'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
