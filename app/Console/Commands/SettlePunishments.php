<?php

namespace App\Console\Commands;

use App\Services\StrikeService;
use Illuminate\Console\Command;

class SettlePunishments extends Command
{
    protected $signature = 'moderation:settle-punishments';

    protected $description = 'Start over the members whose mute or ban has run its course, and remove strikes that ran out';

    public function handle(StrikeService $strikes): int
    {
        // In this order: a punishment's end clears its strikes, which need no announcing one by one.
        $this->info("Settled {$strikes->settleFinished()} punishment(s).");
        $this->info("Retired {$strikes->retireExpiredStrikes()} expired strike(s).");

        return self::SUCCESS;
    }
}
