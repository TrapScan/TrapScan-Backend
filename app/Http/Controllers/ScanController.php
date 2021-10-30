<?php

namespace App\Http\Controllers;

use App\Models\QR;
use App\Models\Trap;
use Illuminate\Http\Request;

class ScanController extends Controller
{
    public function scan(Request $request, $qr_id) {
        $qr = QR::where('qr_code', $qr_id)->first();
        if(! $qr) {
            return response()->json(['message' => 'Trap not found'], 404);
        }

        if($qr->trap_id) {
            $trap = Trap::find($qr->trap_id);
            $last_inspection = $trap->inspections()->latest()->limit(1)->first();
            if($last_inspection) {
                $trap->last_checked = $last_inspection->updated_at->diffForHumans();
                $trap->last_checked_by = $last_inspection->user->name ?? 'Anonymous';
            }
            if($trap->inspections()->where('species_caught', '!=', 'None')->exists()) {
                $trap->last_caught = $trap->inspections()->where('species_caught', '!=', 'None')->first()->species_caught;
            }
            $trap-> total_catches = $trap->inspections()->where('species_caught', '!=', 'None')->count();
        } else {
            // The trap is unmapped, return just the code.
            // Frontend looks for nz_trap_id and will conditionally redirect
            // to the installation form or show error if needed
            $trap = ['qr_id' => $qr->qr_code];
        }

        return $trap;
    }

    public function anonScan(Request $request, $qr_id) {
        $qr = QR::where('qr_code', $qr_id)->get();
        if(! $qr->trap_id) {
            return response()->json(['message' => 'Trap not found'], 404);
        }

        return Trap::find($qr->trap_id);
    }
}
