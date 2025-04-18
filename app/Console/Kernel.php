<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('notify:articles')->everyMinute();
        // $schedule->command('account:notification')->everyMinute();

        // not need now
        // $schedule->command('notify:articles')->dailyAt('09:15');
        // $schedule->command('notify:articles')->dailyAt('21:15');

        // $schedule->command('account:notification')->dailyAt('12:30');
        // $schedule->command('invoice:notification')->dailyAt('15:30');
        // $schedule->command('account:notification')->dailyAt('18:30');
        
        // $schedule->job(new \App\Jobs\SendNotification('invoice'))->everyMinute();
         
        $schedule->job(new \App\Jobs\SendNotification('holiday'))->dailyAt('09:30');
        $schedule->job(new \App\Jobs\SendNotification('account'))->dailyAt('12:30');
        $schedule->job(new \App\Jobs\SendNotification('invoice'))->dailyAt('15:30');
        $schedule->job(new \App\Jobs\SendNotification('account'))->dailyAt('18:45');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
