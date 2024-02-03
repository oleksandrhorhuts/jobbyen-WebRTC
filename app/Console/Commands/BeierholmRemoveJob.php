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

class BeierholmRemoveJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronjob:BeierholmRemoveJob';

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
        //Beierholm
        $company_name = 'Beierholm';
        $jobs = Job::where('company', $company_name)->where('is_active', '=', 1)->get();
        foreach($jobs as $value)
        {
            $job_url = $value->real_url;

            $this->client = new \GuzzleHttp\Client(['allow_redirects' => [
                'max'             => 5,
                'track_redirects' => true
            ]]);
            $response        = $this->client->get($job_url);
            $headersRedirect = $response->getHeader(\GuzzleHttp\RedirectMiddleware::HISTORY_HEADER);


            if (count($headersRedirect) > 1) {
                Job::where('id', $value->id)->update(['is_active'=>0]);
            } else {
            }


            // $crawler = Goutte::request('GET', $job_url);

            // $temp = $crawler->filter('.JobDescription')->count();

            // if($temp == 0){
			//     Job::where('id', $value->id)->update(['is_active'=>0]);
			// }
        }
    }
}
