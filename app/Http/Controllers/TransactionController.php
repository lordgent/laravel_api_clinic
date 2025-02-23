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

        $serviceInfo = ServiceInfo::findOrFail($request->service_info_id);

        $validator = validator::make($request->all(), [
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
     
        $nextQueueNumber = DB::table('transactions_user')
        ->where('clinic_id',  $request->clinic_id)
        ->max('no_antrian') + 1;

        $transactionCode = 'HUB' . now()->format('YmdHis') . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
        $bookingDate = \Carbon\Carbon::createFromFormat('d-m-Y', $request->booking_date)->format('Y-m-d');
        $transactionUser = TransactionsUser::create([
            'id' => (string) Str::uuid(),
            'admin_fee' => '10000',
            'price' => $serviceInfo -> price,
            'transaction_code' => $transactionCode,
            'no_antrian' => $nextQueueNumber,
            'user_id' => $user,
            'clinic_id' => $request->clinic_id,
            'service_info_id' => $request->service_info_id,
            'booking_date'=> $bookingDate
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transaction created successfully.',
            'data' => $transactionUser
        ], 201);
    }

    public function getAllByUserId()
{
    $userId = auth()->user()->id;
    $today = Carbon::today()->format('Y-m-d');

    $transactions = TransactionsUser::where('user_id', $userId)
        ->orderByDesc('created_at')
        ->with(['clinic', 'serviceInfo'])
        ->get()
        ->map(function ($transaction) use ($today) {
            $bookingDate = Carbon::parse($transaction->booking_date)->format('Y-m-d');

            if ($bookingDate === $today && $transaction->status !== 'active' && $transaction->status !== 'completed') {
                $transaction->status = 'called';
            } else {
                $transaction->status;
            }

            return $transaction;
        });
    
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

        $latestTransaction = TransactionsUser::where('clinic_id', $request->clinic_id)
        ->where('user_id', $userId)
        ->orderByDesc('created_at') 
        ->first(); 

    $isRestrictedStatus = $latestTransaction && in_array($latestTransaction->status, ['waiting', 'called','active']);

        return response()->json([
            'success' => true,
            'message' => 'success',
            'data' => $isRestrictedStatus
        ], 200);
    }
    

public function currentQueue($clinicId)
{
    $today = Carbon::today()->toDateString();

    $currentQueue = TransactionsUser::whereDate('booking_date', $today)
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
    $queues = TransactionsUser::whereDate('booking_date', $today)
    ->where('transactions_user.clinic_id', $clinicId) 
    ->join('users', 'transactions_user.user_id', '=', 'users.id')
    ->orderBy('transactions_user.no_antrian', 'asc')
    ->get([
        'transactions_user.id',
        'transactions_user.no_antrian as queue_number',
        'transactions_user.status',
        'users.name as patient_name'
    ])
    ->map(function ($transaction) use ($today) {
        $bookingDate = Carbon::parse($transaction->booking_date)->format('Y-m-d');

        if ($bookingDate === $today && $transaction->status !== "active" && $transaction->status !== "completed") {
            $transaction->status = 'called';
        } elseif ($bookingDate < $today) {
            $transaction->status = 'expired';
        } else {
            $transaction->status;
        }

        return $transaction;
    });

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


public function updateStatusTransactionUser(Request $request, $id)
{
    $request->validate([
        'status' => 'required|string|in:approve,expired,completed',
    ]);

    $transaction = TransactionsUser::find($id);
    if (!$transaction) {
        return response()->json(['message' => 'Transaction not found'], 404);
    }

    if ($request->status === 'approve') {
        $transaction->status = 'active';
    } else if ($request->status === 'skip') {
        $transaction->status = 'missed';
    } else if($request->status === 'completed'){
        $transaction->status = 'completed';
    }

    $transaction->save();

    return response()->json([
        'message' => 'Transaction status updated successfully',
        'data' => $transaction
    ]);
}


}
