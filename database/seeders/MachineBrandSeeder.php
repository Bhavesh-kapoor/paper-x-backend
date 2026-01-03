<?php

namespace Database\Seeders;

use App\Models\MachineBrand;
use Illuminate\Database\Seeder;

class MachineBrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            // PRINTING MACHINES - Offset
            ['name' => 'Heidelberg', 'machine_category' => 'printing', 'sort_order' => 1],
            ['name' => 'Komori', 'machine_category' => 'printing', 'sort_order' => 2],
            ['name' => 'KBA (Koenig & Bauer)', 'machine_category' => 'printing', 'sort_order' => 3],
            ['name' => 'Mitsubishi', 'machine_category' => 'printing', 'sort_order' => 4],
            ['name' => 'Ryobi', 'machine_category' => 'printing', 'sort_order' => 5],
            ['name' => 'Sakurai', 'machine_category' => 'printing', 'sort_order' => 6],
            ['name' => 'Akiyama', 'machine_category' => 'printing', 'sort_order' => 7],
            ['name' => 'Man Roland', 'machine_category' => 'printing', 'sort_order' => 8],
            ['name' => 'Shinohara', 'machine_category' => 'printing', 'sort_order' => 9],
            
            // PRINTING MACHINES - Digital
            ['name' => 'HP Indigo', 'machine_category' => 'printing', 'sort_order' => 10],
            ['name' => 'Xerox', 'machine_category' => 'printing', 'sort_order' => 11],
            ['name' => 'Konica Minolta', 'machine_category' => 'printing', 'sort_order' => 12],
            ['name' => 'Ricoh', 'machine_category' => 'printing', 'sort_order' => 13],
            ['name' => 'Canon', 'machine_category' => 'printing', 'sort_order' => 14],
            ['name' => 'Kodak', 'machine_category' => 'printing', 'sort_order' => 15],
            ['name' => 'Xeikon', 'machine_category' => 'printing', 'sort_order' => 16],
            
            // FLEXOGRAPHIC PRINTING
            ['name' => 'Bobst', 'machine_category' => 'printing', 'sort_order' => 17],
            ['name' => 'Mark Andy', 'machine_category' => 'printing', 'sort_order' => 18],
            ['name' => 'Nilpeter', 'machine_category' => 'printing', 'sort_order' => 19],
            ['name' => 'Uteco', 'machine_category' => 'printing', 'sort_order' => 20],
            ['name' => 'Comexi', 'machine_category' => 'printing', 'sort_order' => 21],
            ['name' => 'Windmöller & Hölscher', 'machine_category' => 'printing', 'sort_order' => 22],
            ['name' => 'Webtech', 'machine_category' => 'printing', 'sort_order' => 23],
            ['name' => 'Pelican', 'machine_category' => 'printing', 'sort_order' => 24],
            
            // GRAVURE PRINTING
            ['name' => 'Kohli Gravure', 'machine_category' => 'printing', 'sort_order' => 25],
            
            // DIE CUTTING & CREASING
            ['name' => 'Yawa', 'machine_category' => 'die_cutting', 'sort_order' => 26],
            ['name' => 'Masterwork', 'machine_category' => 'die_cutting', 'sort_order' => 27],
            ['name' => 'Sanwa', 'machine_category' => 'die_cutting', 'sort_order' => 28],
            ['name' => 'Brausse', 'machine_category' => 'die_cutting', 'sort_order' => 29],
            ['name' => 'SBL', 'machine_category' => 'die_cutting', 'sort_order' => 30],
            ['name' => 'Lishunyuan', 'machine_category' => 'die_cutting', 'sort_order' => 31],
            ['name' => 'Guowang', 'machine_category' => 'die_cutting', 'sort_order' => 32],
            
            // FOLDER GLUERS & CARTON
            ['name' => 'Jagenberg', 'machine_category' => 'folding_gluing', 'sort_order' => 33],
            ['name' => 'DGM', 'machine_category' => 'folding_gluing', 'sort_order' => 34],
            ['name' => 'Vega', 'machine_category' => 'folding_gluing', 'sort_order' => 35],
            ['name' => 'Bahmueller', 'machine_category' => 'folding_gluing', 'sort_order' => 36],
            ['name' => 'Koenig & Bauer', 'machine_category' => 'folding_gluing', 'sort_order' => 37],
            
            // RIGID BOX MAKING
            ['name' => 'Emmeci', 'machine_category' => 'rigid_box', 'sort_order' => 38],
            ['name' => 'Kolbus', 'machine_category' => 'rigid_box', 'sort_order' => 39],
            ['name' => 'Zhongke', 'machine_category' => 'rigid_box', 'sort_order' => 40],
            ['name' => 'Hongming', 'machine_category' => 'rigid_box', 'sort_order' => 41],
            ['name' => 'Fuling', 'machine_category' => 'rigid_box', 'sort_order' => 42],
            ['name' => 'SMT', 'machine_category' => 'rigid_box', 'sort_order' => 43],
            ['name' => 'Sun Automation', 'machine_category' => 'rigid_box', 'sort_order' => 44],
            
            // CORRUGATION
            ['name' => 'BHS', 'machine_category' => 'corrugation', 'sort_order' => 45],
            ['name' => 'Fosber', 'machine_category' => 'corrugation', 'sort_order' => 46],
            ['name' => 'Agnati', 'machine_category' => 'corrugation', 'sort_order' => 47],
            ['name' => 'ISOWA', 'machine_category' => 'corrugation', 'sort_order' => 48],
            ['name' => 'TCY', 'machine_category' => 'corrugation', 'sort_order' => 49],
            ['name' => 'Dongfang', 'machine_category' => 'corrugation', 'sort_order' => 50],
            ['name' => 'Peters', 'machine_category' => 'corrugation', 'sort_order' => 51],
            
            // LAMINATION & COATING
            ['name' => 'Monotech', 'machine_category' => 'lamination', 'sort_order' => 52],
            ['name' => 'Komfi', 'machine_category' => 'lamination', 'sort_order' => 53],
            ['name' => 'Autobond', 'machine_category' => 'lamination', 'sort_order' => 54],
            ['name' => 'D&K', 'machine_category' => 'lamination', 'sort_order' => 55],
            ['name' => 'Nordson', 'machine_category' => 'lamination', 'sort_order' => 56],
            
            // PAPER & BOARD CONVERTING
            ['name' => 'Polar', 'machine_category' => 'converting', 'sort_order' => 57],
            ['name' => 'Wohlenberg', 'machine_category' => 'converting', 'sort_order' => 58],
            ['name' => 'Perfecta', 'machine_category' => 'converting', 'sort_order' => 59],
            ['name' => 'HPM', 'machine_category' => 'converting', 'sort_order' => 60],
            ['name' => 'Pasaban', 'machine_category' => 'converting', 'sort_order' => 61],
            ['name' => 'Bielomatik', 'machine_category' => 'converting', 'sort_order' => 62],
            
            // BINDING & BOOK FINISHING
            ['name' => 'Muller Martini', 'machine_category' => 'binding', 'sort_order' => 63],
            ['name' => 'Horizon', 'machine_category' => 'binding', 'sort_order' => 64],
            ['name' => 'Welbound', 'machine_category' => 'binding', 'sort_order' => 65],
            ['name' => 'Aster', 'machine_category' => 'binding', 'sort_order' => 66],
            
            // SPECIALTY & FINISHING
            ['name' => 'Gietz', 'machine_category' => 'finishing', 'sort_order' => 67],
            ['name' => 'Kama', 'machine_category' => 'finishing', 'sort_order' => 68],
            
            // PAPER BAG & CUP
            ['name' => 'Newlong', 'machine_category' => 'paper_bag_cup', 'sort_order' => 69],
            ['name' => 'Holweg Weber', 'machine_category' => 'paper_bag_cup', 'sort_order' => 70],
            ['name' => 'Curioni', 'machine_category' => 'paper_bag_cup', 'sort_order' => 71],
            ['name' => 'Lemo', 'machine_category' => 'paper_bag_cup', 'sort_order' => 72],
            ['name' => 'UFlex (India)', 'machine_category' => 'paper_bag_cup', 'sort_order' => 73],
        ];

        foreach ($brands as $brand) {
            MachineBrand::firstOrCreate(
                ['name' => $brand['name']],
                $brand
            );
        }
    }
}
