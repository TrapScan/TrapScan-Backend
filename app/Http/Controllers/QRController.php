<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\QR;
use App\Models\Trap;
use App\Models\UserProject;
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
            $qr_code = getUniqueTrapId();
            QR::create(['qr_code' => $qr_code]);
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
        return QR::whereNull('trap_id')->get();
    }

    public function unmappedInProject(Project $project) {
        return Trap::where('project_id', $project->id)->unmappedInProject()->get();
    }

    public function noCode(Request $request) {
        $user = $request->user();
        if($user && $user->hasRole('admin')){
            return Trap::select('id', 'project_id', 'nz_trap_id', 'name', 'coordinates', 'qr_id')
                ->noCode()->with('project')->get();
        }
        $projects = $user->isInProject();
        $ids = collect();
        foreach ($projects as $pr){
            $ids->push($pr->id);
        }

        return Trap::select('id', 'project_id', 'nz_trap_id', 'name', 'coordinates', 'qr_id')
            ->whereIn('project_id',$ids)
                ->noCode()->with('project')->get();
    }

    /*
     * This endpoint will be called only by admin users from the admin tool
     * This will facilitate bulk assignment if needed
     *
     */
    public function mapQRCodeAdmin(Request $request) {
        $validated_data = $request->validate([
            'qr_id' => 'required|exists:qr,qr_code',
            'nz_id' => 'required|exists:traps,nz_trap_id'
        ]);

        $qr = QR::where('qr_code', $validated_data['qr_id'])->first();
        $trap = Trap::where('nz_trap_id', $validated_data['nz_id'])->first();
        $oldQR = QR::where('qr_code', $trap->qr_id)->first();

        // Check if Pcord or Admin
        $user = $request->user();
        $project = $trap->project;
        if($user->hasRole('admin') || $user->isCoordinatorOf($project)) {
            // Map new code
            $trap->qr_id = $qr->qr_code;
            $qr->trap_id = $trap->id;
            $trap->save();
            $qr->save();

            // Remove old mapping in QR table
            if($oldQR) {
                $oldQR->trap_id = null;
                $oldQR->save();
            }

            return response()->json([
                'trap' => $trap,
                'message' => 'Trap has been mapped successfully'
            ]);
        } else {
            return response()->json([
                'message' => "You don't have permission to do that"
            ], 403);
        }
    }

    /*
     * This function will be called by general users from the scanning application
     *      * TODO: This method can be removed if that tool will be doing nothing extra (than mapQRCode())

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
