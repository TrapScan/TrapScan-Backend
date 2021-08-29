<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Trap;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrapFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected string $model = Trap::class;

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
            'name' => $this->faker->name,
            'coordinates' => new Point($this->faker->latitude, $this->faker->longitude)
        ];
    }

    public function unmapped() {
        return $this->state(function (array $attributes) {
           return [
               'nz_trap_id' => null,
               'trap_line_id' => null,
               'project_id' => null,
               'user_id' => null,
               'name' => $this->faker->name,
               'coordinates' => new Point($this->faker->latitude, $this->faker->longitude)
           ];
        });
    }

    public function unmappedInProject(Project $project) {
        return $this->state(function (array $attributes) use($project) {
            return [
                'nz_trap_id' => null,
                'trap_line_id' => null,
                'project_id' => $project->id,
                'user_id' => null,
                'name' => $this->faker->name,
                'coordinates' => new Point($this->faker->latitude, $this->faker->longitude)
            ];
        });
    }
}
