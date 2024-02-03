<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Goutte;
use App\Job;
use App\Place;
use App\City;
use File;
use DB;
use App\JobCategory;
use App\Category;
use Goutte\Client;

class DanskeBankRemoveJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronjob:DanskeBankRemoveJob';

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
        $company_name = 'Danske Bank';
        $jobs = Job::where('company', $company_name)->where('is_active', '=', 1)->get();
        foreach ($jobs as $value) {
            $job_url = $value->real_url;
            $crawler = Goutte::request('GET', $job_url);

            if ($crawler->filter('div.gsDbgDynamicTablePaddingLeft table tr:nth-child(5) td table:nth-child(2) tr td div table tr td div table tr')->count()) {
                if ($crawler->filter('div.gsDbgDynamicTablePaddingLeft table tr:nth-child(5) td table:nth-child(2) tr td div table tr td div table tr')->text() == 'Ansøgningsfristen er overskredet.') {
                    Job::where('id', $value->id)->update(['is_active' => 0]);
                }
            } else {
            }
        }
    }
}
