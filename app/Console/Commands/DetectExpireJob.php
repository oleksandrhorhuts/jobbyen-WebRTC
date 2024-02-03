<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Job;

class DetectExpireJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronjob:DetectExpireJob';

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
        $jobs = Job::where('user_id', '>', 0)->get();
        foreach ($jobs as $k => $v) {
            if (date('Y-m-d 00:00:00') >= date('Y-m-d', strtotime('+30 days', strtotime($v->created_at)))) {
                Job::where('id', $v->id)->update(['is_active' => 0]);
            }
        }
    }
}
