<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
        // 'App\Console\Commands\MainScript',
        // 'App\Console\Commands\MainRemoveScript',

        /* Job Command */
        'App\Console\Commands\AalborgKommuneJob',
        'App\Console\Commands\AalborgKommuneRemoveJob',
        'App\Console\Commands\AarhusKommuneJob',
        'App\Console\Commands\AarhusKommuneRemoveJob',
        'App\Console\Commands\AarhusUniversitetJob',
        'App\Console\Commands\AldiJob',
        'App\Console\Commands\AldiRemoveJob',
        'App\Console\Commands\BallerupKommuneJob',
        'App\Console\Commands\BallerupKommuneRemoveJob',
        'App\Console\Commands\BeierholmJob',
        'App\Console\Commands\BeierholmRemoveJob',
        'App\Console\Commands\BrondbyKommuneJob',
        'App\Console\Commands\BrondbyKommuneRemoveJob',
        'App\Console\Commands\CoopJob',
        'App\Console\Commands\CoopRemoveJob',
        'App\Console\Commands\CowiJob',
        'App\Console\Commands\CowiRemoveJob',
        'App\Console\Commands\CPHJob',
        'App\Console\Commands\DagrofaJob',
        'App\Console\Commands\DanishCrownJob',
        'App\Console\Commands\DanishCrownRemoveJob',
        'App\Console\Commands\DanskeBankJob',
        'App\Console\Commands\DanskeBankRemoveJob',
        'App\Console\Commands\DeloitteJob',
        'App\Console\Commands\DeloitteRemoveJob',
        'App\Console\Commands\DSBJob',
        'App\Console\Commands\DSBRemoveJob',
        'App\Console\Commands\DSVJob',
        'App\Console\Commands\DSVRemoveJob',
        'App\Console\Commands\ECCOJob',
        'App\Console\Commands\ECCORemoveJob',
        'App\Console\Commands\EgedalKommuneJob',
        'App\Console\Commands\EgedalKommuneRemoveJob',
        'App\Console\Commands\EgmontJob',
        'App\Console\Commands\EsbjergKommuneJob',
        'App\Console\Commands\EsbjergKommuneRemoveJob',
        'App\Console\Commands\FredensborgKommuneJob',
        'App\Console\Commands\FredericiaKommuneJob',
        'App\Console\Commands\FrederikKommuneJob',
        'App\Console\Commands\FrederikKommuneRemoveJob',
        'App\Console\Commands\FrederikssundKommuneJob',
        'App\Console\Commands\GribskovKommuneJob',
        'App\Console\Commands\GrundFosJob',
        'App\Console\Commands\GrundFosRemoveJob',
        'App\Console\Commands\HaderslevKommuneJob',
        'App\Console\Commands\HalsnaesKommuneJob',
        'App\Console\Commands\HedenstedKommuneJob',
        'App\Console\Commands\HelsingorKommuneJob',
        'App\Console\Commands\HerlevKommuneJob',
        'App\Console\Commands\HerningKommuneJob',
        'App\Console\Commands\HillerodKommuneJob',
        'App\Console\Commands\HolbaekKommuneJob',
        'App\Console\Commands\HolstebroKommuneJob',
        'App\Console\Commands\HorsensKommuneJob',
        'App\Console\Commands\HorsensKommuneRemoveJob',
        'App\Console\Commands\HRIndustriesJob',
        'App\Console\Commands\HviidLarsenApsJob',
        'App\Console\Commands\IkastBrandeKommuneJob',
        'App\Console\Commands\ISSJob',
        'App\Console\Commands\ISSRemoveJob',
        'App\Console\Commands\JyskeBankJob',
        'App\Console\Commands\JyskeBankRemoveJob',
        'App\Console\Commands\KalundborgKommuneJob',
        'App\Console\Commands\KobenhavnKommuneJob',
        'App\Console\Commands\KobenhavnKommuneRemoveJob',
        'App\Console\Commands\KoldingKommuneJob',
        'App\Console\Commands\KoldingKommuneRemoveJob',
        'App\Console\Commands\LegoJob',
        'App\Console\Commands\LegoRemoveJob',
        'App\Console\Commands\LemvigKommuneJob',
        'App\Console\Commands\LidlJob',
        'App\Console\Commands\LidlRemoveJob',
        'App\Console\Commands\LundbeckJob',
        'App\Console\Commands\LundbeckRemoveJob',
        'App\Console\Commands\MaerskJob',
        'App\Console\Commands\MaerskRemoveJob',
        'App\Console\Commands\MariagerfjordKommuneJob',
        'App\Console\Commands\MedarbejdernePeopleTrustJob',
        'App\Console\Commands\NaestvedKommuneJob',
        'App\Console\Commands\NetcompanyJob',
        'App\Console\Commands\NetcompanyRemoveJob',
        'App\Console\Commands\NirasJob',
        'App\Console\Commands\NirasRemoveJob',
        'App\Console\Commands\NorddjursKommuneJob',
        'App\Console\Commands\NordeaJob',
        'App\Console\Commands\NordeaRemoveJob',
        'App\Console\Commands\NovoNordiskJob',
        'App\Console\Commands\NovoNordiskRemoveJob',
        'App\Console\Commands\OdenseJob',
        'App\Console\Commands\OdenseRemoveJob',
        'App\Console\Commands\OdsherredKommuneJob',
        'App\Console\Commands\OliviaDanmarkJob',
        'App\Console\Commands\OrstedJob',
        'App\Console\Commands\OrstedRemoveJob',
        'App\Console\Commands\PersonaleBorsenJob',
        'App\Console\Commands\PhaseOneJob',
        'App\Console\Commands\PostNordJob',
        'App\Console\Commands\PostNordRemoveJob',
        'App\Console\Commands\ProSelectionJob',
        'App\Console\Commands\RandersKommuneJob',
        'App\Console\Commands\RandersKommuneRemoveJob',
        'App\Console\Commands\RegionMidJob',
        'App\Console\Commands\RegionNordjyllandJob',
        'App\Console\Commands\RegionNordjyllandRemoveJob',
        'App\Console\Commands\RegionSjaelland',
        'App\Console\Commands\RegionSyddanmark',
        'App\Console\Commands\RemaRemoveJob',
        'App\Console\Commands\RingstedKommuneJob',
        'App\Console\Commands\RodovreKommuneJob',
        'App\Console\Commands\RoskildeKommuneJob',
        'App\Console\Commands\RoskildeKommuneRemoveJob',
        'App\Console\Commands\SallingGroupJob',
        'App\Console\Commands\SallingGroupRemoveJob',
        'App\Console\Commands\SkiveKommuneJob',
        'App\Console\Commands\SKTSTJob',
        'App\Console\Commands\SlagelseKommuneJob',
        'App\Console\Commands\StruerKommuneJob',
        'App\Console\Commands\SwecoJob',
        'App\Console\Commands\TDCJob',
        'App\Console\Commands\TDCRemoveJob',
        'App\Console\Commands\TermaJob',
        'App\Console\Commands\TermaRemoveJob',
        'App\Console\Commands\TonderKommuneJob',
        'App\Console\Commands\UFSTJob',
        'App\Console\Commands\VejleKommuneJob',
        'App\Console\Commands\VejleKommuneRemoveJob',
        'App\Console\Commands\VestasJob',
        'App\Console\Commands\VestasRemoveJob',
        'App\Console\Commands\ViborgKommuneJob',
        'App\Console\Commands\ViborgKommuneRemoveJob',
        'App\Console\Commands\VordingborgKommuneJob',

        /* App Command */
        'App\Console\Commands\CheckSeekerAgent',
        'App\Console\Commands\DetectExpireJob',
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('cronjob:MainScript')->everyFiveMinutes();
        // $schedule->command('cronjob:MainRemoveScript')->hourly();

        /* Job Schedule */
        $schedule->command('cronjob:AalborgKommuneJob')->dailyAt('00:10')->withoutOverlapping();
        $schedule->command('cronjob:AalborgKommuneRemoveJob')->dailyAt('00:10')->withoutOverlapping();

        $schedule->command('cronjob:AarhusKommuneJob')->dailyAt('00:10')->withoutOverlapping();
        $schedule->command('cronjob:AarhusKommuneRemoveJob')->dailyAt('00:10')->withoutOverlapping();

        $schedule->command('cronjob:AarhusUniversitetJob')->dailyAt('00:10')->withoutOverlapping();

        $schedule->command('cronjob:AldiJob')->dailyAt('00:10')->withoutOverlapping();
        $schedule->command('cronjob:AldiRemoveJob')->dailyAt('00:10')->withoutOverlapping();

        $schedule->command('cronjob:BallerupKommuneJob')->dailyAt('00:10')->withoutOverlapping();
        $schedule->command('cronjob:BallerupKommuneRemoveJob')->dailyAt('00:10')->withoutOverlapping();

        $schedule->command('cronjob:BeierholmJob')->dailyAt('00:10')->withoutOverlapping();
        $schedule->command('cronjob:BeierholmRemoveJob')->dailyAt('00:10')->withoutOverlapping();

        $schedule->command('cronjob:BrondbyKommuneJob')->dailyAt('00:10')->withoutOverlapping();
        $schedule->command('cronjob:BrondbyKommuneRemoveJob')->dailyAt('00:10')->withoutOverlapping();

        $schedule->command('cronjob:CheckSeekerAgent')->dailyAt('00:10')->withoutOverlapping();

        $schedule->command('cronjob:CoopJob')->dailyAt('00:10')->withoutOverlapping();
        $schedule->command('cronjob:CoopRemoveJob')->dailyAt('00:10')->withoutOverlapping();

        $schedule->command('cronjob:CowiJob')->dailyAt('00:10')->withoutOverlapping();
        $schedule->command('cronjob:CowiRemoveJob')->dailyAt('00:10')->withoutOverlapping();

        $schedule->command('cronjob:CPHJob')->dailyAt('00:10')->withoutOverlapping();

        $schedule->command('cronjob:DagrofaJob')->dailyAt('00:10')->withoutOverlapping();

        $schedule->command('cronjob:DanishCrownJob')->dailyAt('00:10')->withoutOverlapping();
        $schedule->command('cronjob:DanishCrownRemoveJob')->dailyAt('00:10')->withoutOverlapping();

        $schedule->command('cronjob:DanskeBankJob')->dailyAt('00:10')->withoutOverlapping();
        $schedule->command('cronjob:DanskeBankRemoveJob')->dailyAt('00:10')->withoutOverlapping();

        $schedule->command('cronjob:DeloitteJob')->dailyAt('00:10')->withoutOverlapping();
        $schedule->command('cronjob:DeloitteRemoveJob')->dailyAt('00:10')->withoutOverlapping();

        $schedule->command('cronjob:DetectExpireJob')->dailyAt('00:10')->withoutOverlapping();

        $schedule->command('cronjob:DSBJob')->dailyAt('00:10')->withoutOverlapping();
        $schedule->command('cronjob:DSBRemoveJob')->dailyAt('00:10')->withoutOverlapping();

        $schedule->command('cronjob:DSVJob')->dailyAt('00:10')->withoutOverlapping();
        $schedule->command('cronjob:DSVRemoveJob')->dailyAt('00:10')->withoutOverlapping();

        $schedule->command('cronjob:ECCOJob')->dailyAt('00:10')->withoutOverlapping();
        $schedule->command('cronjob:ECCORemoveJob')->dailyAt('00:10')->withoutOverlapping();

        $schedule->command('cronjob:EgedalKommuneJob')->dailyAt('00:10')->withoutOverlapping();
        $schedule->command('cronjob:EgedalKommuneRemoveJob')->dailyAt('00:10')->withoutOverlapping();

        $schedule->command('cronjob:EgmontJob')->dailyAt('00:10')->withoutOverlapping();

        $schedule->command('cronjob:EsbjergKommuneJob')->dailyAt('00:10')->withoutOverlapping();
        $schedule->command('cronjob:EsbjergKommuneRemoveJob')->dailyAt('00:10')->withoutOverlapping();

        $schedule->command('cronjob:FredensborgKommuneJob')->dailyAt('00:10')->withoutOverlapping();

        $schedule->command('cronjob:FredericiaKommuneJob')->dailyAt('00:10')->withoutOverlapping();

        $schedule->command('cronjob:FrederikKommuneJob')->dailyAt('00:10')->withoutOverlapping();
        $schedule->command('cronjob:FrederikKommuneRemoveJob')->dailyAt('00:10')->withoutOverlapping();

        $schedule->command('cronjob:FrederikssundKommuneJob')->dailyAt('01:00')->withoutOverlapping();

        $schedule->command('cronjob:GribskovKommuneJob')->dailyAt('01:00')->withoutOverlapping();

        $schedule->command('cronjob:GrundFosJob')->dailyAt('01:00')->withoutOverlapping();
        $schedule->command('cronjob:GrundFosRemoveJob')->dailyAt('01:00')->withoutOverlapping();

        $schedule->command('cronjob:HaderslevKommuneJob')->dailyAt('01:00')->withoutOverlapping();

        $schedule->command('cronjob:HalsnaesKommuneJob')->dailyAt('01:00')->withoutOverlapping();

        $schedule->command('cronjob:HedenstedKommuneJob')->dailyAt('01:00')->withoutOverlapping();

        $schedule->command('cronjob:HelsingorKommuneJob')->dailyAt('01:00')->withoutOverlapping();

        $schedule->command('cronjob:HerlevKommuneJob')->dailyAt('01:00')->withoutOverlapping();

        $schedule->command('cronjob:HerningKommuneJob')->dailyAt('01:00')->withoutOverlapping();

        $schedule->command('cronjob:HillerodKommuneJob')->dailyAt('01:00')->withoutOverlapping();

        $schedule->command('cronjob:HolbaekKommuneJob')->dailyAt('01:00')->withoutOverlapping();

        $schedule->command('cronjob:HolstebroKommuneJob')->dailyAt('01:00')->withoutOverlapping();

        $schedule->command('cronjob:HorsensKommuneJob')->dailyAt('01:00')->withoutOverlapping();
        $schedule->command('cronjob:HorsensKommuneRemoveJob')->dailyAt('01:00')->withoutOverlapping();

        $schedule->command('cronjob:HRIndustriesJob')->dailyAt('01:00')->withoutOverlapping();

        $schedule->command('cronjob:HviidLarsenApsJob')->dailyAt('01:00')->withoutOverlapping();

        $schedule->command('cronjob:IkastBrandeKommuneJob')->dailyAt('01:00')->withoutOverlapping();

        $schedule->command('cronjob:ISSJob')->dailyAt('01:00')->withoutOverlapping();
        $schedule->command('cronjob:ISSRemoveJob')->dailyAt('01:00')->withoutOverlapping();

        $schedule->command('cronjob:JyskeBankJob')->dailyAt('01:00')->withoutOverlapping();
        $schedule->command('cronjob:JyskeBankRemoveJob')->dailyAt('01:00')->withoutOverlapping();

        $schedule->command('cronjob:KalundborgKommuneJob')->dailyAt('01:00')->withoutOverlapping();

        $schedule->command('cronjob:KobenhavnKommuneJob')->dailyAt('01:00')->withoutOverlapping();
        $schedule->command('cronjob:KobenhavnKommuneRemoveJob')->dailyAt('01:00')->withoutOverlapping();

        $schedule->command('cronjob:KoldingKommuneJob')->dailyAt('01:00')->withoutOverlapping();
        $schedule->command('cronjob:KoldingKommuneRemoveJob')->dailyAt('01:00')->withoutOverlapping();

        $schedule->command('cronjob:LegoJob')->dailyAt('01:00')->withoutOverlapping();
        $schedule->command('cronjob:LegoRemoveJob')->dailyAt('01:00')->withoutOverlapping();

        $schedule->command('cronjob:LemvigKommuneJob')->dailyAt('01:00')->withoutOverlapping();

        $schedule->command('cronjob:LidlJob')->dailyAt('01:00')->withoutOverlapping();
        $schedule->command('cronjob:LidlRemoveJob')->dailyAt('01:00')->withoutOverlapping();

        $schedule->command('cronjob:LundbeckJob')->dailyAt('01:00')->withoutOverlapping();
        $schedule->command('cronjob:LundbeckRemoveJob')->dailyAt('01:00')->withoutOverlapping();

        $schedule->command('cronjob:MaerskJob')->dailyAt('02:00')->withoutOverlapping();
        $schedule->command('cronjob:MaerskRemoveJob')->dailyAt('02:00')->withoutOverlapping();

        $schedule->command('cronjob:MariagerfjordKommuneJob')->dailyAt('02:00')->withoutOverlapping();

        $schedule->command('cronjob:MedarbejdernePeopleTrustJob')->dailyAt('02:00')->withoutOverlapping();

        $schedule->command('cronjob:NaestvedKommuneJob')->dailyAt('02:00')->withoutOverlapping();

        $schedule->command('cronjob:NetcompanyJob')->dailyAt('02:00')->withoutOverlapping();
        $schedule->command('cronjob:NetcompanyRemoveJob')->dailyAt('02:00')->withoutOverlapping();

        $schedule->command('cronjob:NirasJob')->dailyAt('02:00')->withoutOverlapping();
        $schedule->command('cronjob:NirasRemoveJob')->dailyAt('02:00')->withoutOverlapping();

        $schedule->command('cronjob:NorddjursKommuneJob')->dailyAt('02:00')->withoutOverlapping();

        $schedule->command('cronjob:NordeaJob')->dailyAt('02:00')->withoutOverlapping();
        $schedule->command('cronjob:NordeaRemoveJob')->dailyAt('02:00')->withoutOverlapping();

        $schedule->command('cronjob:NovoNordiskJob')->dailyAt('02:00')->withoutOverlapping();
        $schedule->command('cronjob:NovoNordiskRemoveJob')->dailyAt('02:00')->withoutOverlapping();

        $schedule->command('cronjob:OdenseJob')->dailyAt('02:00')->withoutOverlapping();
        $schedule->command('cronjob:OdenseRemoveJob')->dailyAt('02:00')->withoutOverlapping();

        $schedule->command('cronjob:OdsherredKommuneJob')->dailyAt('02:00')->withoutOverlapping();
        $schedule->command('cronjob:OrstedRemoveJob')->dailyAt('02:00')->withoutOverlapping();

        $schedule->command('cronjob:PersonaleBorsenJob')->dailyAt('02:00')->withoutOverlapping();

        $schedule->command('cronjob:PhaseOneJob')->dailyAt('02:00')->withoutOverlapping();

        $schedule->command('cronjob:PostNordJob')->dailyAt('02:00')->withoutOverlapping();
        $schedule->command('cronjob:PostNordRemoveJob')->dailyAt('02:00')->withoutOverlapping();

        $schedule->command('cronjob:ProSelectionJob')->dailyAt('02:00')->withoutOverlapping();

        $schedule->command('cronjob:RandersKommuneJob')->dailyAt('02:00')->withoutOverlapping();
        $schedule->command('cronjob:RandersKommuneRemoveJob')->dailyAt('02:00')->withoutOverlapping();

        $schedule->command('cronjob:RegionMidJob')->dailyAt('02:00')->withoutOverlapping();

        $schedule->command('cronjob:RegionNordjyllandJob')->dailyAt('02:00')->withoutOverlapping();
        $schedule->command('cronjob:RegionNordjyllandRemoveJob')->dailyAt('02:00')->withoutOverlapping();

        $schedule->command('cronjob:RegionSjaelland')->dailyAt('03:00')->withoutOverlapping();

        $schedule->command('cronjob:RegionSyddanmark')->dailyAt('03:00')->withoutOverlapping();
        $schedule->command('cronjob:RemaRemoveJob')->dailyAt('03:00')->withoutOverlapping();

        $schedule->command('cronjob:RingstedKommuneJob')->dailyAt('03:00')->withoutOverlapping();

        $schedule->command('cronjob:RodovreKommuneJob')->dailyAt('03:00')->withoutOverlapping();

        $schedule->command('cronjob:RoskildeKommuneJob')->dailyAt('03:00')->withoutOverlapping();
        $schedule->command('cronjob:RoskildeKommuneRemoveJob')->dailyAt('03:00')->withoutOverlapping();

        $schedule->command('cronjob:SallingGroupJob')->dailyAt('03:00')->withoutOverlapping();
        $schedule->command('cronjob:SallingGroupRemoveJob')->dailyAt('03:00')->withoutOverlapping();

        $schedule->command('cronjob:SkiveKommuneJob')->dailyAt('03:00')->withoutOverlapping();

        $schedule->command('cronjob:SKTSTJob')->dailyAt('03:00')->withoutOverlapping();

        $schedule->command('cronjob:SlagelseKommuneJob')->dailyAt('03:00')->withoutOverlapping();

        $schedule->command('cronjob:StruerKommuneJob')->dailyAt('03:00')->withoutOverlapping();

        $schedule->command('cronjob:SwecoJob')->dailyAt('03:00')->withoutOverlapping();

        $schedule->command('cronjob:TDCJob')->dailyAt('03:00')->withoutOverlapping();
        $schedule->command('cronjob:TDCRemoveJob')->dailyAt('03:00')->withoutOverlapping();

        $schedule->command('cronjob:TermaJob')->dailyAt('03:00')->withoutOverlapping();
        $schedule->command('cronjob:TermaRemoveJob')->dailyAt('03:00')->withoutOverlapping();

        $schedule->command('cronjob:TonderKommuneJob')->dailyAt('03:00')->withoutOverlapping();

        $schedule->command('cronjob:UFSTJob')->dailyAt('03:00')->withoutOverlapping();

        $schedule->command('cronjob:VejleKommuneJob')->dailyAt('03:00')->withoutOverlapping();
        $schedule->command('cronjob:VejleKommuneRemoveJob')->dailyAt('03:00')->withoutOverlapping();

        $schedule->command('cronjob:VestasJob')->dailyAt('03:00')->withoutOverlapping();
        $schedule->command('cronjob:VestasRemoveJob')->dailyAt('03:00')->withoutOverlapping();

        $schedule->command('cronjob:ViborgKommuneJob')->dailyAt('03:00')->withoutOverlapping();
        $schedule->command('cronjob:ViborgKommuneRemoveJob')->dailyAt('03:00')->withoutOverlapping();

        $schedule->command('cronjob:VordingborgKommuneJob')->dailyAt('03:00')->withoutOverlapping();

        $schedule->command('cronjob:CheckSeekerAgent')->everyFiveMinutes();
        $schedule->command('cronjob:DetectExpireJob')->daily();

    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
