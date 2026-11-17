<?php

use Illuminate\Support\Facades\Schedule;

// Hostinger cron: hər dəqiqə "php artisan schedule:run"
Schedule::command('plan:post-deploy')->everyMinute()->withoutOverlapping();
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')->everyMinute()->withoutOverlapping();
Schedule::command('plan:reminders')->hourlyAt(2)->withoutOverlapping();
