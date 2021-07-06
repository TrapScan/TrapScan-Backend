<?php

use Illuminate\Support\Facades\Auth;

if (!function_exists('getUniqueTrapId')) {
    function getUniqueTrapId() {
        $found = false;
        while(!$found) {
            $mouri_words = [
                'Aroha',
                'Awa',
                'Haka',
                'Hangi',
                'Hapu',
                'Hīkoi',
                'Hui',
                'Iti',
                'Iwi',
                'Kai',
                'Karakia',
                'Kaumatua',
                'Kauri',
                'Kiwi',
                'Koha',
                'Mahi',
                'Mana',
                'Manuhiri',
                'Māori',
                'Marae',
                'Maunga',
                'Moa',
                'Moana',
                'Motu',
                'Nui',
                'Pā',
                'Pākehā',
                'Pounamu',
                'Puku',
                'Rangatira',
                'Taihoa',
                'Tama',
                'Tamāhine',
                'Tamariki',
                'Tāne',
                'Tangi',
                'Taonga',
                'Tapu',
                'Tipun',
                'Tuatara',
                'Wahine',
                'Wai',
                'Waiata',
                'Waka',
                'Whaikōrero',
                'Whakapapa',
                'Whānau',
                'Whenua',
            ];
            $id = str_pad(rand(0, pow(10, 4)-1), 4, '0', STR_PAD_LEFT);
            $word = array_rand($mouri_words, 1);
            $candidate = $word . $id;
            if(! \App\Models\Trap::where('qr_id', $candidate)->exists()) {
                return $candidate;
            }
        }
    }
}
