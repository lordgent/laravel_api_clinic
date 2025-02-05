<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Clinic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ClinicsController extends Controller
{
     /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addClinic(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|string|max:255',
            'address' => 'string|max:255',
            'phone' => 'string|max:15',
            'photo' => 'nullable|string',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ]);
        }
        $user_id = auth()->user()->id;
        $photoPath = null;

        if ($request->photo) {
            $photoPath = $this->saveBase64Image($request->photo);
        }
        try {
            $clinic = Clinic::create([
                'name' => $request->name,
                'category_id' => $request->category_id,
                'address' => $request->address,
                'phone' => $request->phone,
                'photo' => $photoPath,
                'user_id' => $user_id,
                'price' => $request -> price
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Clinic created successfully',
                'data' => $clinic,
                'error' => null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to create clinic',
                'error' => $e->getMessage()
            ]);
        }

    }

    private function saveBase64Image($base64Image)
{
    try {
        // Cek apakah string base64 valid
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $matches)) {
            $imageType = $matches[1]; // Ambil tipe file (png, jpg, jpeg)
            $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
            $base64Image = base64_decode($base64Image);

            // Buat nama file unik
            $fileName = 'clinic_' . time() . '.' . $imageType;
            $filePath = 'uploads/clinics/' . $fileName;

            // Simpan file ke dalam storage public
            Storage::disk(name: 'public')->put($filePath, $base64Image);

            return $filePath; // Simpan path untuk database
        }

        return null;
    } catch (\Exception $e) {
        return null;
    }
}

public function getClinic()
{
    try {
        $clinics = Clinic::all();

        if ($clinics->isEmpty()) {
            return response()->json([
                'status' => 404,
                'message' => 'No clinics found',
                'data' => [],
                'error' => null
            ]);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Clinics fetched successfully',
            'data' => $clinics,
            'error' => null
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'message' => 'Failed to fetch clinics',
            'error' => $e->getMessage()
        ]);
    }
}


public function getClinicByName($name)
{
    try {
        $clinic = Clinic::where('name', 'like', '%' . $name . '%')->first();
        
        if (!$clinic) {
            return response()->json([
                'status' => 404,
                'message' => 'Clinic not found',
                'data' => null,
                'error' => null
            ]);
        }


        return response()->json([
            'status' => 200,
            'message' => 'Clinic fetched successfully',
            'data' => $clinic,
            'error' => null
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'message' => 'Failed to fetch clinic',
            'error' => $e->getMessage()
        ]);
    }
}

public function createScedhule(Request $request): JsonResponse
{
    $request->validate([
        'quota' => 'required|integer|min:1',
        'clinic_id' => 'required|exists:clinics,id',
        'day' => 'required|string',
        'open_time' => 'required|date_format:H:i', 
        'close_time' => 'required|date_format:H:i',
    ]);

    try {
        $booking = Booking::create([
            'id' => Str::uuid(),
            'day' => $request->day,
            'quota' => $request->quota,
            'clinic_id' => $request->clinic_id,
            'open_time' => $request->open_time,
            'close_time' => $request->close_time,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Booking created successfully',
            'data' => $booking
        ], 201);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to create booking',
            'error' => $e->getMessage()
        ], 500);
    }
}


public function getByClinicId($clinic_id): JsonResponse
{
    try {
        $bookings = Booking::where('clinic_id', $clinic_id)
        ->orderBy('created_at', 'asc') // Mengurutkan berdasarkan created_at secara menurun
        ->get();

        if ($bookings->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No bookings found for this clinic',
                'data' => []
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Bookings retrieved successfully',
            'data' => $bookings
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve bookings',
            'error' => $e->getMessage()
        ], 500);
    }
}

}
