<?php

namespace App\Http\Controllers;

use App\Models\Trap;
use Illuminate\Http\Request;

class ScanController extends Controller
{
    public function scan(Request $request, $qr_id) {
        $trap = Trap::where('qr_id', $qr_id)->first();
        if(! $trap) {
            return response()->json(['message' => 'Trap not found'], 404);
        }

        return $trap;
    }

    public function anonScan(Request $request, $qr_id) {
        $trap = Trap::where('qr_id', $qr_id)->get();
        if(! $trap) {
            return response()->json(['message' => 'Trap not found'], 404);
        }

        return $trap;
    }
}
