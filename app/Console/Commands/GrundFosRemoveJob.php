<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Goutte;
use App\Job;
use App\Place;
use App\City;
use File;
use App\JobCategory;
use App\Category;
use Goutte\Client;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client as GuzzleClient;
use Symfony\Component\DomCrawler\Crawler;
class GrundFosRemoveJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronjob:GrundFosRemoveJob';

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
        $this->client = new GuzzleClient();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //GrundFos
        $company_name = 'GrundFos';
        $jobs = Job::where('company', $company_name)->where('is_active', '=', 1)->get();
        foreach ($jobs as $value) {
            $job_url = $value->real_url;
            $this->info($job_url);
            $this->info('==================');

            $job_query_arr = explode("?", $job_url);
            $job_query = $job_query_arr[count($job_query_arr) - 1];
            $job_real_url = $job_query_arr[0];

            $jobId = null;
            $queryArr = preg_match_all("/jobid=(.*)/", $job_query, $matches);
            if ($matches[1]) {
                if ($matches[1][0]) {
                    $jobId = $matches[1][0];
                }
            }
            $this->info('job id :' . $jobId);
            $this->info('==================');
            $this->info('job url :' . $job_real_url);
            $this->info('==================');

            $job_response = $this->client->get($job_real_url, [
                // 'headers' => $this->headers,
                'query' => [
                    'jobid' => $jobId,
                ],
            ]);
            $contents = $job_response->getBody()->getContents();
            $job_crawler = new Crawler($contents);
            // $crawler = Goutte::request('GET', $job_url);
            $temp = $job_crawler->filter('div.opportunity-details p')->count();
            if ($temp == 0) {
                $this->info('---------------------------------');
                Job::where('id', $value->id)->update(['is_active' => 0]);
            } else {
                $this->info('*********************************');
            }
        }
    }
}
