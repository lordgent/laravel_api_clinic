<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\ServiceInfo;
use Illuminate\Http\Request;

class ServiceInfoController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|string',
            'clinic_id' => 'required|exists:clinics,id'
        ]);

        $service = ServiceInfo::create([
            'name' => $request->name,
            'price' => $request->price,
            'clinic_id' => $request->clinic_id
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Service created successfully',
            'data' => $service
        ], 201);
    }

    public function getByClinicId($clinic_id)
{
    $clinic = Clinic::find($clinic_id);

    if (!$clinic) {
        return response()->json([
            'status' => 'error',
            'message' => 'Clinic not found'
        ], 404);
    }

    $services = ServiceInfo::where('clinic_id', $clinic_id)->get();

    return response()->json([
        'status' => 'success',
        'message' => 'Services fetched successfully',
        'data' => $services
    ], 200);
}


}
