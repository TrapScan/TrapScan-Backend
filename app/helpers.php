<?php

use App\Models\QR;
use App\Models\Trap;
use Illuminate\Support\Facades\Auth;

if (!function_exists('getUniqueTrapId')) {
    function getUniqueTrapId() {
        $found = false;
        while(!$found) {
            $mouri_words = [
                    'arero',
                    'aroha',
                    'aruhe',
                    'awa',
                    'hihi',
                    'hoiho',
                    'hui',
                    'huia',
                    'ihu',
                    'iti',
                    'kai',
                    'karoro',
                    'kauae',
                    'kauri',
                    'kawau',
                    'kea',
                    'kiwi',
                    'koha',
                    'koreke',
                    'kuaka',
                    'mahi',
                    'mamaku',
                    'mana',
                    'manawa',
                    'matuku',
                    'mauri',
                    'miro',
                    'moa',
                    'moana',
                    'motu',
                    'niho',
                    'nui',
                    'pae',
                    'papa',
                    'piopio',
                    'poaka',
                    'poho',
                    'ponga',
                    'poto',
                    'puha',
                    'puke',
                    'puku',
                    'rimu',
                    'ringa',
                    'roa',
                    'ruru',
                    'tai',
                    'tawa',
                    'totoa',
                    'turi',
                    'tutu',
                    'upoko',
                    'wai',
                    'waka',
                    'anuhe',
                    'weka',
                    'tuhi',
                    'ngaio',
                    'pepeke',
                    'huhu',
                    'karaka',
                    'whio',
            ];
            $id = str_pad(rand(0, pow(10, 4)-1), 4, '0', STR_PAD_LEFT);
            $word = $mouri_words[array_rand($mouri_words, 1)];
            $candidate = $word . '-' . $id;
            if(! QR::where('qr_code', $candidate)->exists()) {
                return $candidate;
            }
        }
    }
}
