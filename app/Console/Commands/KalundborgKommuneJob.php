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
use GuzzleHttp\Client as GuzzleClient;

use App\JobLocation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Symfony\Component\DomCrawler\Crawler;
class KalundborgKommuneJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronjob:KalundborgKommuneJob';

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
        $src = ['oe', 'aa', 'ae'];
        $dst = ['ø', 'å', 'æ'];
        $company_name = 'HR-Industries A/S';
        $base_url = 'https://www.hviidoglarsen.dk/';
        $find_arr = ['marts', 'maj', 'juni', 'juli', 'oktober', 'februar', 'januar'];
        $replace_arr = ['march', 'may', 'june', 'july', 'october', 'february', 'january'];


        // $crawler = Goutte::request('GET', 'https://emea3.recruitmentplatform.com/syndicated/lay/jsoutputinitrapido.cfm?ID=PUFFK026203F3VBQBV76GLOTE&LG=DN&mask=kmdlive&mtbrwchk=nok&browserchk=no&page=details.html&Resultsperpage=5&pagenum=1&option=52&sort=DESC&page1=index.html&component=lay9999_lst400a&rapido=false&1605432416790');
        // $body = $crawler->filter('body')->outerHtml();

    }
}
