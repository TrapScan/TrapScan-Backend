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
        } else {
            // The trap is unmapped, return just the code.
            // Frontend looks for nz_trap_id and will conditionally redirect
            // to the installation form or show error if needed
            $trap = ['qr_id' => $qr->code];
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
