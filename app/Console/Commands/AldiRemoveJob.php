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

class AldiRemoveJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronjob:AldiRemoveJob';

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
        $company_name = 'Aldi';
        $jobs = Job::where(['company'=> $company_name, 'is_active'=>1])->get();

        $this->info('total job : ' . sizeof($jobs));
        foreach ($jobs as $value) {
            $job_url = $value->real_url;

            $goutte = new Client();
            // $goutte->setClient(new GuzzleClient(array('verify' => false)));
            $crawler = $goutte->request('GET', $job_url);

            if($crawler->filter('h1')->count() && $crawler->filter('h1')->text()=='' ){
                // $this->info($job_url);
                Job::where('id', $value->id)->update(['is_active'=>0]);
            }else{
                // $this->info('active : '.$job_url);
                continue;
            }

            // $this->client = new \GuzzleHttp\Client([
            //     'verify'=>false,
            //     'allow_redirects' => [
            //         'max'             => 5,
            //         'track_redirects' => true
            //     ]
            // ]);
            // $response        = $this->client->get($job_url);
            // $headersRedirect = $response->getHeader(\GuzzleHttp\RedirectMiddleware::HISTORY_HEADER);

            // $this->info($job_url);
            // $this->info(json_encode($headersRedirect));
            // $this->info('===========');

            // if (sizeof($headersRedirect) > 0) {
            //     $this->info($job_url);
            // } else {

            // }

            // if($crawler->filter('.mod-job-detail__body')->text() == 'Ansøg'){
            //     Job::where('id', $value->id)->update(['is_active'=>0]);
            // }
        }
    }
}
