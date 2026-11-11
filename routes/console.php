<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// A member whose mute or ban has ended starts over; a new strike settles theirs on the spot too, so this
// only tidies up after those who stay quiet.
Schedule::command('moderation:settle-punishments')->hourly();
