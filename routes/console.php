<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('crm:mail-notifications')
    ->everyTenSeconds()
    ->withoutOverlapping();

Schedule::command('affiliate:sync-hyperlead')
    ->everyFiveMinutes()
    ->withoutOverlapping(5)
    ->onOneServer();

Schedule::command('affiliate:sync-accesstrade')
    ->everyFiveMinutes()
    ->withoutOverlapping(5)
    ->onOneServer();
