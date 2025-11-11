<?php

namespace App\Console\Commands;

use App\Mail\StockAlert;
use App\Models\Pereaksi;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendStockAlert extends Command
{
    protected $signature = 'stock:alert';
    protected $description = 'Send daily stock alert for reagents that are out of stock or under stock';

    public function handle()
    {
        // Ambil semua pereaksi yang out of stock (0) atau under stock (<= 500)
        $alertReagents = Pereaksi::where(function ($query) {
            $query->where('Stock', 0) // Out of Stock
                  ->orWhereRaw('Stock <= min_stock'); // Under Stock
        })->get();

        if ($alertReagents->isEmpty()) {
            $this->info('No reagents are out of stock or under stock.');
            return;
        }

        // Ambil semua user dari tabel User
        $users = User::all();

        if ($users->isEmpty()) {
            $this->error('No users found to send the alert.');
            return;
        }

        // Kirim email ke setiap user
        foreach ($users as $user) {
            Mail::to($user->email)->queue(new StockAlert($alertReagents));
        }

        $this->info('Stock alert emails have been queued for ' . $users->count() . ' users.');
    }
}