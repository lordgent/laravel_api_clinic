<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\TransactionsUser;
use Illuminate\Http\Request;
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

        $validator = validator::make($request->all(), [
            'booking_id' => 'required|uuid|exists:booking,id',
            'clinic_id' => 'required|uuid|exists:clinics,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()
            ], 400);
        }

        $lastTransaction = TransactionsUser::where('clinic_id', $request->clinic_id)
            ->where('user_id', $request->user_id)
            ->orderByDesc('created_at')
            ->first();

        $noAntrian = $lastTransaction ? (intval($lastTransaction->no_antrian) + 1) : 1;

        $transactionCode = 'BT' . now()->format('YmdHis') . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
        $user = auth()->user()->id;
        $transactionUser = TransactionsUser::create([
            'id' => (string) Str::uuid(),
            'admin_fee' => '10000',
            'transaction_code' => $transactionCode,
            'status' => 'In Progress',
            'no_antrian' => $noAntrian,
            'user_id' => $user,
            'booking_id' => $request->booking_id,
            'clinic_id' => $request->clinic_id,
        ]);

        $booking->quota = $booking->quota - 1;
        $booking->save();

        return response()->json([
            'success' => true,
            'message' => 'Transaction created successfully.',
            'data' => $transactionUser
        ], 201);
    }
}
