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

class DeloitteRemoveJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronjob:DeloitteRemoveJob';

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
        //Deloitte
        $company_name = 'Deloitte';
        $jobs = Job::where('company', $company_name)->where('is_active', '=', 1)->get();
        foreach($jobs as $value)
        {
            $job_url = $value->real_url;
            $goutte = new Client();
            // $goutte->setClient(new GuzzleClient(array('verify' => false)));
            $crawler = $goutte->request('GET', $job_url);
            $temp = $crawler->filter('h3')->text();

            $result = 0;
            if(trim($temp, " \t\n\r\0\x0B\xC2\xA0") == 'Job post has expired')
            {
                $result = 1;
            }else{
                $this->client = new \GuzzleHttp\Client([
                    // 'verify'=>false,
                    'allow_redirects' => [
                        'max'             => 5,
                        'track_redirects' => true
                    ]
                ]);
                $response        = $this->client->get($job_url);
                $headersRedirect = $response->getHeader(\GuzzleHttp\RedirectMiddleware::HISTORY_HEADER);

                $this->info(count($headersRedirect));
                if (count($headersRedirect)) {
                    $result = 1;
                }
            }
            if($result == 1){
			    Job::where('id', $value->id)->update(['is_active'=>0]);
			}
        }
    }
}
