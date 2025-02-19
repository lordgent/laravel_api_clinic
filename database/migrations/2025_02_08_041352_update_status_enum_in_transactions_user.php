<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE transactions_user MODIFY COLUMN status ENUM('waiting', 'called', 'missed', 'completed', 'cancel', 'active') DEFAULT 'waiting'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE transactions_user MODIFY COLUMN status ENUM('waiting', 'called', 'missed', 'completed', 'cancel') DEFAULT 'waiting'");
    }
};
