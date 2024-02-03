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
class RemaRemoveJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronjob:RemaRemoveJob';

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
        //Rema
        $company_name = 'Rema1000';
        $jobs = Job::where('company', $company_name)->where('is_active', '=', 1)->get();
        foreach($jobs as $value)
        {
            $job_url = $value->real_url;
            $crawler = Goutte::request('GET', $job_url);
            
            $temp = $crawler->filter('div#AdvertisementInnerContent')->count();
            
            if($temp == 0){
			    Job::where('id', $value->id)->update(['is_active'=>0]);
			}
            
        }
    }
}