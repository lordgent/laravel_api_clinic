<?php

namespace App\Console\Commands;

use App\Models\TransactionsUser;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateTransactionStatus extends Command
{
    protected $signature = 'transaction:update-status';
    protected $description = 'Mengubah status transaksi menjadi "called" jika active_date adalah hari ini';

    public function handle()
    {
        $today = Carbon::today()->toDateString();
        Log::info("Hari ini: $today");

        $waitingQueue = TransactionsUser::whereDate('active_date', $today)
            ->where('status', 'waiting')
            ->orderBy('no_antrian', 'asc')
            ->get();

        if ($waitingQueue->isNotEmpty()) {
            $waitingQueue->each(function ($queue) {
                $queue->update([
                    'status' => 'called',
                    'called_at' => Carbon::now()
                ]);
                Log::info("Antrean {$queue->no_antrian} dipanggil sebagai 'called'.");
            });
        }
    }
}
