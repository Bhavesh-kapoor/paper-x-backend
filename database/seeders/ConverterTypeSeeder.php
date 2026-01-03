<?php

namespace Database\Seeders;

use App\Models\ConverterType;
use Illuminate\Database\Seeder;

class ConverterTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            // CORRUGATED
            ['name' => '3-Ply Corrugated Box Manufacturer', 'category' => 'corrugated', 'sort_order' => 1],
            ['name' => '5-Ply Corrugated Box Manufacturer', 'category' => 'corrugated', 'sort_order' => 2],
            ['name' => '7-Ply Corrugated Box Manufacturer', 'category' => 'corrugated', 'sort_order' => 3],
            ['name' => 'Heavy Duty / Export Corrugated Box Manufacturer', 'category' => 'corrugated', 'sort_order' => 4],
            ['name' => 'Corrugated Master Carton Manufacturer', 'category' => 'corrugated', 'sort_order' => 5],
            ['name' => 'Slotted Container Manufacturer', 'category' => 'corrugated', 'sort_order' => 6],
            ['name' => 'Die-Cut Corrugated Box Manufacturer', 'category' => 'corrugated', 'sort_order' => 7],
            ['name' => 'Corrugated Roll Manufacturer', 'category' => 'corrugated', 'sort_order' => 8],
            ['name' => 'Corrugated Sheet Plant (Only Sheets)', 'category' => 'corrugated', 'sort_order' => 9],
            ['name' => 'Corrugated Pad / Layer Pad Manufacturer', 'category' => 'corrugated', 'sort_order' => 10],
            ['name' => 'Industrial Partition Manufacturer', 'category' => 'corrugated', 'sort_order' => 11],
            ['name' => 'Corrugated Furniture Manufacturer', 'category' => 'corrugated', 'sort_order' => 12],
            ['name' => 'E-commerce Corrugated Packaging Converter', 'category' => 'corrugated', 'sort_order' => 13],
            ['name' => 'Automotive Corrugated Packaging Converter', 'category' => 'corrugated', 'sort_order' => 14],
            
            // RIGID, SET-UP & LUXURY
            ['name' => 'High-End Rigid Box Manufacturer', 'category' => 'rigid', 'sort_order' => 15],
            ['name' => 'Handmade Rigid Box Manufacturer', 'category' => 'rigid', 'sort_order' => 16],
            ['name' => 'Semi-Automatic Rigid Box Manufacturer', 'category' => 'rigid', 'sort_order' => 17],
            ['name' => 'Premium Set-Up Box Manufacturer', 'category' => 'rigid', 'sort_order' => 18],
            ['name' => 'Magnetic Rigid Box Specialist', 'category' => 'rigid', 'sort_order' => 19],
            ['name' => 'Drawer / Slide Box Specialist', 'category' => 'rigid', 'sort_order' => 20],
            ['name' => 'Hinged Lid Box Manufacturer', 'category' => 'rigid', 'sort_order' => 21],
            ['name' => 'Book-Style Box Manufacturer', 'category' => 'rigid', 'sort_order' => 22],
            ['name' => 'Shoulder / Neck Box Manufacturer', 'category' => 'rigid', 'sort_order' => 23],
            ['name' => 'EVA / Foam-Integrated Box Manufacturer', 'category' => 'rigid', 'sort_order' => 24],
            ['name' => 'Fabric-Wrapped Rigid Box Manufacturer', 'category' => 'rigid', 'sort_order' => 25],
            ['name' => 'Leatherette-Wrapped Box Manufacturer', 'category' => 'rigid', 'sort_order' => 26],
            
            // FOLDING CARTON / MONO CARTON
            ['name' => 'Pharmaceutical Carton Specialist', 'category' => 'folding_carton', 'sort_order' => 27],
            ['name' => 'OTC Carton Converter', 'category' => 'folding_carton', 'sort_order' => 28],
            ['name' => 'FMCG Mono Carton Converter', 'category' => 'folding_carton', 'sort_order' => 29],
            ['name' => 'Cosmetic & Personal Care Carton Converter', 'category' => 'folding_carton', 'sort_order' => 30],
            ['name' => 'Liquor Mono Carton Converter', 'category' => 'folding_carton', 'sort_order' => 31],
            ['name' => 'Cigarette Carton Converter', 'category' => 'folding_carton', 'sort_order' => 32],
            ['name' => 'Confectionery Carton Converter', 'category' => 'folding_carton', 'sort_order' => 33],
            ['name' => 'Ice Cream Carton Converter', 'category' => 'folding_carton', 'sort_order' => 34],
            ['name' => 'Frozen Food Carton Converter', 'category' => 'folding_carton', 'sort_order' => 35],
            ['name' => 'Small Batch Carton Converter', 'category' => 'folding_carton', 'sort_order' => 36],
            
            // PRINTING – COMMERCIAL, SECURITY & SPECIALISED
            ['name' => 'Commercial Offset Printer', 'category' => 'printing', 'sort_order' => 37],
            ['name' => 'Packaging Offset Printer', 'category' => 'printing', 'sort_order' => 38],
            ['name' => 'Book Printing Press', 'category' => 'printing', 'sort_order' => 39],
            ['name' => 'Magazine Printer', 'category' => 'printing', 'sort_order' => 40],
            ['name' => 'Newspaper Printer', 'category' => 'printing', 'sort_order' => 41],
            ['name' => 'Security Printer', 'category' => 'printing', 'sort_order' => 42],
            ['name' => 'Exam Paper Printer', 'category' => 'printing', 'sort_order' => 43],
            ['name' => 'Cheque / MICR Printer', 'category' => 'printing', 'sort_order' => 44],
            ['name' => 'Lottery / Ticket Printer', 'category' => 'printing', 'sort_order' => 45],
            ['name' => 'UV Offset Printer', 'category' => 'printing', 'sort_order' => 46],
            ['name' => 'Hybrid Offset + Digital Printer', 'category' => 'printing', 'sort_order' => 47],
            ['name' => 'Large Format Printer', 'category' => 'printing', 'sort_order' => 48],
            ['name' => 'Signage & Display Printer', 'category' => 'printing', 'sort_order' => 49],
            
            // LABELS, TAGS & IDENTIFICATION
            ['name' => 'Paper Label Converter', 'category' => 'labels', 'sort_order' => 50],
            ['name' => 'Pressure Sensitive Label Converter', 'category' => 'labels', 'sort_order' => 51],
            ['name' => 'Self-Adhesive Label Converter', 'category' => 'labels', 'sort_order' => 52],
            ['name' => 'Wrap-Around Label Converter', 'category' => 'labels', 'sort_order' => 53],
            ['name' => 'Shrink Sleeve Converter (Paper-Hybrid)', 'category' => 'labels', 'sort_order' => 54],
            ['name' => 'Security Label Manufacturer', 'category' => 'labels', 'sort_order' => 55],
            ['name' => 'Tamper Evident Label Converter', 'category' => 'labels', 'sort_order' => 56],
            ['name' => 'RFID Label Converter', 'category' => 'labels', 'sort_order' => 57],
            ['name' => 'Barcode & Variable Data Label Converter', 'category' => 'labels', 'sort_order' => 58],
            ['name' => 'Garment Tag Manufacturer', 'category' => 'labels', 'sort_order' => 59],
            ['name' => 'Jewellery Tag Manufacturer', 'category' => 'labels', 'sort_order' => 60],
            
            // BOOKS, STATIONERY & EDUCATION
            ['name' => 'Textbook Printer', 'category' => 'books_stationery', 'sort_order' => 61],
            ['name' => 'Notebook Manufacturer', 'category' => 'books_stationery', 'sort_order' => 62],
            ['name' => 'Diary & Planner Manufacturer', 'category' => 'books_stationery', 'sort_order' => 63],
            ['name' => 'Examination Answer Book Manufacturer', 'category' => 'books_stationery', 'sort_order' => 64],
            ['name' => 'Office Stationery Manufacturer', 'category' => 'books_stationery', 'sort_order' => 65],
            ['name' => 'School Stationery Manufacturer', 'category' => 'books_stationery', 'sort_order' => 66],
            ['name' => 'Art & Drawing Book Manufacturer', 'category' => 'books_stationery', 'sort_order' => 67],
            ['name' => 'Coffee Table Book Printer', 'category' => 'books_stationery', 'sort_order' => 68],
            ['name' => 'Hardcover Book Manufacturer', 'category' => 'books_stationery', 'sort_order' => 69],
            ['name' => 'Library Binding Unit', 'category' => 'books_stationery', 'sort_order' => 70],
            
            // PAPER BAGS & FLEXIBLE PAPER PACKAGING
            ['name' => 'SOS Paper Bag Manufacturer', 'category' => 'paper_bags', 'sort_order' => 71],
            ['name' => 'V-Bottom Paper Bag Manufacturer', 'category' => 'paper_bags', 'sort_order' => 72],
            ['name' => 'Shopping Bag Manufacturer', 'category' => 'paper_bags', 'sort_order' => 73],
            ['name' => 'Grocery Bag Manufacturer', 'category' => 'paper_bags', 'sort_order' => 74],
            ['name' => 'Bakery Paper Bag Manufacturer', 'category' => 'paper_bags', 'sort_order' => 75],
            ['name' => 'Food Delivery Bag Manufacturer', 'category' => 'paper_bags', 'sort_order' => 76],
            ['name' => 'Greaseproof Bag Manufacturer', 'category' => 'paper_bags', 'sort_order' => 77],
            ['name' => 'Multi-Wall Paper Sack Manufacturer', 'category' => 'paper_bags', 'sort_order' => 78],
            ['name' => 'Cement / Chemical Paper Sack Manufacturer', 'category' => 'paper_bags', 'sort_order' => 79],
            
            // FOOD SERVICE & DISPOSABLES
            ['name' => 'Paper Cup Manufacturer', 'category' => 'food_service', 'sort_order' => 80],
            ['name' => 'Paper Bowl Manufacturer', 'category' => 'food_service', 'sort_order' => 81],
            ['name' => 'Paper Lid Manufacturer', 'category' => 'food_service', 'sort_order' => 82],
            ['name' => 'Paper Straw Manufacturer', 'category' => 'food_service', 'sort_order' => 83],
            ['name' => 'Paper Plate Manufacturer', 'category' => 'food_service', 'sort_order' => 84],
            ['name' => 'Molded Fiber Tableware Manufacturer', 'category' => 'food_service', 'sort_order' => 85],
            ['name' => 'QSR Disposable Packaging Converter', 'category' => 'food_service', 'sort_order' => 86],
            
            // INDUSTRIAL & ENGINEERED PAPER PRODUCTS
            ['name' => 'Honeycomb Board Manufacturer', 'category' => 'industrial', 'sort_order' => 87],
            ['name' => 'Honeycomb Panel Manufacturer', 'category' => 'industrial', 'sort_order' => 88],
            ['name' => 'Edge Board / Angle Board Manufacturer', 'category' => 'industrial', 'sort_order' => 89],
            ['name' => 'Paper Pallet Manufacturer', 'category' => 'industrial', 'sort_order' => 90],
            ['name' => 'Paper Cushioning Manufacturer', 'category' => 'industrial', 'sort_order' => 91],
            ['name' => 'Paper Dunnage Manufacturer', 'category' => 'industrial', 'sort_order' => 92],
            ['name' => 'Pulp Molded Industrial Packaging Manufacturer', 'category' => 'industrial', 'sort_order' => 93],
            
            // TUBES, CORES & CYLINDRICAL PRODUCTS
            ['name' => 'Paper Tube Manufacturer', 'category' => 'tubes_cores', 'sort_order' => 94],
            ['name' => 'Paper Core Manufacturer', 'category' => 'tubes_cores', 'sort_order' => 95],
            ['name' => 'Textile Core Manufacturer', 'category' => 'tubes_cores', 'sort_order' => 96],
            ['name' => 'Film / Foil Core Manufacturer', 'category' => 'tubes_cores', 'sort_order' => 97],
            ['name' => 'Spiral Wound Tube Manufacturer', 'category' => 'tubes_cores', 'sort_order' => 98],
            ['name' => 'Parallel Wound Tube Manufacturer', 'category' => 'tubes_cores', 'sort_order' => 99],
            
            // JOB WORK / SPECIALISED SERVICES
            ['name' => 'Sheet Cutting Job-Worker', 'category' => 'job_work', 'sort_order' => 100],
            ['name' => 'Reel Slitting Job-Worker', 'category' => 'job_work', 'sort_order' => 101],
            ['name' => 'Reel to Sheet Converter', 'category' => 'job_work', 'sort_order' => 102],
            ['name' => 'Die-Cutting Job-Worker', 'category' => 'job_work', 'sort_order' => 103],
            ['name' => 'Pasting Job-Worker', 'category' => 'job_work', 'sort_order' => 104],
            ['name' => 'Lamination Job-Worker', 'category' => 'job_work', 'sort_order' => 105],
            ['name' => 'Foil Stamping Job-Worker', 'category' => 'job_work', 'sort_order' => 106],
            ['name' => 'UV Coating Job-Worker', 'category' => 'job_work', 'sort_order' => 107],
            ['name' => 'Binding Job-Worker', 'category' => 'job_work', 'sort_order' => 108],
            ['name' => 'Assembly & Kitting Job-Worker', 'category' => 'job_work', 'sort_order' => 109],
        ];

        foreach ($types as $type) {
            ConverterType::firstOrCreate(
                ['name' => $type['name']],
                $type
            );
        }
    }
}
