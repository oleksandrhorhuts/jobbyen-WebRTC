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
use GuzzleHttp\Client as GuzzleClient;


class TermaRemoveJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronjob:TermaRemoveJob';

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
        $company_name = 'Terma A/S';
        $jobs = Job::where('company', $company_name)->where('is_active', '=', 1)->get();
        foreach($jobs as $value)
        {
            $job_url = $value->real_url;
            $goutte = new Client();
            // $goutte->setClient(new GuzzleClient(array('verify' => false)));
            $crawler = $goutte->request('GET', $job_url)->html();
			if(preg_match('/jsMessages/', $crawler)){
                Job::where('id', $value->id)->update(['is_active'=>0]);
            }
        }
    }
}
