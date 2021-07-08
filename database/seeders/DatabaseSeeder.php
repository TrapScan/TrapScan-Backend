<?php

namespace Database\Seeders;

use App\Models\Inspections;
use App\Models\Project;
use App\Models\Trap;
use App\Models\TrapLine;
use App\Models\User;
use Illuminate\Database\Seeder;
use function Symfony\Component\Translation\t;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $userCount = 20;
        $projectCount = 10;
        $trapCount = 10;
        $inspectionsCount = 3;
        $trapLinesCount = 2; // Keep less than projectCount

        $users = User::factory($userCount)->create();
        $projects = Project::factory($projectCount)->has(
            Trap::factory()->count($trapCount)
        )->create()->toArray();

        foreach ($users as $user) {
            // Make a user part of 3 random projects
            $randomProjects = array_rand($projects, 3);
            foreach ($randomProjects as $project) {
                $user->projects()->attach($project);
            }

            // In each project give it's users a random amount of inspections
            // on each trap in the project
            foreach ($user->projects as $userProject) {
                foreach ($userProject->traps as $userTrap) {
                    $rand = rand(0, $inspectionsCount);
                    Inspections::factory($rand)->create([
                        'recorded_by' => $user->id,
                        'trap_id' => $userTrap->id
                    ]);
                }
            }
        }

        // Create a couple traplines with half of the traps from a project
        for($i=0; $i < $trapLinesCount; $i++) {
            $project = Project::all()->random(1)->first();
            if(! $project->traplines()->exists()){
                // Create a trapline from half the traps in the project
                $traps = $project->traps->random($project->traps->count() /2);
                $trapLine = TrapLine::factory(1)->create([
                    'project_id' => $project->id,
                ])->first();
                $trapLine->traps()->saveMany($traps);
            }
        }
    }
}
