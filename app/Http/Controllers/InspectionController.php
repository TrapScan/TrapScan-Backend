<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use App\Models\Trap;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InspectionController extends Controller
{
    public function create(Request $request)
    {
        $validated_data = $request->validate([
            'QR_ID' => 'required',
            'code' => 'required',
            'date' => 'required|date|date_format:Y-m-d H:i:s',
            'recorded_by' => 'required',
            'strikes' => 'required',
            'species_caught' => 'required',
            'status' => 'required',
            'rebaited' => 'required',
            'bait_type' => 'required',
            'trap_condition' => 'required',
            'notes' => 'required',
            'words' => 'required',
            'trap_last_checked' => 'nullable|date',
        ]);

        $trap = Trap::where('qr_id', $validated_data['QR_ID'])->first();
        if (!$trap) {
            return response()->json([
                'error' => 'Error: Trap ' . $validated_data['QR_ID'] . ' does not exist'
            ], 422);
        }

        // Duplicate check
        $oneHourAgo = Carbon::now()->subHour();
        $duplicate = Inspection::where('trap_id', $trap->id)
            ->where('created_at', '>=', $oneHourAgo)->first();
        if (Inspection::where('trap_id', $trap->id)
            ->where('created_at', '>=', $oneHourAgo)->exists()) {
            return response()->json([
                'message' => 'Inspection was added < 1 hour ago, duplicate has been ignored',
                'data' => $duplicate->toArray()
            ],200);
        } else {
            $inspection = Inspection::create([
                'date' => $validated_data['date'],
                'trap_id' => $trap->id,
                'recorded_by' => $request->user()->id,
                'strikes' => $validated_data['strikes'],
                'species_caught' => $validated_data['species_caught'],
                'status' => $validated_data['status'],
                'rebaited' => $validated_data['rebaited'] === 'yes',
                'bait_type' => $validated_data['bait_type'],
                'trap_condition' => $validated_data['trap_condition'],
                'notes' => $validated_data['notes'],
                'words' => $validated_data['notes'],
            ]);

            return response()->json(['message' => 'Inspection  added', 'data' => $inspection->toArray()], 200);
        }
    }

    public function show(Inspection $inspection){
        return $inspection;
    }
}
