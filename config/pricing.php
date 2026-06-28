<?php

/**
 * Posting fee pricing model (Zupply / PaperX).
 *
 * Source: Zupply docs/Price docs (REVISED PRICING MODEL + PRICING MODEL–ALL ROLES).
 * All fees are in CREDITS (1 credit = ₹1). GST is added on top of every base fee.
 *
 * Flows:
 *  - Raw material (dealer/converter buy/sell): value band (A–D) × quantity bucket × urgency.
 *  - Ancillary materials: flat.
 *  - Machine (converter/machine dealer buy/sell): price-range bracket × urgency.
 *  - Converter jobwork (find/give): flat.
 *  - Brand packaging: flat.
 */
return [
    'gst_percent' => 18,

    // Boards entered in MM convert to gsm via this constant (matches the doc's worked example).
    'mm_to_gsm' => 660,

    // Standard sheets per ream (used when quantity_unit = reams).
    'sheets_per_ream' => 500,

    // Mill board / Indian stiff board posted in bundles: 1 bundle = this many kg.
    'kg_per_bundle' => 25,

    // Band used when a (non-ancillary) material is not found in value_band_map.
    'default_value_band' => 'A',

    // Bucket used when kg can't be derived (reels/rolls, or thickness not in GSM/MM).
    'fallback_bucket' => '501_1000',

    // Quantity buckets — ordered; `max_kg` is the inclusive upper bound (last = null = no cap).
    'buckets' => [
        ['key' => 'le_250',    'label' => '≤ 250 kg',     'max_kg' => 250],
        ['key' => '251_500',   'label' => '251–500 kg',   'max_kg' => 500],
        ['key' => '501_1000',  'label' => '501–1,000 kg', 'max_kg' => 1000],
        ['key' => '1_2mt',     'label' => '1–2 MT',       'max_kg' => 2000],
        ['key' => '2_5mt',     'label' => '2–5 MT',       'max_kg' => 5000],
        ['key' => '5_10mt',    'label' => '5–10 MT',      'max_kg' => 10000],
        ['key' => '10_plus',   'label' => '10+ MT',       'max_kg' => null],
    ],

    // Raw-material grids: band => bucketKey => [normal, urgent].
    'value_band_grids' => [
        'A' => [
            'le_250' => [29, 59],   '251_500' => [39, 79],   '501_1000' => [49, 99],
            '1_2mt' => [69, 139],   '2_5mt' => [99, 199],    '5_10mt' => [149, 299],   '10_plus' => [199, 399],
        ],
        'B' => [
            'le_250' => [39, 79],   '251_500' => [49, 99],   '501_1000' => [64, 129],
            '1_2mt' => [89, 179],   '2_5mt' => [129, 259],   '5_10mt' => [194, 389],   '10_plus' => [259, 519],
        ],
        'C' => [
            'le_250' => [49, 99],   '251_500' => [64, 129],  '501_1000' => [84, 169],
            '1_2mt' => [119, 239],  '2_5mt' => [169, 339],   '5_10mt' => [254, 509],   '10_plus' => [339, 679],
        ],
        'D' => [
            'le_250' => [64, 129],  '251_500' => [84, 169],  '501_1000' => [109, 219],
            '1_2mt' => [154, 309],  '2_5mt' => [219, 439],   '5_10mt' => [329, 659],   '10_plus' => [439, 879],
        ],
    ],

    // Ancillary materials → flat [normal, urgent]. Detected via material category.
    'ancillary_category' => 'ANCILLARY MATERIALS',
    'ancillary_flat' => ['normal' => 49, 'urgent' => 99],

    // Machine buy/sell → price-range bracket × urgency.
    'machine_default_bracket' => '5_15l',
    'machine_brackets' => [
        ['key' => 'below_2l',  'label' => 'Below ₹2 lakh',      'min' => 0,        'max' => 200000,   'normal' => 199,  'urgent' => 399],
        ['key' => '2_5l',      'label' => '₹2 – ₹5 lakh',        'min' => 200000,   'max' => 500000,   'normal' => 299,  'urgent' => 599],
        ['key' => '5_15l',     'label' => '₹5 – ₹15 lakh',       'min' => 500000,   'max' => 1500000,  'normal' => 499,  'urgent' => 999],
        ['key' => '15_50l',    'label' => '₹15 – ₹50 lakh',      'min' => 1500000,  'max' => 5000000,  'normal' => 799,  'urgent' => 1499],
        ['key' => '50l_1cr',   'label' => '₹50 lakh – ₹1 crore', 'min' => 5000000,  'max' => 10000000, 'normal' => 1199, 'urgent' => 2499],
        ['key' => 'above_1cr', 'label' => 'Above ₹1 crore',      'min' => 10000000, 'max' => null,     'normal' => 1999, 'urgent' => 3999],
    ],

    // Converter jobwork (find / give) → flat [normal, urgent].
    'jobwork_flat' => ['normal' => 199, 'urgent' => 399],

    // Brand packaging → flat [normal, urgent].
    'brand_flat' => ['normal' => 499, 'urgent' => 699],

    /*
     * Material name → value band (A–D). Keys are lower-cased material names.
     * Materials not listed fall back to `default_value_band`. Ancillary materials are
     * priced by ancillary_flat (detected via category) regardless of this map.
     */
    'value_band_map' => [
        // ---- Band A (commodity papers, kraft, basic boards) ----
        'book printing paper' => 'A',
        'ledger paper' => 'A',
        'kraft paper (mg)' => 'A',
        'kraft paper (mf)' => 'A',
        'sack kraft paper' => 'A',
        'semi kraft paper' => 'A',
        'test liner' => 'A',
        'fluting paper' => 'A',
        'corrugating medium' => 'A',
        'linerboard' => 'A',
        'ribbed kraft' => 'A',
        'eco kraft paper' => 'A',
        'white top kraft' => 'A',
        'duplex board (grey back)' => 'A',
        'duplex board (white back)' => 'A',
        'grey board' => 'A',
        'kappa board' => 'A',
        'mill board' => 'A',
        'kraft board' => 'A',
        'straw board' => 'A',
        'box board' => 'A',
        'chipboard' => 'A',

        // ---- Band B (premium writing/printing, art card, coated kraft) ----
        'maplitho paper' => 'B',
        'copier paper' => 'B',
        'offset printing paper' => 'B',
        'bond paper' => 'B',
        'clupak kraft' => 'B',
        'pe coated kraft' => 'B',
        'art card' => 'B',

        // ---- Band C (digital/creamwove, FBB/ivory/coated boards, fancy basics) ----
        'creamwove paper' => 'C',
        'digital printing paper' => 'C',
        'inkjet paper' => 'C',
        'laser printing paper' => 'C',
        'folding box board (fbb)' => 'C',
        'ivory board' => 'C',
        'coated board' => 'C',
        'uncoated board' => 'C',
        'bristol board' => 'C',
        'glassine paper' => 'C',
        'black paper' => 'C',
        'food wrapping paper' => 'C',
        'cupstock paper' => 'C',
        'coloured paper' => 'C',
        'art paper' => 'C',
        'kraft texture paper' => 'C',
        'linen paper' => 'C',
        'embossed paper' => 'C',
        'lamination base paper' => 'C',
        'tissue base paper' => 'C',

        // ---- Band D (specialty, security, luxury, industrial) ----
        'bible paper' => 'D',
        'ncr paper' => 'D',
        'carbonless paper' => 'D',
        'solid bleached sulphate (sbs)' => 'D',
        'solid unbleached sulphate (sus)' => 'D',
        'thermal paper' => 'D',
        'ogr paper (oil & grease resistant)' => 'D',
        'butter paper' => 'D',
        'wax paper' => 'D',
        'silicone paper' => 'D',
        'release paper' => 'D',
        'parchment paper' => 'D',
        'filter paper' => 'D',
        'security paper' => 'D',
        'label stock paper' => 'D',
        'fluorescent paper' => 'D',
        'metallic paper' => 'D',
        'pearlised paper' => 'D',
        'textured paper' => 'D',
        'handmade paper' => 'D',
        'imported fancy paper' => 'D',
        'insulation paper' => 'D',
        'electrical paper' => 'D',
        'cigarette paper' => 'D',
        'straw paper' => 'D',
    ],
];
