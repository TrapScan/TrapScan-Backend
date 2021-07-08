<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inspections extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'trap_id',
        'recorded_by',
        'strikes',
        'species_caught',
        'status',
        'rebaited',
        'bait_type',
        'trap_condition',
        'notes',
        'words'
    ];

    public function trap() {
        return $this->hasOne(Trap::class);
    }

    public function user() {
        return $this->hasOne(User::class, 'recorded_by', 'id');
    }
}
