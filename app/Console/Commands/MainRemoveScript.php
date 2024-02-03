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

class MainRemoveScript extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronjob:MainRemoveScript';

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

        $updated = DB::table('schedule_remove_count')->where('id', 1)->update(['total_count' => intval($cron_count)]);

        $schedule_count = DB::table('schedule_remove_count')->where('id', 1)->first();
        $current_idx = $schedule_count->current_idx;
        $total_count = $schedule_count->total_count;

        $cron = DB::table('cron_schedule')->where('active', 1)->orderBy('cron_schedule.id')
            ->distinct()->skip($current_idx - 1)->take($current_idx)->first();

        if ($cron) {
            $delCommand = $cron->delCommand;

            $this->info('del command : '.$delCommand);
            if ($delCommand) {
                Artisan::call($delCommand);
            }
        }

        $updated = DB::table('schedule_remove_count')->where('id', 1)->update(['current_idx' => $current_idx + 1]);
        if ($total_count == $current_idx) {
            $updated = DB::table('schedule_remove_count')->where('id', 1)->update(['current_idx' => 1]);
        }
    }
}
