<?php

namespace Database\Seeders;

use App\Models\Machine;
use Illuminate\Database\Seeder;

class MachineSeeder extends Seeder
{
    public function run(): void
    {
        $machines = [
            // PRINTING MACHINES
            ['name' => 'Sheetfed Offset Printing Machine', 'type' => 'printing', 'description' => 'Offset printing machine for sheetfed applications'],
            ['name' => 'Web Offset Printing Machine', 'type' => 'printing', 'description' => 'Offset printing machine for web applications'],
            ['name' => 'Mini Offset Printing Machine', 'type' => 'printing', 'description' => 'Small format offset printing machine'],
            ['name' => 'Single Color Offset', 'type' => 'printing', 'description' => 'Single color offset printing machine'],
            ['name' => 'Two Color Offset', 'type' => 'printing', 'description' => 'Two color offset printing machine'],
            ['name' => 'Four Color Offset', 'type' => 'printing', 'description' => 'Four color offset printing machine'],
            ['name' => 'Five / Six Color Offset', 'type' => 'printing', 'description' => 'Five or six color offset printing machine'],
            ['name' => 'Perfecting Offset Machine', 'type' => 'printing', 'description' => 'Perfecting offset printing machine'],
            ['name' => 'Digital Sheetfed Press', 'type' => 'printing', 'description' => 'Digital sheetfed printing press'],
            ['name' => 'Digital Label Printing Machine', 'type' => 'printing', 'description' => 'Digital label printing machine'],
            ['name' => 'Variable Data Printing Machine', 'type' => 'printing', 'description' => 'Variable data printing machine'],
            ['name' => 'Flexo Printing Machine (Stack Type)', 'type' => 'printing', 'description' => 'Flexographic printing machine stack type'],
            ['name' => 'Flexo Printing Machine (CI Type)', 'type' => 'printing', 'description' => 'Flexographic printing machine CI type'],
            ['name' => 'Flexo Printing Machine (Inline)', 'type' => 'printing', 'description' => 'Flexographic printing machine inline'],
            ['name' => 'Rotogravure Printing Machine', 'type' => 'printing', 'description' => 'Rotogravure printing machine'],
            
            // DIE CUTTING & CREASING MACHINES
            ['name' => 'Manual Die Cutting Machine', 'type' => 'die_cutting', 'description' => 'Manual die cutting machine'],
            ['name' => 'Semi Automatic Die Cutter', 'type' => 'die_cutting', 'description' => 'Semi automatic die cutting machine'],
            ['name' => 'Automatic Die Cutting Machine', 'type' => 'die_cutting', 'description' => 'Automatic die cutting machine'],
            ['name' => 'Automatic Die Cutter with Stripping', 'type' => 'die_cutting', 'description' => 'Automatic die cutter with stripping'],
            ['name' => 'Automatic Die Cutter with Blank Separator', 'type' => 'die_cutting', 'description' => 'Automatic die cutter with blank separator'],
            ['name' => 'Rotary Die Cutting Machine', 'type' => 'die_cutting', 'description' => 'Rotary die cutting machine'],
            ['name' => 'Automatic Flatbed Die Cutter', 'type' => 'die_cutting', 'description' => 'Automatic flatbed die cutter'],
            ['name' => 'Blanking Machine', 'type' => 'die_cutting', 'description' => 'Blanking machine'],
            ['name' => 'Grooving Machine', 'type' => 'die_cutting', 'description' => 'Grooving machine'],
            
            // FOLDER GLUERS & CARTON MACHINES
            ['name' => 'Folder Gluer Machine', 'type' => 'folding_gluing', 'description' => 'Folder gluer machine'],
            ['name' => 'Crash Lock Bottom Folder Gluer', 'type' => 'folding_gluing', 'description' => 'Crash lock bottom folder gluer'],
            ['name' => 'Straight Line Folder Gluer', 'type' => 'folding_gluing', 'description' => 'Straight line folder gluer'],
            ['name' => '4/6 Corner Folder Gluer', 'type' => 'folding_gluing', 'description' => '4/6 corner folder gluer'],
            ['name' => 'Window Patching Machine', 'type' => 'folding_gluing', 'description' => 'Window patching machine'],
            ['name' => 'Carton Erector', 'type' => 'folding_gluing', 'description' => 'Carton erector'],
            ['name' => 'Carton Sealer', 'type' => 'folding_gluing', 'description' => 'Carton sealer'],
            ['name' => 'Folder Gluer', 'type' => 'folding_gluing', 'description' => 'Folder gluer'],
            ['name' => 'Automatic Folder Gluer', 'type' => 'folding_gluing', 'description' => 'Automatic folder gluer'],
            ['name' => 'Pasting Machine', 'type' => 'folding_gluing', 'description' => 'Pasting machine'],
            ['name' => 'Gluing Machine', 'type' => 'folding_gluing', 'description' => 'Gluing machine'],
            ['name' => 'Stitching Machine', 'type' => 'folding_gluing', 'description' => 'Stitching machine'],
            ['name' => 'Taping Machine', 'type' => 'folding_gluing', 'description' => 'Taping machine'],
            ['name' => 'Folding Machine', 'type' => 'folding_gluing', 'description' => 'Folding machine'],
            
            // RIGID BOX MAKING MACHINES
            ['name' => 'Grey Board Grooving Machine', 'type' => 'rigid_box', 'description' => 'Grey board grooving machine'],
            ['name' => 'V-Grooving Machine', 'type' => 'rigid_box', 'description' => 'V-grooving machine'],
            ['name' => 'Rigid Box Wrapping Machine', 'type' => 'rigid_box', 'description' => 'Rigid box wrapping machine'],
            ['name' => 'Box Forming Machine', 'type' => 'rigid_box', 'description' => 'Box forming machine'],
            ['name' => 'Corner Pasting Machine', 'type' => 'rigid_box', 'description' => 'Corner pasting machine'],
            ['name' => 'Automatic Rigid Box Making Line', 'type' => 'rigid_box', 'description' => 'Automatic rigid box making line'],
            ['name' => 'Manual Rigid Box Making Machine', 'type' => 'rigid_box', 'description' => 'Manual rigid box making machine'],
            
            // CORRUGATION MACHINES
            ['name' => 'Corrugation Line', 'type' => 'corrugation', 'description' => 'Corrugation line'],
            ['name' => 'Single Facer', 'type' => 'corrugation', 'description' => 'Single facer'],
            ['name' => 'Double Backer', 'type' => 'corrugation', 'description' => 'Double backer'],
            ['name' => 'Corrugation Line (3/5/7 Ply)', 'type' => 'corrugation', 'description' => 'Corrugation line 3/5/7 ply'],
            ['name' => 'High Speed Corrugation Plant', 'type' => 'corrugation', 'description' => 'High speed corrugation plant'],
            ['name' => 'Corrugation Slotting Machine', 'type' => 'corrugation', 'description' => 'Corrugation slotting machine'],
            ['name' => 'Flexo Folder Gluer (FFG)', 'type' => 'corrugation', 'description' => 'Flexo folder gluer'],
            ['name' => 'Corrugation Die Cutter', 'type' => 'corrugation', 'description' => 'Corrugation die cutter'],
            ['name' => 'Rotary Slotter', 'type' => 'corrugation', 'description' => 'Rotary slotter'],
            ['name' => 'Automatic Corrugation Plant', 'type' => 'corrugation', 'description' => 'Automatic corrugation plant'],
            ['name' => 'Semi-Automatic Corrugation Plant', 'type' => 'corrugation', 'description' => 'Semi-automatic corrugation plant'],
            ['name' => 'Slitter-Scorer', 'type' => 'corrugation', 'description' => 'Slitter-scorer'],
            ['name' => 'Slotter Machine', 'type' => 'corrugation', 'description' => 'Slotter machine'],
            
            // LAMINATION & COATING MACHINES
            ['name' => 'Thermal Lamination Machine', 'type' => 'lamination', 'description' => 'Thermal lamination machine'],
            ['name' => 'BOPP Lamination Machine', 'type' => 'lamination', 'description' => 'BOPP lamination machine'],
            ['name' => 'Wet Lamination Machine', 'type' => 'lamination', 'description' => 'Wet lamination machine'],
            ['name' => 'UV Coating Machine', 'type' => 'lamination', 'description' => 'UV coating machine'],
            ['name' => 'Aqueous Coating Machine', 'type' => 'lamination', 'description' => 'Aqueous coating machine'],
            ['name' => 'Roller Coating Machine', 'type' => 'lamination', 'description' => 'Roller coating machine'],
            ['name' => 'Extrusion Coating Line', 'type' => 'lamination', 'description' => 'Extrusion coating line'],
            ['name' => 'Water-Based Coating Machine', 'type' => 'lamination', 'description' => 'Water-based coating machine'],
            ['name' => 'Spot UV Machine', 'type' => 'lamination', 'description' => 'Spot UV machine'],
            
            // PAPER & BOARD CONVERTING MACHINES
            ['name' => 'Sheet Cutter (Reel to Sheet)', 'type' => 'converting', 'description' => 'Sheet cutter reel to sheet'],
            ['name' => 'Slitter Rewinder', 'type' => 'converting', 'description' => 'Slitter rewinder'],
            ['name' => 'Guillotine Cutting Machine', 'type' => 'converting', 'description' => 'Guillotine cutting machine'],
            ['name' => 'Programmable Paper Cutter', 'type' => 'converting', 'description' => 'Programmable paper cutter'],
            ['name' => 'Sheet Pasting Machine', 'type' => 'converting', 'description' => 'Sheet pasting machine'],
            ['name' => 'Board Laminating Machine', 'type' => 'converting', 'description' => 'Board laminating machine'],
            
            // BINDING & BOOK FINISHING MACHINES
            ['name' => 'Perfect Binding Machine', 'type' => 'binding', 'description' => 'Perfect binding machine'],
            ['name' => 'Saddle Stitching Machine', 'type' => 'binding', 'description' => 'Saddle stitching machine'],
            ['name' => 'Section Sewing Machine', 'type' => 'binding', 'description' => 'Section sewing machine'],
            ['name' => 'Case Binding Machine', 'type' => 'binding', 'description' => 'Case binding machine'],
            ['name' => 'Thread Binding Machine', 'type' => 'binding', 'description' => 'Thread binding machine'],
            ['name' => 'Book Trimming Machine', 'type' => 'binding', 'description' => 'Book trimming machine'],
            ['name' => 'Book Folding Machine', 'type' => 'binding', 'description' => 'Book folding machine'],
            ['name' => 'Perfect Binder', 'type' => 'binding', 'description' => 'Perfect binder'],
            ['name' => 'Case Binder', 'type' => 'binding', 'description' => 'Case binder'],
            ['name' => 'Thread Sewing Machine', 'type' => 'binding', 'description' => 'Thread sewing machine'],
            ['name' => 'Wire Stitcher', 'type' => 'binding', 'description' => 'Wire stitcher'],
            ['name' => 'Pinning Machine', 'type' => 'binding', 'description' => 'Pinning machine'],
            
            // SPECIALTY & FINISHING MACHINES
            ['name' => 'Foil Stamping Machine', 'type' => 'finishing', 'description' => 'Foil stamping machine'],
            ['name' => 'Hot Foil Stamping Machine', 'type' => 'finishing', 'description' => 'Hot foil stamping machine'],
            ['name' => 'Embossing Machine', 'type' => 'finishing', 'description' => 'Embossing machine'],
            ['name' => 'Debossing Machine', 'type' => 'finishing', 'description' => 'Debossing machine'],
            ['name' => 'UV Spot Machine', 'type' => 'finishing', 'description' => 'UV spot machine'],
            ['name' => 'Screen Printing Machine', 'type' => 'finishing', 'description' => 'Screen printing machine'],
            ['name' => 'Cold Foil Machine', 'type' => 'finishing', 'description' => 'Cold foil machine'],
            
            // PAPER BAG & CUP MACHINES
            ['name' => 'Paper Bag Making Machine', 'type' => 'paper_bag_cup', 'description' => 'Paper bag making machine'],
            ['name' => 'Paper Cup Making Machine', 'type' => 'paper_bag_cup', 'description' => 'Paper cup making machine'],
            ['name' => 'Paper Plate Making Machine', 'type' => 'paper_bag_cup', 'description' => 'Paper plate making machine'],
            ['name' => 'Paper Straw Making Machine', 'type' => 'paper_bag_cup', 'description' => 'Paper straw making machine'],
            ['name' => 'Paper Pouch Machine', 'type' => 'paper_bag_cup', 'description' => 'Paper pouch machine'],
            ['name' => 'Paper Cup Machine', 'type' => 'paper_bag_cup', 'description' => 'Paper cup machine'],
            ['name' => 'Paper Plate Machine', 'type' => 'paper_bag_cup', 'description' => 'Paper plate machine'],
            ['name' => 'Molded Pulp Machine', 'type' => 'paper_bag_cup', 'description' => 'Molded pulp machine'],
            
            // AUXILIARY & SUPPORT MACHINES
            ['name' => 'Air Compressor', 'type' => 'auxiliary', 'description' => 'Air compressor'],
            ['name' => 'Boiler', 'type' => 'auxiliary', 'description' => 'Boiler'],
            ['name' => 'Dust Extraction System', 'type' => 'auxiliary', 'description' => 'Dust extraction system'],
            ['name' => 'Shrink Wrapping Machine', 'type' => 'auxiliary', 'description' => 'Shrink wrapping machine'],
            ['name' => 'Stretch Wrapping Machine', 'type' => 'auxiliary', 'description' => 'Stretch wrapping machine'],
            ['name' => 'Palletizing Machine', 'type' => 'auxiliary', 'description' => 'Palletizing machine'],
            
            // SPECIALTY MACHINES
            ['name' => 'Molded Pulp Machine', 'type' => 'specialty', 'description' => 'Molded pulp machine'],
            ['name' => 'Honeycomb Expander', 'type' => 'specialty', 'description' => 'Honeycomb expander'],
            ['name' => 'Edge Board Machine', 'type' => 'specialty', 'description' => 'Edge board machine'],
            
            // PRINTING MACHINES (from converter doc)
            ['name' => '1-Color Offset Machine', 'type' => 'printing', 'description' => '1-color offset machine'],
            ['name' => '2-Color Offset Machine', 'type' => 'printing', 'description' => '2-color offset machine'],
            ['name' => '4-Color Offset Machine', 'type' => 'printing', 'description' => '4-color offset machine'],
            ['name' => '6-Color Offset Machine', 'type' => 'printing', 'description' => '6-color offset machine'],
            ['name' => '8-Color Offset Machine', 'type' => 'printing', 'description' => '8-color offset machine'],
            ['name' => 'Digital Production Press', 'type' => 'printing', 'description' => 'Digital production press'],
            ['name' => 'Inkjet Web Press', 'type' => 'printing', 'description' => 'Inkjet web press'],
            ['name' => 'UV Offset Press', 'type' => 'printing', 'description' => 'UV offset press'],
        ];

        foreach ($machines as $machine) {
            Machine::firstOrCreate(
                ['name' => $machine['name']],
                $machine
            );
        }
    }
}
