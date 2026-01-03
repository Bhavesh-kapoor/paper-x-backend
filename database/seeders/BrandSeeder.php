<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        // Complete list of brands/mills from the document
        $brands = [
            'ITC Paperboards & Specialty Papers',
            'ITC Indobev / ITC PSPD',
            'JK Paper Ltd',
            'West Coast Paper Mills',
            'Tamil Nadu Newsprint & Papers (TNPL)',
            'International Paper APPM (India)',
            'Emami Paper Mills',
            'Ballarpur Industries (BILT)',
            'Shree Shyam Paper / Shyam Board',
            'Pudumjee Paper Products',
            'Seshasayee Paper & Boards',
            'Andhra Paper',
            'Century Pulp & Paper',
            'Naini Papers',
            'Orient Paper & Industries',
            'Khanna Paper Mills',
            'West Coast Kraft Division',
            'Shree Renuka Papers',
            'Shree Krishna Papers',
            'Shree Shyam Papers',
            'Gayatri Paper Mills',
            'Avon Papers',
            'Rama Kraft',
            'Sri Andal Paper Mills',
            'Sabari Papers',
            'Shree Rama Newsprint',
            'Pyramid Paper Mills',
            'Sri Andal Board',
            'Sri Balaji Boards',
            'Sri Ramco Board',
            'Shree Giriraj Boards',
            'Shree Shyam Boards',
            'APP (Asia Pulp & Paper)',
            'PaperOne',
            'Paperline',
            'Gold Coin',
            'APRIL Fine',
            'April Fine',
            'April Ecostar',
            'Chenming Group',
            'Nine Dragons Paper',
            'Sun Paper',
            'Lee & Man Paper',
            'SCG Paper',
            'Double A (Thailand)',
            'Stora Enso',
            'Ensocoat',
            'Ensoboard',
            'UPM',
            'UPM Finesse',
            'UPM Star',
            'BillerudKorsnäs',
            'Invercote',
            'Incada',
            'Mondi Group',
            'Mondi Kraft',
            'Mondi Board',
            'Arjowiggins',
            'Curious',
            'Conqueror',
            'WestRock',
            'Fedrigoni (Italy)',
            'Favini (Italy)',
            'Gmund (Germany)',
            'Mohawk Fine Papers (USA)',
            'Antalis (Europe Distributor Brand)',
            'N R Agarwal Industries Ltd',
            'NR Duplex',
            'NR Board',
            'NR White Back',
            'Kailash Devi Paper Mills Ltd',
            'KD Duplex',
            'KD White Back',
            'KD Grey Back',
            'Cheema Papers Ltd',
            'Cheema Duplex',
            'Cheema Board',
            'Shree Shyam Pulp & Board Mills',
            'Shree Krishna Paper Mills',
            'Shree Ram Paper Boards',
            'Shree Rama Multi-Tech',
            'Rama Paper Mills',
            'Shree Venkateshwara Papers',
            'Sri Lakshmi Boards',
            'Sri Sai Board Mills',
            'Shree Padmavathi Boards',
            'Shree Hari Paper Mills',
            'Shree Radha Krishna Paper Mills',
            'Shree Ajit Paper',
            'Shree Venkateshwara Kraft',
            'Shree Balaji Kraft',
            'Shree Padmavathi Kraft',
            'Shree Lakshmi Kraft',
            'Shree Hari Kraft',
            'Shree Radha Krishna Kraft',
        ];

        // Remove duplicates and insert
        $uniqueBrands = array_unique($brands);
        foreach ($uniqueBrands as $brand) {
            DB::table('brands')->updateOrInsert(
                ['name' => $brand],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
