<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Trap;
use Illuminate\Http\Request;

class QRController extends Controller
{
    public function create(Request $request) {
        $validated_data = $request->validate([
            'number' => 'required|integer',
            'project' => 'nullable|integer',
            'user' => 'nullable|integer'
        ]);

        $ids = [];
        $project = Project::find($validated_data['project'])->exists();
        for ($i = 0; $i < $validated_data['number']; $i++) {
            $qr_id = getUniqueTrapId();
            Trap::create([
                'nz_trap_id' => null,
                'trap_line_id' => null,
                'qr_id' => $qr_id,
                'project_id' => $project ?: $validated_data['project'],
                'user_id' => null
            ]);
            $ids[] = $qr_id;
        }

        return ['new_qr_codes' => $ids];
    }

    public function unmapped(Request $request) {
        return Trap::unmapped()->get();
    }

    public function unmappedInProject(Project $project) {
        return Trap::where('project_id', $project->id)->unmappedInProject()->get();
    }
}
