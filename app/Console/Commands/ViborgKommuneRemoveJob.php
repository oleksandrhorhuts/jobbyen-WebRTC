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


class ViborgKommuneRemoveJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronjob:ViborgKommuneRemoveJob';

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
        $company_name = 'Viborg kommune';
        $jobs = Job::where('company', $company_name)->where('is_active', '=', 1)->get();
        foreach ($jobs as $value) {
            $job_url = $value->real_url;
            $this->info($job_url);
            $client = new \GuzzleHttp\Client([
                'http_errors' => false,
                'headers' => [
                    'User-Agent' => 'Command Prompt',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
                    'Accept-Encoding' => 'gzip, deflate, br',
                ],
                'connect_timeout' => 2, 'timeout' => 5
            ]);

            try {
                $clientRequest = $client->request('GET', $job_url);
                $getBody = $clientRequest->getBody();
                $getStatusCode = $clientRequest->getStatusCode();
                if ($getStatusCode == 200) {


                    $crawler = Goutte::request('GET', $job_url);
                    if ($crawler->filter('header.intro__header h1')->text() == '404 - Siden blev ikke fundet') {
                        Job::where('id', $value->id)->update(['is_active' => 0]);
                    }
                } else {
                    Job::where('id', $value->id)->update(['is_active' => 0]);
                }
            } catch (\Exception $e) {
                $this->info('==========================');
                $this->info($e->getMessage());
                Job::where('id', $value->id)->update(['is_active' => 0]);
                $this->info('==========================');
            }




            // $this->info($job_url);
            // $crawler = Goutte::request('GET', $job_url);


            // if($crawler->filter('.manchet')->text() == 'Vi beklager, men den side, du forsøger at få vist, findes ikke.'){
            //     Job::where('id', $value->id)->update(['is_active'=>0]);
            // }
        }
    }
}
