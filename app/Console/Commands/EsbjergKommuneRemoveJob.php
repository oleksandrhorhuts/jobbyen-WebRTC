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
use Guzzle\Http\Exception\ClientErrorResponseException;
use Guzzle\Plugin\History\HistoryPlugin;
use GuzzleHttp\Exception\TooManyRedirectsException;
use Symfony\Component\DomCrawler\Crawler;


class EsbjergKommuneRemoveJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronjob:EsbjergKommuneRemoveJob';

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
        $company_name = 'Esbjerg kommune';
        $jobs = Job::where('company', $company_name)->where('is_active', '=', 1)->get();
        foreach ($jobs as $value) {
            $job_url = $value->real_url;
            $client = new \GuzzleHttp\Client();

            try {
                $request = $client->get($job_url);
                $content = $request->getBody()->getContents();
                $crawler = new Crawler( $content );
                if(!$crawler->filter('.rich-text__content')->count()){
                    Job::where('id', $value->id)->update(['is_active'=>0]);
                }
                // var_dump($response);
            } catch (ClientErrorResponseException $exception) {


            } catch (TooManyRedirectsException $e){
                Job::where('id', $value->id)->update(['is_active'=>0]);
            }
        }


    }
}
