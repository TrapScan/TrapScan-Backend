<?php

namespace App\Models;

use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trap extends Model
{
    use HasFactory;
    use SpatialTrait;

    protected $fillable = [
        'nz_trap_id',
        'qr_id',
        'trap_line_id',
        'project_id',
        'user_id',
        'coordinates',
        'name'
    ];

    protected $spatialFields = [
        'coordinates'
    ];

    public function getRouteKeyName() {
        return 'qr_id';
    }

    public function qrCode() {
        return $this->belongsTo(QR::class);
    }

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

    public function scopeNoCode($query) {
        return $query->whereNotNull('nz_trap_id')->whereNotNull('project_id')->whereNull('qr_id');
    }

    public function scopeMapped($query) {
        return $query->whereNotNull('nz_trap_id')->whereNotNull('project_id')->whereNotNull('qr_id');
    }

    public function scopeUnmapped($query) {
        return $query->whereNull('nz_trap_id')->whereNull('trap_line_id')->whereNull('user_id');
    }

    public function scopeUnmappedInProject($query) {
        return $query->whereNull('nz_trap_id')->whereNull('trap_line_id')->whereNull('user_id');
    }
}
