<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\ServiceInfo;
use App\Models\TransactionsUser;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    /**
     * Create a new transaction for user.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {

        $booking = Booking::findOrFail($request->booking_id);
        $serviceInfo = ServiceInfo::findOrFail($request->service_info_id);

        $validator = validator::make($request->all(), [
            'booking_id' => 'required|uuid|exists:booking,id',
            'clinic_id' => 'required|uuid|exists:clinics,id',
            'service_info_id' => 'required|uuid'
        ]);
        $user = auth()->user()->id;
        $cekExist = TransactionsUser::where('clinic_id', $request->clinic_id)
        ->where('user_id', $user)
        ->where('status', 'waiting') // Cek antrian yang masih aktif
        ->exists();
    
    if ($cekExist) {
        return response()->json([
            'success' => false,
            'message' => "kamu sudah memiliki antrian di tempat ini"
        ], 400);
    }

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()
            ], 400);
        }

        $hariIndonesia = $booking->day;

        $hariInggris = [
            'Minggu' => 'Sunday',
            'Senin' => 'Monday',
            'Selasa' => 'Tuesday',
            'Rabu' => 'Wednesday',
            'Kamis' => 'Thursday',
            'Jumat' => 'Friday',
            'Sabtu' => 'Saturday'
        ];
        
        $hariEng = $hariInggris[$hariIndonesia];
        
        $today = new DateTime();
        $today->modify('this ' . $hariEng); 
        $formattedTanggal = $today->format('Y-m-d');

        $nextQueueNumber = DB::table('transactions_user')
        ->where('clinic_id',  $request->clinic_id)
        ->max('no_antrian') + 1;

        $transactionCode = 'HUB' . now()->format('YmdHis') . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
       
        $transactionUser = TransactionsUser::create([
            'id' => (string) Str::uuid(),
            'admin_fee' => '10000',
            'price' => $serviceInfo -> price,
            'transaction_code' => $transactionCode,
            'no_antrian' => $nextQueueNumber,
            'user_id' => $user,
            'booking_id' => $request->booking_id,
            'clinic_id' => $request->clinic_id,
            'active_date' => $formattedTanggal,
            'service_info_id' => $request->service_info_id
        ]);

        $booking-> quota = $booking->quota - 1;
        $booking->save();

        return response()->json([
            'success' => true,
            'message' => 'Transaction created successfully.',
            'data' => $transactionUser
        ], 201);
    }

    public function getAllByUserId()
{
    $userId = auth()->user()->id;
    
    $transactions = TransactionsUser::where('user_id', $userId)
        ->orderByDesc('created_at')
        ->with(['booking', 'clinic', 'serviceInfo'])
        ->get();
    
        return response()->json([
            'success' => true,
            'message' => 'Transactions retrieved successfully.',
            'data' => $transactions
        ], 200);
}

public function getDetailById($id)
{
    $transaction = TransactionsUser::with(['booking', 'clinic'])->find($id);
    
    if (!$transaction) {
        return response()->json([
            'success' => false,
            'message' => 'Transaction not found.'
        ], 404);
    }
    
    return response()->json([
        'success' => true,
        'message' => 'Transaction retrieved successfully.',
        'data' => $transaction
    ], 200);
}


    /**
     * Cek transaction for user.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
public function getCekTransaction(Request $request)
{
    $userId = auth()->user()->id;
    $cekExist = TransactionsUser::where('clinic_id', $request->clinic_id)
    ->where('user_id', $userId)
    ->whereIn('status', ['waiting', 'called', 'active'])
    ->exists();

    return response()->json([
        'success' => 200,
        'message' => 'success',
        'data' => $cekExist
    ], 200);
    
}


public function currentQueue($clinicId)
{
    $today = Carbon::today()->toDateString();

    $currentQueue = TransactionsUser::whereDate('active_date', $today)
        ->where('status', 'active')
        ->when($clinicId, function ($query) use ($clinicId) {
            return $query->where('clinic_id', $clinicId);
        })
        ->orderBy('no_antrian', 'asc')
        ->first();

    if ($currentQueue) {
        return response()->json([
            'success' => 200,
            'message' => 'success',
            'data' => [
                'queue_number' => $currentQueue->no_antrian,
                'clinic_id' => $currentQueue->clinic_id,
                'transaction_code' => $currentQueue->transaction_code,
                'status' => $currentQueue->status
            ]
        ], 200);

     
    
    }

    return response()->json([
        'status' => 'success',
        'message' => 'Belum ada antrean yang dipanggil'
    ]);
    


    
}

public function listQueue($clinicId)
{
    $today = Carbon::today()->toDateString();
    $queues = TransactionsUser::whereDate('active_date', $today)
    ->where('transactions_user.clinic_id', $clinicId) 
    ->join('users', 'transactions_user.user_id', '=', 'users.id')
    ->orderBy('transactions_user.no_antrian', 'asc')
    ->get([
        'transactions_user.id',
        'transactions_user.no_antrian as queue_number',
        'transactions_user.status',
        'users.name as patient_name'
    ]);


        return response()->json([
            'success' => 200,
            'message' => 'success',
            'data' => $queues
        ], 200);

}

public function callNextPatient($clinicId)
{
    $currentPatient = TransactionsUser::where('clinic_id', $clinicId)
        ->where('status', 'called')
        ->first();

    if ($currentPatient) {
        $currentPatient->status = 'active';
        $currentPatient->called_at = Carbon::now();
        $currentPatient->save();
    }

    $nextPatient = TransactionsUser::where('clinic_id', $clinicId)
        ->where('status', 'waiting')
        ->orderBy('no_antrian', 'asc')
        ->first();
    if ($nextPatient) {
        $nextPatient->status = 'called';
        $nextPatient->save();
    }

    return response()->json(['message' => 'Antrean diperbarui']);
}

public function updateStatus(Request $request)
{
    $request->validate([
        'transaction_id' => 'required',
    ]);

    $transaction = TransactionsUser::where('id', $request->transaction_id)->first();
    
    if (!$transaction) {
        return response()->json(['message' => 'Transaction not found'], 404);
    }

    DB::beginTransaction();
    try {
        $transaction->update(['status' => 'active']);
        DB::commit();
        
        return response()->json([
            'success' => 200,
            'message' => 'success',
            'data' => $transaction
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['message' => 'Failed to update transaction status', 'error' => $e->getMessage()], 500);
    }
}

}
