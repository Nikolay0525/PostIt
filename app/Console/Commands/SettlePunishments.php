<?php

namespace App\Console\Commands;

use App\Services\StrikeService;
use Illuminate\Console\Command;

class SettlePunishments extends Command
{
    protected $signature = 'moderation:settle-punishments';

    protected $description = 'Delete the strikes of members whose mute or ban has run its course, so they start over';

    public function handle(StrikeService $strikes): int
    {
        $this->info("Settled {$strikes->settleFinished()} punishment(s).");

        return self::SUCCESS;
    }
}
