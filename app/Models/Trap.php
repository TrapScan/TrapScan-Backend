<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trap extends Model
{
    use HasFactory;

    protected $fillable = [
        'nz_trap_id',
        'qr_id',
        'trap_line_id',
        'project_id',
        'user_id',
    ];

    public function trapline() {
        return $this->belongsTo(TrapLine::class);
    }

    public function project() {
        return $this->belongsTo(Project::class);
    }

    public function owner() {
        return $this->belongsTo(User::class);
    }

    public function inspections() {
        return $this->hasMany(Inspection::class);
    }

    public function scopeUnmapped($query) {
        return $query->whereNull('nz_trap_id')->whereNull('trap_line_id')->whereNull('project_id')->whereNull('user_id');
    }

    public function scopeUnmappedInProject($query) {
        return $query->whereNull('nz_trap_id')->whereNull('trap_line_id')->whereNull('user_id');
    }
}
