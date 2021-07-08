<?php

namespace Database\Factories;

use App\Models\Trap;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrapFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Trap::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'nz_trap_id' => $this->faker->numberBetween(1, 100000),
            'qr_id' => getUniqueTrapId(),
        ];
    }
}
