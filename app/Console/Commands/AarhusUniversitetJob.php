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

use App\JobLocation;
use Illuminate\Support\Facades\Hash;
class AarhusUniversitetJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronjob:AarhusUniversitetJob';

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
        //
        $crawler = Goutte::request('GET', 'https://recruit.hr-on.com/frame-api/pages/list-jobs?theme=KromannConnectApS&joblisturl=https%3A%2F%2Fpersonaleborsen.dk%2Fkandidater%2Fledige-stillinger%2F&locale=da_DK&companyid=166&xdmframe=1&metaTitle=Ledige%20stillinger%20-%20Se%20aktuelle%20ledige%20stillinger%20hos%20vores%20kunder');
        $total_pagination = $crawler->filter('.jobposting')->count();
        echo $total_pagination;
    }
}
