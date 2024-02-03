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

class AarhusKommuneRemoveJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronjob:AarhusKommuneRemoveJob';

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
        $company_name = 'Aarhus kommune';
        $jobs = Job::where('company', $company_name)->where('is_active', '=', 1)->get();
        foreach ($jobs as $value) {
            $job_url = $value->real_url;

            $client = new \GuzzleHttp\Client([
                // 'verify' => false,
                'http_errors' => false,
                'headers' => [
                    'User-Agent' => 'Command Prompt',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
                    'Accept-Encoding' => 'gzip, deflate, br',
                ]
            ]);
            $clientRequest = $client->request('GET', $job_url);
            $getBody = $clientRequest->getBody();
            $getStatusCode = $clientRequest->getStatusCode();
            if ($getStatusCode == 200) {
                // $this->info('active');
                // $this->info($job_url);
            } else {
                // $this->info('not active');
                // $this->info($job_url);
                Job::where('id', $value->id)->update(['is_active'=>0]);
            }
            // $this->info('===============================');

            // $crawler = Goutte::request('GET', $job_url);

            // $temp = $crawler->filter('.emply__content')->count();

            // if($temp == 0){
            //     Job::where('id', $value->id)->update(['is_active'=>0]);
            // }
        }
    }
}
