<?php

namespace App\Observers;

use App\Models\Pereaksi;
use App\Mail\StockAlert;
use Illuminate\Support\Facades\Mail;

class PereaksiObserver
{
    // private $alertReagents = [];
    // private $alertDebounceTime = 3600; // 1 hour in seconds
    // private static $lastAlertTime = null;

    // public function updating(Pereaksi $pereaksi)
    // {
    //     if ($pereaksi->Stock === 0) {
    //         $this->collectAlertReagent($pereaksi);
    //     } elseif ($pereaksi->Stock <= 500) {
    //         $this->collectAlertReagent($pereaksi);
    //     } else {
    //         // $pereaksi->Status = 'In Stock';
    //     }
    // }

    // private function collectAlertReagent($pereaksi)
    // {
    //     $this->alertReagents[] = $pereaksi;
    //     $this->checkAndSendNotifications();
    // }

    // private function checkAndSendNotifications()
    // {
    //     $currentTime = time();
        
    //     // Check if enough time has passed since the last alert
    //     if (self::$lastAlertTime === null || 
    //         ($currentTime - self::$lastAlertTime) >= $this->alertDebounceTime) {
            
    //         if (!empty($this->alertReagents)) {
    //             $this->sendNotification($this->alertReagents);
    //             $this->alertReagents = []; // Clear the collection
    //             self::$lastAlertTime = $currentTime;
    //         }
    //     }
    // }

    // private function sendNotification($alertReagents)
    // {
    //     // Daftar email yang akan menerima notifikasi
    //     $recipients = [
    //         [
    //             'email' => 'raflyrangga79@gmail.com',
    //             'name' => 'Rafly'
    //         ],
    //         [
    //             // 'email' => 'erlinda.mf@gmail.com',
    //             // 'name' => 'Erlinda'
    //         ],
    //     ];

    //     foreach ($recipients as $recipient) {
    //         $user = (object)[
    //             'name' => $recipient['name'],
    //             'email' => $recipient['email']
    //         ];
            
    //         Mail::to($recipient['email'])
    //             ->send(new StockAlert($alertReagents, $user));
    //     }
        
    // }
}