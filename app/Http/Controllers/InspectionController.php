<?php

namespace App\Http\Controllers;

use App\Jobs\SendCatchNotificationToCoordinators;
use App\Jobs\SendTrapIssueNotificationToCoordinators;
use App\Jobs\UploadToTrapNZ;
use App\Models\Inspection;
use App\Models\Trap;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InspectionController extends Controller
{
    public function createAnon(Request $request) {
        $validated_data = $request->validate([
            'QR_ID' => 'required',
            'species_caught' => 'required'
        ]);

        $trap = Trap::where('qr_id', $validated_data['QR_ID'])->first();
        if(! $trap) {
            return response()->json([
                'error' => 'Error: Trap ' . $validated_data['QR_ID'] . ' does not exist'
            ], 422);
        }

        $oneHourAgo = Carbon::now()->subMinutes(10);
        if(env('APP_ENV') === 'local') $oneHourAgo = Carbon::now()->subSeconds(20);
        if(Inspection::where('trap_id', $trap->id)->where('species_caught', $validated_data['species_caught'])
            ->where('created_at', '>=', $oneHourAgo)->exists()) {
            // Silently fail to the user to prevent spam of the same trap
            Log::info('Skipping store of anan inspection. Possible spam', $validated_data);
        } else {
            $inspection = Inspection::create([
                'date' => Carbon::now(),
                'trap_id' => $trap->id,
                'recorded_by' => null,
                'strikes' => 0,
                'species_caught' => $validated_data['species_caught'],
                'status' => 'Sprung',
                'rebaited' => false,
                'bait_type' => 'None',
                'trap_condition' => 'Unknown',
                'notes' => 'Anonymous inspection. Only contains species data',
                'words' => 'Anonymous inspection',
                'anon' => true
            ]);
        }

        return response()->json(['message' => 'Inspection  added'], 200);
    }
    public function create(Request $request)
    {
        $validated_data = $request->validate([
            'QR_ID' => 'required',
            'code' => 'required',
            'date' => 'required|date|date_format:Y-m-d H:i:s',
            'recorded_by' => 'nullable|integer',
            'strikes' => 'required',
            'species_caught' => 'required',
            'status' => 'required',
            'bait_type'=>'nullable|string',
            'rebaited' => 'required',
            'trap_condition' => 'required',
            'notes' => 'nullable|string',
            'words' => 'required',
            'trap_last_checked' => 'nullable|date',
            'upload_to_nz' => 'required',
        ]);

        $trap = Trap::where('qr_id', $validated_data['QR_ID'])->first();
        if (!$trap) {
            return response()->json([
                'error' => 'Error: Trap ' . $validated_data['QR_ID'] . ' does not exist'
            ], 422);
        }

        // Duplicate check
        $oneHourAgo = Carbon::now()->subMinutes(10);
        if(env('APP_ENV') === 'local') $oneHourAgo = Carbon::now()->subSeconds(20);
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
                'rebaited' => $validated_data['rebaited'] === 'Yes' || $validated_data['rebaited'] === 'yes',
                'bait_type' => ($validated_data['bait_type'] != null ? $validated_data['bait_type'] : 'None'),
                'trap_condition' => $validated_data['trap_condition'],
                'notes' => $validated_data['notes'] ?? null,
                'words' => $validated_data['words'],
                'upload_to_nz' => $validated_data['upload_to_nz'] ?? 0,
            ]);
            // Notifications
            if($inspection->species_caught && $inspection->species_caught !== 'None') {
                SendCatchNotificationToCoordinators::dispatch($inspection);
            }

            if($inspection->upload_to_nz) {
                UploadToTrapNZ::dispatch($inspection);
            }

            if($inspection->trap_condition === 'Needs maintenance') {
                SendTrapIssueNotificationToCoordinators::dispatch($inspection);
            }

            return response()->json(['message' => 'Inspection  added', 'data' => $inspection->toArray()], 200);
        }
    }

    public function show(Inspection $inspection){
        return $inspection;
    }
}
