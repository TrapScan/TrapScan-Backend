<?php

namespace App\Imports;

use App\Models\Project;
use App\Models\Trap;
use App\Models\TrapLine;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TrapImport implements ToCollection, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public $headings = [
        "Project",
        "ID",
        "Trap_line",
        "Trap_type",
        "Trap_sub_type",
        "Date_installed",
        "Retired",
        "Sensor_ID",
        "Sensor_provider",
        "Status",
        "Total_kills",
        "Lat",
        "Lon",
        "Notes",
    ];

    public function collection(Collection $collection)
    {
        foreach ($collection as $row) {
            $trap = Trap::where('name', $row['id'])->first();
            if($trap) {
                $trap->notes = $row['notes'];
                if($row['trap_line']){
                    $trap_line = TrapLine::where('name', $row['trap_line'])->first();
                    if(! $trap_line) {
                        $project = Project::where('name', $row['project'])->first();
                        if($project) {
                            $trap_line = TrapLine::create([
                               'project_id' => $project->id,
                               'name' => $row['trap_line']
                            ]);
                        }
                    }
                    if($trap->trapline?->name !== $row['trap_line']) {
                        $trap->trapline()->associate($trap_line);
                        $trap->save();
                    }
                }
            }
        }
    }
}
