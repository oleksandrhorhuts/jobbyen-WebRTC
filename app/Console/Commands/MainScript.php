<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Goutte;
use App\Job;
use App\Place;
use App\City;
use File;
use DB;
use Artisan;

class MainScript extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronjob:MainScript';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $cron_count = DB::table('cron_schedule')->count();

        $updated = DB::table('schedule_count')->where('id', 1)->update(['total_count' => intval($cron_count)]);

        $schedule_count = DB::table('schedule_count')->where('id', 1)->first();
        $current_idx = $schedule_count->current_idx;
        $total_count = $schedule_count->total_count;


        $cron = DB::table('cron_schedule')->orderBy('cron_schedule.id')
            ->distinct()->skip($current_idx - 1)->take($current_idx)->first();

        if ($cron) {
            if ($cron->active) {
                $command = $cron->command;

                if ($command) {
                    echo $command;
                    echo '-------------------------';
                    
                    Artisan::call($command);
                }
            } else {
            }
        }

        $updated = DB::table('schedule_count')->where('id', 1)->update(['current_idx' => $current_idx + 1]);

        if ($total_count == $current_idx) {
            $updated = DB::table('schedule_count')->where('id', 1)->update(['current_idx' => 1]);
        }
    }
}
