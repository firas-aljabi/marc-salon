<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Reservation;
use App\Notifications\ReservationAlertNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AlertClient extends Command
{
    protected $signature = 'alert:cron';

    protected $description = 'Send alert or reminder to customers before their reservation date';

    public function handle()
    {
        $currentDate = Carbon::now();

        $alertDate = $currentDate->addDays(2)->format('Y-m-d');

        $reservations = Reservation::whereDate('date', $alertDate)->get();

        foreach ($reservations as $reservation) {
            $client = Client::where('id', $reservation->client_id)->first();
            $client->notify(new ReservationAlertNotification($reservation, $client));
        }

        return Command::SUCCESS;
    }
}
