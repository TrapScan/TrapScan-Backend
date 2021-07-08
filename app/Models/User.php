<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function projects() {
        return $this->belongsToMany(Project::class);
    }

    public function traps() {
        return $this->hasMany(Trap::class);
    }

    public function inspections() {
        return $this->hasMany(Inspections::class, 'recorded_by', 'id');
    }

    public function profile() {
        return $this->hasOne(Profile::class);
    }

    public function providers() {
        return $this->hasMany(Provider::class, 'user_id', 'id');
    }
}
