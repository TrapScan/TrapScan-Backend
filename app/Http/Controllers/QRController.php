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
            'user' => 'nullable|integer'
        ]);

        $ids = [];
        for ($i = 0; $i < $validated_data['number']; $i++) {
            $qr_id = getUniqueTrapId();
            Trap::create([
                'nz_trap_id' => null,
                'trap_line_id' => null,
                'qr_id' => $qr_id,
                'project_id' => null,
                'user_id' => null
            ]);
            $ids[] = $qr_id;
        }

        return ['new_qr_codes' => $ids];
    }

    public function createInProject(Request $request, Project $project) {
        $validated_data = $request->validate([
            'number' => 'required|integer',
            'user' => 'nullable|integer'
        ]);

        $ids = [];
        for ($i = 0; $i < $validated_data['number']; $i++) {
            $qr_id = getUniqueTrapId();
            Trap::create([
                'nz_trap_id' => null,
                'trap_line_id' => null,
                'qr_id' => $qr_id,
                'project_id' => $project->id,
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

    /*
     * This endpoint will be called only by admin users from the admin tool
     * This will facilitate bulk assignment if needed
     *
     * TODO: This method can be removed if that tool will be doing nothing extra (than mapQRCode())
     */
    public function mapQRCodeAdmin(Request $request) {
        $validated_data = $request->validate([
            'qr_id' => 'required|exists:traps,qr_id',
            'nz_id' => 'required'
        ]);
        $trap = Trap::where('qr_id', $validated_data['qr_id'])->first();
        $trap->nz_trap_id = $validated_data['nz_id'];
        $trap->save();

        return response()->json([
            'trap' => $trap,
            'message' => 'Trap has been mapped successfully'
        ]);
    }

    /*
     * This function will be called by general users from the scanning application
     */
    public function mapQRCode(Request $request) {
        $validated_data = $request->validate([
            'qr_id' => 'required|exists:traps,qr_id',
            'nz_id' => 'required'
        ]);
        $user = $request->user();
        $project = Trap::where('qr_id', $validated_data['qr_id'])->first()->project;

        // Do some extra validation if the user is not admin
        if(! $request->user()->hasRole('admin')) {
            // Check if the user is a pcord for this trap
            if( $user->isCoordinatorOf($project)) {
                $trap = Trap::where('qr_id', $validated_data['qr_id'])->first();
                $trap->nz_trap_id = $validated_data['nz_id'];
                $trap->save();

                return $trap;
            } else {
                return response()->json(['Message' => 'You are not a coordinator for this project'], 403);
            }
        } else {
            // Allow admins to rewrite codes without checking
            $trap = Trap::where('qr_id', $validated_data['qr_id'])->first();
            $trap->nz_trap_id = $validated_data['nz_id'];
            $trap->save();
        }
    }
}
