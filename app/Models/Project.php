<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    const USER_PROJECT_COORDINATOR_SETTINGS = [
        'notify_catches',
        'notify_inspections',
        'notify_problems'
    ];
    const USER_PROJECT_COORDINATOR_LABELS = [
        'Catches',
        'Inspections',
        'Problems'
    ];

    protected $fillable = [
        'name',
        'description'
    ];

    public function traps() {
        return $this->hasMany(Trap::class);
    }

    public function traplines() {
        return $this->hasMany(TrapLine::class);
    }

    public function users() {
        return $this->belongsToMany(User::class);
    }

    public function coordinators() {
        return $this->users()->wherePivot('coordinator', '=', true);
    }

    public function inspections() {
        return $this->hasManyThrough(Inspection::class, Trap::class);
    }
}
