<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property array catch_filter
 */
class UserProject extends Pivot
{
    protected $casts = [
        'catch_filter' => 'array'
    ];

    public function hasCatchFilter() {
        return $this->catch_filter ? true : false;
    }
}
