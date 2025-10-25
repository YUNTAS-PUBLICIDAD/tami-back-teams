<?php
namespace App\Refactor\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';
    protected $fillable = [
        'name', 'email', 'password', 'email_verified_at', 'remember_token', 'role_id'
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function blogs()
    {
        return $this->hasMany(Blog::class);
    }
}
