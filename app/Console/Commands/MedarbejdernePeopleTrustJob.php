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

class MedarbejdernePeopleTrustJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronjob:MedarbejdernePeopleTrustJob';

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
        $company_name = 'medarbejderne';
        $base_url = 'https://www.medarbejderne.dk/';
        $find_arr = ['marts', 'maj', 'juni', 'juli', 'oktober', 'februar', 'januar'];
        $replace_arr = ['march', 'may', 'june', 'july', 'october', 'february', 'january'];

        $search_link = 'https://medarbejderne.peopletrust.dk/candidate/?module=xhr&page=Index&action=index';
        $headers = [
			'Content-Type' => 'application/json'
        ];
        $client = new \GuzzleHttp\Client();
        $r = $client->post($search_link, [
            $headers,
            'body' => json_encode([
                'reqX' => 1,
                'req0' => 'b4256851-027f-8527-7469-c6c745b6a291',
                'trg0' => 'candidateweb/Job/dsJob',
                'pld0' => [
                    'data' => [
                        'freeText' => '',
                        'fClear' => 0,
                        'filterType' => 'candidate.job'
                    ],
                    'window' => [
                        'limit' => 20,
                        'page' => 1
                    ]
                ]
                // 'pld0' => '{"data":{"freeText":"","fClear":"0","filterType":"candidate.job"},"window":{"limit":20,"page":1}}'
            ])
        ]);

        var_dump($r->getBody()->getContents());
        exit;
        $jobs_data = json_decode($r->getBody()->getContents());

        echo json_encode($r->getBody()->getContents());
    }
}
