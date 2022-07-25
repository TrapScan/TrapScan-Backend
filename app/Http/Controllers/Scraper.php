<?php /** @noinspection PhpParamsInspection */

namespace App\Http\Controllers;

use App\Imports\TrapImport;
use App\Jobs\UploadToTrapNZ;
use App\Models\Inspection;
use App\Models\Project;
use App\Models\Trap;
use App\Models\TrapLine;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use GuzzleHttp\Cookie\CookieJar;
use Symfony\Component\DomCrawler\Crawler;

class Scraper extends Controller
{
    const BASE_URL = "https://trap.nz";
    const LOGIN_URL = self::BASE_URL . "/user/login?destination=my-projects";
    const TRAP_URL = self::BASE_URL . "/project/trap_overview.json";

    public function uploadTraps(Request $request)
    {
        $user = $request->user();
//        if($user->email !== 'dylan@dylanhobbs.ie') {
//            return response()->json([
//                'Contact Dylan'
//            ], 400);
//        }
        if (!$user->hasRole('admin')) {
            return response()->json([
                'Connect admin.'
            ], 400);
        }
        $validated_data = $request->validate([
            'file' => 'required|file'
        ]);
        $data = new TrapImport;
        Excel::import($data, $validated_data['file'], null, \Maatwebsite\Excel\Excel::CSV);
        return response()->json([
            'CSV Uploaded',
            [
                'notes_added' => $data->notes_added,
                'trap_lines_created' => $data->trap_lines_created,
                'traps_added_to_lines' => $data->traps_added_to_lines
            ]
        ], 200);
    }

    public function scrapeSingleTrap(Request $request)
    {
        $user = $request->user();

        if (!$user->isCoordinator()) {
            return response()->json([
                'You do not have permission to do this action'
            ], 401);
        }

        $validated_data = $request->validate([
            'nz_id' => 'required'
        ]);

        $existingTrap = Trap::where('nz_trap_id', $validated_data['nz_id'])->exists();
        if($existingTrap) {
            return response()->json([
                'message' => 'This trap is already in TrapScan'
            ], 400);
        }

        try {
            $loginNeeded = false;
            $client = new Client();
            $cookieJar = new CookieJar();
            try {
                $response = $client->get(self::BASE_URL . '/my-projects', [
                    'cookies' => $cookieJar
                ]);
            } catch (\Exception $e) {
                if ($e->getCode() === 403) {
                    $loginNeeded = true;
                }
            }
            if ($loginNeeded) {
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
            try {
                $response = $client->get(self::BASE_URL . '/node/' . $validated_data['nz_id'], [
                    'cookies' => $cookieJar
                ]);
            } catch(\Exception $e) {
                return response()->json([
                    'message' => 'This trap does not exist on Trap NZ'
                ], 401);
            }

            $htmlString = (string)$response->getBody();
            $crawler = new Crawler($htmlString);
            $newTrap = [];

            $lat = $crawler->filter('div.field:nth-child(6) > div:nth-child(2) > div:nth-child(1)')->text();
            $long = $crawler->filter('div.field:nth-child(7) > div:nth-child(2) > div:nth-child(1)')->text();
            $name = $crawler->filter('.page-header')->text();
            $project = $crawler->filter('.navbar-text > strong:nth-child(1) > a:nth-child(1)')->attr('href');
            $project_id_regex = [];
            $project = preg_match('/\d+/', $project, $project_id_regex);
            $project = $project_id_regex[0];

            // TODO: Need to add nz_id to traplines to make sure it doesnt exist already, name is not unique
//            $trapLine = $crawler->filter('div.field:nth-child(5) > div:nth-child(2) > div:nth-child(1) > a:nth-child(1)')->attr('href');
//            $trapline_id_regex = [];
//            preg_match('/\d+/', $trapLine, $trapline_id_regex);
//            $trapLine = $trapline_id_regex[0];
//            $trapline_name = $crawler->filter('div.field:nth-child(5) > div:nth-child(2) > div:nth-child(1) > a:nth-child(1)')->text();

            $newTrap['coordinates'] = new Point($long, $lat);
            $newTrap['name'] = $name;
            $newTrap['nz_trap_id'] = $validated_data['nz_id'];
            $newTrap['project_id'] = $project;

            $existingProject = Project::where('nz_id', $project)->first();
            if (!$existingProject) throw new \Exception('This project is not part of TrapScan. ' . $newTrap['project_id'] . ' was not found');

            $newTrap['project_id'] = $existingProject->id;
            if(!$user->isCoordinatorOf($existingProject)) {
                return response()->json([
                    'message' => 'You do not have coordinator access to this project'
                ], 401);
            }
            
            return Trap::create([
                'nz_trap_id' => $newTrap['nz_trap_id'],
                'project_id' => $newTrap['project_id'],
                'name' => $newTrap['name'],
                'coordinates' => $newTrap['coordinates']
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Something went wrong', 'error' => $e->getMessage()], 400);
        }
    }

    public function projects(Request $request)
    {
        $user = $request->user();

//        if($user->email !== 'dylan@dylanhobbs.ie') {
//            return response()->json([
//                'Contact Dylan'
//            ], 400);
//        }

        if (!$user->hasRole('admin')) {
            return response()->json([
                'Connect admin.'
            ], 400);
        }

        $loginNeeded = false;
        $client = new Client();
        $cookieJar = new CookieJar();
        try {
            $response = $client->get(self::BASE_URL . '/my-projects', [
                'cookies' => $cookieJar
            ]);
        } catch (\Exception $e) {
            if ($e->getCode() === 403) {
                $loginNeeded = true;
            }
        }
        if ($loginNeeded) {
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
        $htmlString = (string)$response->getBody();
        $crawler = new Crawler($htmlString);

        $projects = [];
        /*
         * Get Basic Trap Information
         */
        foreach ($crawler->filter('tbody > tr') as $node) {
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
        $added_traps = 0;
        $skipped_traps = 0;
        foreach ($projects as $index => $project) {
            $traps = [];
            $link = self::BASE_URL . $project['link'];
            // Set project in cookie
            $response = $client->get($link, [
                'cookies' => $cookieJar
            ]);
            $existing_project = Project::where('name', $project['name'])->first();
            if (!$existing_project) {
                $existing_project = Project::create([
                    'name' => $project['name'],
                    'description' => 'Fetch test'
                ]);
            }
            // Fetch Trap List
            $response = $client->get(self::TRAP_URL, [
                'cookies' => $cookieJar
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
                    if (!$trap) {
                        $added_traps += 1;
                        Trap::create([
                            'nz_trap_id' => $newTrap['nid'],
                            'project_id' => $existing_project->id,
                            'name' => $newTrap['name'],
                            'coordinates' => new Point($newTrap['coordinates'][0], $newTrap['coordinates'][1])
                        ]);
                    } else {
                        $skipped_traps += 1;
                    }
                }
                $projects[$index]['traps'] = $traps;
            }
        }

        return response()->json([
            'added' => $added_traps,
            'skipped' => $skipped_traps,
            'projects' => $projects
        ], 200);
    }

    /**
     * @param $id // TrapNZ ID -
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function submitInspection($id)
    {
        $inspection = Inspection::find(14619);
        UploadToTrapNZ::dispatch($inspection);
        return response()->json(['message' => 'yes']);

//        $loginNeeded = false;
//        $client = new Client();
//        $cookieJar = new CookieJar();
//        try {
//            $response = $client->get(self::BASE_URL . '/my-projects', [
//                'cookies' => $cookieJar
//            ]);
//        } catch(\Exception $e) {
//            if($e->getCode() === 403) {
//                $loginNeeded = true;
//            }
//        }
//        if($loginNeeded) {
//            $response = $client->post(self::LOGIN_URL, [
//                'form_params' => [
//                    'name' =>  env('TRAP_NZ_USERNAME', 'dylan'),
//                    'pass' => env('TRAP_NZ_PASSWORD', 'notmypassword'),
//                    'form_build_id' => '',
//                    'form_id' => 'user_login',
//                    'op' => 'Log+in'
//                ],
//                'allow_redirects' => true,
//                'cookies' => $cookieJar
//            ]);
//        }
//
//        // TODO: Add inspection's project ID here
//        // Visit the correct project page to be allowed access to the submit form
//        $project_url = self::BASE_URL . "/node/3163834";
//        $project_response = $client->get($project_url, [
//            'cookies' => $cookieJar
//        ]);
//
//        $url = self::BASE_URL . "/node/add/trap-record?field_trap_record_trap=${id}&destination=node/${id}";
//
//        $response = $client->get( $url, [
//            'cookies' => $cookieJar
//        ]);
//        $htmlString = (string) $response->getBody();
//        $crawler = new Crawler($htmlString);
//        $formBuildID = $crawler->filter('.col-md-12 > input:nth-child(2)')->attr('value');
//        $formToken = $crawler->filter('.col-md-12 > input:nth-child(3)')->attr('value');
//        $formID = $crawler->filter('.col-md-12 > input:nth-child(4)')->attr('value');
//
//        $response = $client->request('POST', $url, [
//            'cookies' => $cookieJar,
//            'multipart' => [
//                [
//                    'name' => 'field_trap_record_date[und][0][value][date]',
//                    'contents' => '30 Oct 2021'
//                ],
//                [
//                    'name' => 'field_trap_record_date[und][0][value][time]',
//                    'contents' => '07:21'
//                ],
//                [
//                    'name' => 'field_trap_record_recorded_by[und][0][value]',
//                    'contents' => 'Dylan Tester 1'
//                ],
//                [
//                    'name' => 'field_trap_record_species_caught[und]',
//                    'contents' => '' . $this->speciesToValue('Rat')
//                ],
//                [
//                    'name' => 'field_gender[und]',
//                    'contents' => '_none'
//                ],
//                [
//                    'name' => 'field_maturity[und]',
//                    'contents' => '_none'
//                ],
//                [
//                    'name' => 'field_images[und][0][_weight]',
//                    'contents' => "0"
//                ],
//                [
//                    'name' => 'field_images[und][0][fid]',
//                    'contents' => "0"
//                ],
//                [
//                    'name' => 'field_images[und][0][display]',
//                    'contents' => '1'
//                ],
//                [
//                    'name' => 'field_trap_record_status[und]',
//                    'contents' => '' . $this->statusToValue('Sprung')
//                ],
//                [
//                    'name' => 'field_strikes[und][0][value]',
//                    'contents' => '1'
//                ],
//                [
//                    'name' => 'field_trap_record_trap_condition[und]',
//                    'contents' => 'OK'
//                ],
//                [
//                    'name' => 'field_trap_record_rebaited[und]',
//                    'contents' => 'Yes'
//                ],
//                [
//                    'name' => 'field_trap_record_bait_type[und][]',
//                    'contents' => '' . $this->baitToValue('Whole egg')
//                ],
//                [
//                    'name' => 'field_bait_sub_type[und][0][value]',
//                    'contents' => ''
//                ],
//                [
//                    'name' => 'changed',
//                    'contents' => ''
//                ],
//                [
//                    'name' => 'form_build_id',
//                    'contents' => $formBuildID
//                ],
//                [
//                    'name' => 'form_token',
//                    'contents' => $formToken
//                ],
//                [
//                    'name' => 'form_id',
//                    'contents' => $formID
//                ],
//                [
//                    'name' => 'additional_settings__active_tab',
//                    'contents' => ''
//                ],
//                [
//                    'name' => 'op',
//                    'contents' => 'Save'
//                ],
//            ]
//        ]);
//        return $response->getBody();
    }

    private function speciesToValue($species)
    {
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

    private function statusToValue($status)
    {
        switch ($status) {
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

    private function baitToValue($bait)
    {
        switch ($bait) {
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
