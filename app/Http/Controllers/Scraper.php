<?php /** @noinspection PhpParamsInspection */

namespace App\Http\Controllers;

use App\Imports\TrapImport;
use App\Models\Project;
use App\Models\Trap;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use GuzzleHttp\Client;
use Maatwebsite\Excel\Facades\Excel;
use GuzzleHttp\Cookie\CookieJar;
use Symfony\Component\DomCrawler\Crawler;

class Scraper extends Controller
{
    const BASE_URL = "https://trap.nz";
    const LOGIN_URL = self::BASE_URL . "/user/login?destination=my-projects";
    const TRAP_URL = self::BASE_URL . "/project/trap_overview.json";

    public function uploadTraps() {
         Excel::import(new TrapImport, request()->file('file'), null, \Maatwebsite\Excel\Excel::CSV);
        return true;
    }

    public function projects() {
        $loginNeeded = false;
        $client = new Client();
        $cookieJar = new CookieJar();
        try {
            $response = $client->get(self::BASE_URL . '/my-projects', [
                'cookies' => $cookieJar
            ]);
        } catch(\Exception $e) {
            if($e->getCode() === 403) {
                $loginNeeded = true;
            }
        }
        if($loginNeeded) {
            $response = $client->post(self::LOGIN_URL, [
                'form_params' => [
                    'name' => env('TRAP_NZ_USERNAME', 'dylan'),
                    'pass' => env('TRAP_NZ_PASSWORD', 'notmypassword'),
                    'form_build_id' => '',
                    'form_id' => 'user_login',
                    'op' => 'Log+in'
                ],
                'allow_redirects' => true,
                'cookies' => $cookieJar
            ]);
        }

        $response = $client->get(self::BASE_URL . '/my-projects', [
            'cookies' => $cookieJar
        ]);
        $htmlString = (string) $response->getBody();
        $crawler = new Crawler($htmlString);

        $projects = [];
        /*
         * Get Basic Trap Information
         */
        foreach ($crawler->filter('tbody > tr') as  $node) {
            $crawler = new Crawler($node);
            $project = [];
            $project['link'] = $crawler->children()->eq(0)->children()->eq(0)->attr('href');
            $project['name'] = $crawler->children()->eq(0)->text();
            $project['members'] = $crawler->children()->eq(1)->text();
            $project['role'] = $crawler->children()->eq(2)->text();
            $project['notes'] = $crawler->children()->eq(3)->text();
            $project['area'] = $crawler->children()->eq(4)->text();
            $projects[] = $project;
        }

        /*
         * Get Trap Data
         */
        foreach ($projects as $index => $project) {
            $traps = [];
            $link = self::BASE_URL . $project['link'];
            // Set project in cookie
            $response = $client->get($link, [
                'cookies' => $cookieJar
            ]);
            $existing_project = Project::where('name', $project['name'])->first();
            if(! $existing_project) {
                $existing_project = Project::create([
                   'name' => $project['name'],
                    'description' => 'Fetch test'
                ]);
            }
            // Fetch Trap List
            $response = $client->get(self::TRAP_URL, [
                'cookies'=> $cookieJar
            ]);
            if ($response->getBody()) {
                $trapDta = json_decode($response->getBody(), true);
                foreach ($trapDta['features'] as $trap) {
                    $newTrap = [];
                    $newTrap['coordinates'] = $trap['geometry']['coordinates'];
                    $newTrap['name'] = $trap['properties']['name'];
                    $newTrap['nid'] = $trap['properties']['nid'];
                    $traps[] = $newTrap;
                    $trap = Trap::where('nz_trap_id', $newTrap['nid'])->first();
                    if(! $trap) {
                        Trap::create([
                            'nz_trap_id' => $newTrap['nid'],
                            'project_id' => $existing_project->id,
                            'name' => $newTrap['name'],
                            'coordinates' => new Point($newTrap['coordinates'][0], $newTrap['coordinates'][1])
                        ]);
                    }
                }
                $projects[$index]['traps'] = $traps;
            }
        }

        // Add to DB

        return $projects;
    }

    public function submitInspection($id) {
        $loginNeeded = false;
        $client = new Client();
        $cookieJar = new CookieJar();
        try {
            $response = $client->get(self::BASE_URL . '/my-projects', [
                'cookies' => $cookieJar
            ]);
        } catch(\Exception $e) {
            if($e->getCode() === 403) {
                $loginNeeded = true;
            }
        }
        if($loginNeeded) {
            $response = $client->post(self::LOGIN_URL, [
                'form_params' => [
                    'name' => 'dylanhobbs',
                    'pass' => 'Tarnish-Palpable5-Salsa-Outbid',
                    'form_build_id' => '',
                    'form_id' => 'user_login',
                    'op' => 'Log+in'
                ],
                'allow_redirects' => true,
                'cookies' => $cookieJar
            ]);
        }

        $url = self::BASE_URL . "/node/add/trap-record?field_trap_record_trap=${id}&destination=node/${id}";

        $response = $client->get( $url, [
            'cookies' => $cookieJar
        ]);
        $htmlString = (string) $response->getBody();
        $crawler = new Crawler($htmlString);
        $formBuildID = $crawler->filter('.col-md-12 > input:nth-child(2)')->attr('value');
        $formToken = $crawler->filter('.col-md-12 > input:nth-child(3)')->attr('value');
        $formID = $crawler->filter('.col-md-12 > input:nth-child(4)')->attr('value');

        $response = $client->request('POST', $url, [
            'cookies' => $cookieJar,
            'multipart' => [
                [
                    'name' => 'field_trap_record_date[und][0][value][date]',
                    'contents' => '24 Aug 2021'
                ],
                [
                    'name' => 'field_trap_record_date[und][0][value][time]',
                    'contents' => '07:21'
                ],
                [
                    'name' => 'field_trap_record_recorded_by[und][0][value]',
                    'contents' => 'Dylan Test'
                ],
                [
                    'name' => 'field_trap_record_species_caught[und]',
                    'contents' => '' . $this->speciesToValue('Mouse')
                ],
                [
                    'name' => 'field_gender[und]',
                    'contents' => '_none'
                ],
                [
                    'name' => 'field_maturity[und]',
                    'contents' => '_none'
                ],
                [
                    'name' => 'field_images[und][0][_weight]',
                    'contents' => "0"
                ],
                [
                    'name' => 'field_images[und][0][fid]',
                    'contents' => "0"
                ],
                [
                    'name' => 'field_images[und][0][display]',
                    'contents' => '1'
                ],
                [
                    'name' => 'field_trap_record_status[und]',
                    'contents' => '' . $this->statusToValue('Sprung')
                ],
                [
                    'name' => 'field_strikes[und][0][value]',
                    'contents' => '1'
                ],
                [
                    'name' => 'field_trap_record_trap_condition[und]',
                    'contents' => 'OK'
                ],
                [
                    'name' => 'field_trap_record_rebaited[und]',
                    'contents' => 'Yes'
                ],
                [
                    'name' => 'field_trap_record_bait_type[und][]',
                    'contents' => '' . $this->baitToValue('Whole egg')
                ],
                [
                    'name' => 'field_bait_sub_type[und][0][value]',
                    'contents' => ''
                ],
                [
                    'name' => 'changed',
                    'contents' => ''
                ],
                [
                    'name' => 'form_build_id',
                    'contents' => $formBuildID
                ],
                [
                    'name' => 'form_token',
                    'contents' => $formToken
                ],
                [
                    'name' => 'form_id',
                    'contents' => $formID
                ],
                [
                    'name' => 'additional_settings__active_tab',
                    'contents' => ''
                ],
                [
                    'name' => 'op',
                    'contents' => 'Save'
                ],
            ]
        ]);
        return $response->getBody();
    }

//    public function submitInspection($id) {
////        return $id;
//        /*
//         * Login
//         */
//        $browser = new HttpBrowser(HttpClient::create());
//        $browser->request('GET', self::BASE_URL);
//        $browser->clickLink('Log in');
//        $browser->submitForm('Log in', [
//            'name' => 'dylanhobbs',
//            'pass' => 'Tarnish-Palpable5-Salsa-Outbid',
//            'form_build_id' => '',
//            'form_id' => 'user_login',
//            'op' => 'Log+in'
//        ]);
//
//
//        // Naviage to form
//        $url = self::BASE_URL . "/node/add/trap-record?field_trap_record_trap=${id}&destination=node/${id}";
//        $crawler = $browser->request('GET', $url);
//
//        $form = $crawler->selectButton('edit-submit')->form();
//        $form['field_trap_record_recorded_by[und][0][value]']->setValue('Dylan Test');
//        $form['field_trap_record_species_caught[und]']->select($this->speciesToValue('Mouse'));
//        $form['field_trap_record_status[und]']->select($this->statusToValue('Sprung'));
//        $form['field_trap_record_trap_condition[und]']->select('OK');
//        $form['field_trap_record_rebaited[und]']->select('Yes');
//        $form['field_trap_record_bait_type[und][]']->select($this->baitToValue('Whole egg'));
//
//        $crawler = $browser->submit($form);
//
//        return $browser->getResponse()->getContent();
//    }

    private function speciesToValue($species) {
        switch ($species) {
            case "None":
                return 82;
            case "Unspecified":
                return 103;
            case "Bird":
                return 29;
            case "Cat":
                return 27;
            case "Deer":
                return 1073;
            case "Dog":
                return 104;
            case "Ferret":
                return 20;
            case "Goat":
                return 3449;
            case "Hare":
                return 2682;
            case "Hedgehog":
                return 25;
            case "Magpie":
                return 309;
            case "Mouse":
                return 24;
            case "Peafowl":
                return 2987;
            case "Pig":
                return 1072;
            case "Possum":
                return 19;
            case "Pūkeko":
                return 4430;
            case "Rabbit":
                return 26;
            case "Rat":
                return 23;
            case "-Rat - Kiore":
                return 3409;
            case "-Rat - Norway":
                return 3410;
            case "-Rat - Ship":
                return 3411;
            case "Stoat":
                return 22;
            case "Turkey":
                return 2988;
            case "Other":
                return 30;
            case "Weasel":
                return 21;
            default:
                return 'Undefined';
        }
    }

    private function statusToValue($status) {
        switch($status) {
            case "Removed for Repair":
                return 2615;
            case "Sprung":
                return 2;
            case "Trap Replaced":
                return 2616;
            case "Still set, bait OK":
                return 1;
            case "Still set, bait missing":
                return 3;
            case "Still set, bait bad":
                return 91;
            case "Trap gone":
                return 4;
            case "Trap interfered by stock":
                return 5;
            default:
                return 'Undefined';
        }
    }

    private function baitToValue($bait) {
        switch($bait) {
            case "Carrot":
                return 98;
            case "Lure-it Salmon Spray":
                return 2484;
            case "Smooth":
                return 1065;
            case "Terracotta Lures":
                return 2485;
            case "Cereal":
                return 106;
            case "Cheese":
                return 205;
            case "Chocolate":
                return 99;
            case "Dehydrated Rabbit":
                return 80;
            case "Dried fruit":
                return 46;
            case "Ferret bedding":
                return 100;
            case "Fresh fruit":
                return 45;
            case "Fresh meat":
                return 16;
            case "Fresh Possum":
                return 274;
            case "Fresh Rabbit":
                return 273;
            case "Golf ball":
                return 96;
            case "Goodnature Rat and Mouse Lure":
                return 821;
            case "Goodnature Stoat Lure":
                return 798;
            case "Lure":
                return 47;
            case "Mayo":
                return 824;
            case "Mustelid and Cat Lure":
                return 92;
            case "Nut":
                return 812;
            case "Nutella":
                return 1036;
            case "Peanut butter":
                return 48;
            case "Possum Dough":
                return 850;
            case "Rat and Possum Lure":
                return 93;
            case "Rat oil":
                return 101;
            case "Salted meat":
                return 17;
            case "Salted Possum":
                return 275;
            case "Salted Rabbit":
                return 272;
            case "Tinned Sardines":
                return 768;
            case "Whole egg":
                return 15;
            case "None":
                return 83;
            case "Other (please specify)":
                return 18;
            default:
                return 'undefined';
        }
    }
}
