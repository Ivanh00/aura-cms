@php
    use Aura\Base\TransformColor;

    $settings = \Aura\Base\ThemeTokens::resolve(
        isset($settings) && is_array($settings) ? $settings : [],
    );
    $fontFamily = \Aura\Base\ThemeTokens::fontFamily($settings);
    $lightColors = \Aura\Base\ThemeTokens::colors($settings, 'light');
    $darkColors = \Aura\Base\ThemeTokens::colors($settings, 'dark');
    $designTokens = \Aura\Base\ThemeTokens::design($settings);
    $lightSidebarColors = \Aura\Base\ThemeTokens::sidebarColors($settings, 'light');
    $darkSidebarColors = \Aura\Base\ThemeTokens::sidebarColors($settings, 'dark');
    $colorScope = $colorScope ?? ':root';
    $darkColorScope = $darkColorScope ?? '.dark';
    $includeColorHelpers = $includeColorHelpers ?? true;

    $colorShades = ['25', '50', '100', '200', '300', '400', '500', '600', '700', '800', '900', '950'];
    $grayShades = ['25', '50', '100', '200', '300', '400', '500', '600', '700', '800', '900', '950'];

    $defaultVariables = [
        '--sidebar-bg' => 'var(--primary-600)',
        '--sidebar-bg-hover' => 'var(--primary-500)',
        '--sidebar-bg-dropdown' => 'var(--primary-700)',
        '--sidebar-text' => 'var(--primary-400)',
        '--sidebar-icon' => 'var(--primary-300)',
        '--sidebar-icon-hover' => 'var(--primary-200)',
    ];

    $colorPalettes = [
        'aura' => [
            'colors' => [
                'primary-25' => TransformColor::hexToRgb('#fbfeff'),
                'primary-50' => TransformColor::hexToRgb('#f0f4fe'),
                'primary-100' => TransformColor::hexToRgb('#e0eafd'),
                'primary-200' => TransformColor::hexToRgb('#c2d5fb'),
                'primary-300' => TransformColor::hexToRgb('#95b5f9'),
                'primary-400' => TransformColor::hexToRgb('#6894f6'),
                'primary-500' => TransformColor::hexToRgb('#3c73f2'),
                'primary-600' => TransformColor::hexToRgb('#1f55e9'),
                'primary-700' => TransformColor::hexToRgb('#1643d7'),
                'primary-800' => TransformColor::hexToRgb('#1236b9'),
                'primary-900' => TransformColor::hexToRgb('#0e2a96'),
                'primary-950' => TransformColor::hexToRgb('#081c6b'),
            ],
            'variables' => [
                '--sidebar-bg' => 'var(--primary-700)',
                '--sidebar-bg-hover' => 'var(--primary-600)',
                '--sidebar-bg-dropdown' => 'var(--primary-800)',
                '--sidebar-text' => 'var(--primary-400)',
                '--sidebar-icon' => 'var(--primary-300)',
                '--sidebar-icon-hover' => 'var(--primary-200)',
            ],
        ],
        'red' => [
            'colors' => [
                'primary-25' => '254 242 242',
                'primary-50' => '254 226 226',
                'primary-100' => '254 205 205',
                'primary-200' => '252 187 187',
                'primary-300' => '252 165 165',
                'primary-400' => '248 113 113',
                'primary-500' => '239 68 68',
                'primary-600' => '220 38 38',
                'primary-700' => '185 28 28',
                'primary-800' => '153 27 27',
                'primary-900' => '127 29 29',
                'primary-950' => '69 10 10',
            ]
        ],

        'orange' => [
            'colors' => [
                'primary-25' => '255 247 237',
                'primary-50' => '255 237 213',
                'primary-100' => '254 215 170',
                'primary-200' => '253 186 116',
                'primary-300' => '251 146 60',
                'primary-400' => '249 115 22',
                'primary-500' => '234 88 12',
                'primary-600' => '194 65 12',
                'primary-700' => '154 52 18',
                'primary-800' => '124 45 18',
                'primary-900' => '101 43 18',
                'primary-950' => '89 39 14',
            ],
            'variables' => [
                '--sidebar-bg' => 'var(--primary-500)',
                '--sidebar-bg-hover' => 'var(--primary-600)',
                '--sidebar-bg-dropdown' => 'var(--primary-600)',
                '--sidebar-text' => 'var(--primary-200)',
                '--sidebar-icon' => 'var(--primary-200)',
                '--sidebar-icon-hover' => 'var(--primary-100)',
            ],
        ],
        // Continue with other color palettes...
        'amber' => [
            'colors' => [
                'primary-25' => '255 251 235',
                'primary-50' => '254 243 199',
                'primary-100' => '253 230 138',
                'primary-200' => '252 205 77',
                'primary-300' => '251 191 36',
                'primary-400' => '245 158 11',
                'primary-500' => '217 119 6',
                'primary-600' => '180 83 9',
                'primary-700' => '146 64 14',
                'primary-800' => '120 53 15',
                'primary-900' => '99 49 18',
                'primary-950' => '86 42 16',
            ],
            'variables' => [
                '--sidebar-bg' => 'var(--primary-500)',
                '--sidebar-bg-hover' => 'var(--primary-600)',
                '--sidebar-bg-dropdown' => 'var(--primary-600)',
                '--sidebar-text' => 'var(--primary-200)',
                '--sidebar-icon' => 'var(--primary-200)',
                '--sidebar-icon-hover' => 'var(--primary-100)',
            ],
        ],
        'yellow' => [
            'colors' => [
                'primary-25' => '255 254 232',
                'primary-50' => '254 249 195',
                'primary-100' => '254 240 138',
                'primary-200' => '253 208 71',
                'primary-300' => '250 204 21',
                'primary-400' => '234 176 8',
                'primary-500' => '202 138 4',
                'primary-600' => '161 98 7',
                'primary-700' => '133 77 14',
                'primary-800' => '113 63 18',
                'primary-900' => '101 54 16',
                'primary-950' => '93 49 15',
            ],
            'variables' => [
                '--sidebar-bg' => 'var(--primary-500)',
                '--sidebar-bg-hover' => 'var(--primary-600)',
                '--sidebar-bg-dropdown' => 'var(--primary-600)',
                '--sidebar-text' => 'var(--primary-200)',
                '--sidebar-icon' => 'var(--primary-200)',
                '--sidebar-icon-hover' => 'var(--primary-100)',
            ],
        ],
        'lime' => [
            'colors' => [
                'primary-25' => '247 254 231',
                'primary-50' => '236 252 203',
                'primary-100' => '217 249 157',
                'primary-200' => '190 242 100',
                'primary-300' => '163 230 53',
                'primary-400' => '132 204 22',
                'primary-500' => '101 163 13',
                'primary-600' => '77 124 15',
                'primary-700' => '63 98 18',
                'primary-800' => '54 83 20',
                'primary-900' => '47 75 18',
                'primary-950' => '40 67 16',
            ],
            'variables' => [
                '--sidebar-bg' => 'var(--primary-600)',
                '--sidebar-bg-hover' => 'var(--primary-700)',
                '--sidebar-bg-dropdown' => 'var(--primary-700)',
                '--sidebar-text' => 'var(--primary-400)',
                '--sidebar-icon' => 'var(--primary-300)',
                '--sidebar-icon-hover' => 'var(--primary-200)',
            ],
        ],
        'forest-green' => [
            'colors' => [
                'primary-25' => '242 255 244',
                'primary-50' => '230 255 233',
                'primary-100' => '199 245 205',
                'primary-200' => '157 227 167',
                'primary-300' => '115 204 127',
                'primary-400' => '80 178 91',
                'primary-500' => '54 148 63',
                'primary-600' => '38 120 46',
                'primary-700' => '29 95 36',
                'primary-800' => '24 77 30',
                'primary-900' => '20 64 25',
                'primary-950' => '14 48 18',
            ],
        ],
        'green' => [
            'colors' => [
                'primary-25' => '240 253 244',
                'primary-50' => '236 252 240',
                'primary-100' => '220 250 229',
                'primary-200' => '187 247 208',
                'primary-300' => '134 239 172',
                'primary-400' => '74 222 128',
                'primary-500' => '34 197 94',
                'primary-600' => '22 163 74',
                'primary-700' => '21 128 61',
                'primary-800' => '22 101 52',
                'primary-900' => '20 83 45',
                'primary-950' => '5 46 22',
            ],
            'variables' => [
                '--sidebar-bg' => 'var(--primary-600)',
                '--sidebar-bg-hover' => 'var(--primary-700)',
                '--sidebar-text' => 'var(--primary-400)',
            ],
        ],
        'emerald' => [
            'colors' => [
                'primary-25' => '240 253 248',
                'primary-50' => '236 253 245',
                'primary-100' => '209 250 229',
                'primary-200' => '167 243 208',
                'primary-300' => '110 231 183',
                'primary-400' => '52 211 153',
                'primary-500' => '16 185 129',
                'primary-600' => '5 150 105',
                'primary-700' => '4 120 87',
                'primary-800' => '6 95 70',
                'primary-900' => '6 78 59',
                'primary-950' => '2 44 34',
            ],
            'variables' => [
                '--sidebar-bg' => 'var(--primary-600)',
                '--sidebar-bg-hover' => 'var(--primary-700)',
                '--sidebar-bg-dropdown' => 'var(--primary-700)',
                '--sidebar-text' => 'var(--primary-400)',
                '--sidebar-icon' => 'var(--primary-300)',
                '--sidebar-icon-hover' => 'var(--primary-200)',
            ],
        ],
        'mountain-meadow' => [
            'colors' => [
                'primary-25' => '240 253 250',
                'primary-50' => '224 251 246',
                'primary-100' => '204 248 240',
                'primary-200' => '153 241 223',
                'primary-300' => '94 228 204',
                'primary-400' => '45 212 182',
                'primary-500' => '18 186 159',
                'primary-600' => '13 162 139',
                'primary-700' => '15 135 116',
                'primary-800' => '17 108 93',
                'primary-900' => '20 84 72',
                'primary-950' => '17 65 56',
            ],
            'variables' => [
                '--sidebar-bg' => 'var(--primary-600)',
                '--sidebar-bg-hover' => 'var(--primary-500)',
                '--sidebar-bg-dropdown' => 'var(--primary-700)',
                '--sidebar-text' => 'var(--primary-300)',
                '--sidebar-icon' => 'var(--primary-100)',
                '--sidebar-icon-hover' => 'var(--primary-50)',
            ],
        ],
        'teal' => [
            'colors' => [
                'primary-25' => '240 253 250',
                'primary-50' => '204 251 241',
                'primary-100' => '153 246 228',
                'primary-200' => '94 234 212',
                'primary-300' => '45 212 191',
                'primary-400' => '20 184 166',
                'primary-500' => '13 148 136',
                'primary-600' => '15 118 110',
                'primary-700' => '17 94 89',
                'primary-800' => '19 78 74',
                'primary-900' => '17 63 61',
                'primary-950' => '10 41 40',
            ],
            'variables' => [
                '--sidebar-bg' => 'var(--primary-600)',
                '--sidebar-bg-hover' => 'var(--primary-700)',
                '--sidebar-bg-dropdown' => 'var(--primary-700)',
                '--sidebar-text' => 'var(--primary-400)',
                '--sidebar-icon' => 'var(--primary-300)',
                '--sidebar-icon-hover' => 'var(--primary-200)',
            ],
        ],
        'ocean-breeze' => [
            'colors' => [
                'primary-25' => '236 254 255',
                'primary-50' => '224 247 250',
                'primary-100' => '198 237 242',
                'primary-200' => '165 224 233',
                'primary-300' => '125 206 219',
                'primary-400' => '87 181 199',
                'primary-500' => '54 154 177',
                'primary-600' => '34 131 156',
                'primary-700' => '22 107 133',
                'primary-800' => '15 85 110',
                'primary-900' => '11 70 94',
                'primary-950' => '7 54 75',
            ],
            'variables' => [
                '--sidebar-bg' => 'var(--primary-700)',
                '--sidebar-bg-hover' => 'var(--primary-600)',
                '--sidebar-bg-dropdown' => 'var(--primary-800)',
                '--sidebar-text' => 'var(--primary-200)',
                '--sidebar-icon' => 'var(--primary-100)',
                '--sidebar-icon-hover' => 'var(--primary-50)',
            ],
        ],
        'cyan' => [
            'colors' => [
                'primary-25' => '236 254 255',
                'primary-50' => '207 250 254',
                'primary-100' => '165 243 252',
                'primary-200' => '103 232 249',
                'primary-300' => '34 211 238',
                'primary-400' => '6 182 212',
                'primary-500' => '8 145 178',
                'primary-600' => '14 116 144',
                'primary-700' => '21 94 117',
                'primary-800' => '22 78 99',
                'primary-900' => '22 65 87',
                'primary-950' => '15 45 60',
            ],
            'variables' => [
                '--sidebar-bg' => 'var(--primary-600)',
                '--sidebar-bg-hover' => 'var(--primary-500)',
                '--sidebar-bg-dropdown' => 'var(--primary-700)',
                '--sidebar-text' => 'var(--primary-300)',
                '--sidebar-icon' => 'var(--primary-200)',
                '--sidebar-icon-hover' => 'var(--primary-100)',
            ],
        ],
        'sky' => [
            'colors' => [
                'primary-25' => '240 249 255',
                'primary-50' => '224 242 254',
                'primary-100' => '186 230 253',
                'primary-200' => '125 211 252',
                'primary-300' => '56 189 248',
                'primary-400' => '14 165 233',
                'primary-500' => '2 132 199',
                'primary-600' => '3 105 161',
                'primary-700' => '7 89 133',
                'primary-800' => '12 74 110',
                'primary-900' => '8 47 73',
                'primary-950' => '5 30 52',
            ],
            'variables' => [
                '--sidebar-bg' => 'var(--primary-600)',
                '--sidebar-bg-hover' => 'var(--primary-500)',
                '--sidebar-bg-dropdown' => 'var(--primary-700)',
                '--sidebar-text' => 'var(--primary-300)',
                '--sidebar-icon' => 'var(--primary-200)',
                '--sidebar-icon-hover' => 'var(--primary-100)',
            ],
        ],
        'blue' => [
            'colors' => [
                'primary-25' => '244 251 255', // Lighter shade added
                'primary-50' => '239 246 255',
                'primary-100' => '219 234 254',
                'primary-200' => '191 219 254',
                'primary-300' => '147 197 253',
                'primary-400' => '96 165 250',
                'primary-500' => '59 130 246',
                'primary-600' => '37 99 235',
                'primary-700' => '29 78 216',
                'primary-800' => '30 64 175',
                'primary-900' => '30 58 138',
                'primary-950' => '23 37 84',
            ],
        ],
        'indigo' => [
            'colors' => [
                'primary-25' => '240 239 255',
                'primary-50' => '224 231 255',
                'primary-100' => '199 210 254',
                'primary-200' => '165 180 252',
                'primary-300' => '129 140 248',
                'primary-400' => '99 102 241',
                'primary-500' => '79 70 229',
                'primary-600' => '67 56 202',
                'primary-700' => '55 48 163',
                'primary-800' => '49 46 129',
                'primary-900' => '39 44 97',
                'primary-950' => '30 27 75',
            ],
        ],
        'violet' => [
            'colors' => [
                'primary-25' => '250 245 255',
                'primary-50' => '245 243 255',
                'primary-100' => '237 233 254',
                'primary-200' => '221 214 254',
                'primary-300' => '196 180 253',
                'primary-400' => '167 139 250',
                'primary-500' => '139 92 246',
                'primary-600' => '124 62 237',
                'primary-700' => '109 40 217',
                'primary-800' => '91 33 182',
                'primary-900' => '76 29 149',
                'primary-950' => '63 24 95',
            ],
            'variables' => [
                '--sidebar-bg' => 'var(--primary-600)',
                '--sidebar-bg-hover' => 'var(--primary-500)',
                '--sidebar-bg-dropdown' => 'var(--primary-700)',
                '--sidebar-text' => 'var(--primary-300)',
                '--sidebar-icon' => 'var(--primary-200)',
                '--sidebar-icon-hover' => 'var(--primary-100)',
            ],
        ],
        'purple' => [
            'colors' => [
                'primary-25' => '250 245 255',
                'primary-50' => '243 232 255',
                'primary-100' => '233 213 255',
                'primary-200' => '216 180 254',
                'primary-300' => '192 132 252',
                'primary-400' => '168 85 247',
                'primary-500' => '147 51 234',
                'primary-600' => '126 34 206',
                'primary-700' => '107 33 168',
                'primary-800' => '88 28 135',
                'primary-900' => '72 24 103',
                'primary-950' => '59 20 84',
            ],
            'variables' => [
                '--sidebar-bg' => 'var(--primary-600)',
                '--sidebar-bg-hover' => 'var(--primary-500)',
                '--sidebar-bg-dropdown' => 'var(--primary-700)',
                '--sidebar-text' => 'var(--primary-300)',
                '--sidebar-icon' => 'var(--primary-200)',
                '--sidebar-icon-hover' => 'var(--primary-100)',
            ],
        ],
        'fuchsia' => [
            'colors' => [
                'primary-25' => '250 244 255',
                'primary-50' => '250 232 255',
                'primary-100' => '245 208 254',
                'primary-200' => '240 171 252',
                'primary-300' => '232 121 249',
                'primary-400' => '217 70 239',
                'primary-500' => '192 38 211',
                'primary-600' => '162 28 175',
                'primary-700' => '134 25 143',
                'primary-800' => '112 26 117',
                'primary-900' => '99 25 99',
                'primary-950' => '74 19 74',
            ],
            'variables' => [
                '--sidebar-bg' => 'var(--primary-600)',
                '--sidebar-bg-hover' => 'var(--primary-500)',
                '--sidebar-bg-dropdown' => 'var(--primary-700)',
                '--sidebar-text' => 'var(--primary-300)',
                '--sidebar-icon' => 'var(--primary-200)',
                '--sidebar-icon-hover' => 'var(--primary-100)',
            ],
        ],
        'pink' => [
            'colors' => [
                'primary-25' => '255 242 248',
                'primary-50' => '254 231 243',
                'primary-100' => '252 207 232',
                'primary-200' => '249 168 212',
                'primary-300' => '244 114 182',
                'primary-400' => '236 72 153',
                'primary-500' => '219 39 119',
                'primary-600' => '190 24 93',
                'primary-700' => '157 24 77',
                'primary-800' => '131 24 67',
                'primary-900' => '107 26 61',
                'primary-950' => '80 22 49',
            ],
            'variables' => [
                '--sidebar-bg' => 'var(--primary-600)',
                '--sidebar-bg-hover' => 'var(--primary-500)',
                '--sidebar-bg-dropdown' => 'var(--primary-700)',
                '--sidebar-text' => 'var(--primary-300)',
                '--sidebar-icon' => 'var(--primary-200)',
                '--sidebar-icon-hover' => 'var(--primary-100)',
            ],
        ],
        'rose' => [
            'colors' => [
                'primary-25' => '255 241 242',
                'primary-50' => '254 228 230',
                'primary-100' => '252 205 211',
                'primary-200' => '249 164 175',
                'primary-300' => '246 123 141',
                'primary-400' => '241 82 107',
                'primary-500' => '233 41 73',
                'primary-600' => '212 23 59',
                'primary-700' => '180 18 52',
                'primary-800' => '147 17 47',
                'primary-900' => '119 22 45',
                'primary-950' => '90 17 37',
            ],
            'variables' => [
                '--sidebar-bg' => 'var(--primary-600)',
                '--sidebar-bg-hover' => 'var(--primary-500)',
                '--sidebar-bg-dropdown' => 'var(--primary-700)',
                '--sidebar-text' => 'var(--primary-300)',
                '--sidebar-icon' => 'var(--primary-200)',
                '--sidebar-icon-hover' => 'var(--primary-100)',
            ],
        ],


        'sandal' => [
            'colors' => [
                'primary-25' => '252 251 249',
                'primary-50' => '250 249 245',
                'primary-100' => '245 243 236',
                'primary-200' => '235 231 218',
                'primary-300' => '220 213 193',
                'primary-400' => '202 191 163',
                'primary-500' => '181 166 131',
                'primary-600' => '157 139 102',
                'primary-700' => '131 113 79',
                'primary-800' => '104 88 61',
                'primary-900' => '80 66 46',
                'primary-950' => '59 48 33',
            ],
        ],
        'desert-sand' => [
            'colors' => [
                'primary-25' => '254 252 248',
                'primary-50' => '252 248 240',
                'primary-100' => '248 238 224',
                'primary-200' => '241 223 199',
                'primary-300' => '231 203 167',
                'primary-400' => '217 179 136',
                'primary-500' => '198 151 106',
                'primary-600' => '174 124 82',
                'primary-700' => '147 98 63',
                'primary-800' => '121 77 48',
                'primary-900' => '100 63 39',
                'primary-950' => '84 50 31',
            ],
        ],
        'salmon' => [
            'colors' => [
                'primary-25' => '255 249 248',
                'primary-50' => '254 243 242',
                'primary-100' => '253 232 229',
                'primary-200' => '250 215 210',
                'primary-300' => '245 190 182',
                'primary-400' => '236 157 146',
                'primary-500' => '224 123 109',
                'primary-600' => '206 92 76',
                'primary-700' => '179 69 52',
                'primary-800' => '147 55 41',
                'primary-900' => '121 43 32',
                'primary-950' => '95 32 23',
            ],
        ],

        'autumn-rust' => [
            'colors' => [
                'primary-25' => '254 247 242',
                'primary-50' => '253 240 233',
                'primary-100' => '251 224 209',
                'primary-200' => '247 199 173',
                'primary-300' => '241 166 127',
                'primary-400' => '232 128 82',
                'primary-500' => '217 91 46',
                'primary-600' => '186 68 28',
                'primary-700' => '154 52 22',
                'primary-800' => '127 43 21',
                'primary-900' => '106 37 20',
                'primary-950' => '87 30 17',
            ],
        ],


        'slate' => [
            'colors' => [
                'primary-25' => '252 252 253',
                'primary-50' => '249 250 251',
                'primary-100' => '243 244 246',
                'primary-200' => '229 231 235',
                'primary-300' => '209 213 219',
                'primary-400' => '156 163 175',
                'primary-500' => '107 114 128',
                'primary-600' => '75 85 99',
                'primary-700' => '51 65 85',
                'primary-800' => '30 41 59',
                'primary-900' => '15 23 42',
                'primary-950' => '2 6 23',
            ],
        ],
        'dark-slate' => [
            'colors' => [
                'primary-25' => '251 251 252',
                'primary-50' => '245 246 247',
                'primary-100' => '239 240 242',
                'primary-200' => '226 228 232',
                'primary-300' => '201 205 211',
                'primary-400' => '148 155 167',
                'primary-500' => '93 100 114',
                'primary-600' => '61 71 85',
                'primary-700' => '40 53 72',
                'primary-800' => '20 30 47',
                'primary-900' => '10 17 34',
                'primary-950' => '1 4 19',
            ],
        ],
        'blackout' => [
            'colors' => [
                'primary-25' => '252 252 253',
                'primary-50' => '249 250 251',
                'primary-100' => '243 244 246',
                'primary-200' => '229 231 235',
                'primary-300' => '209 213 219',
                'primary-400' => '156 163 175',
                'primary-500' => '75 85 99',
                'primary-600' => '55 65 81',
                'primary-700' => '17 24 39',
                'primary-800' => '0 0 0',
                'primary-900' => '0 0 0',
                'primary-950' => '0 0 0',
            ],
        ],
        'obsidian' => [
            'colors' => [
                'primary-25' => '246 245 247',
                'primary-50' => '236 235 238',
                'primary-100' => '221 220 223',
                'primary-200' => '201 200 204',
                'primary-300' => '171 170 175',
                'primary-400' => '131 130 136',
                'primary-500' => '91 90 97',
                'primary-600' => '61 60 67',
                'primary-700' => '41 40 47',
                'primary-800' => '26 25 32',
                'primary-900' => '16 15 22',
                'primary-950' => '11 10 17',
            ],
        ],

        'amethyst' => [
            'colors' => [
                'primary-25' => '252 251 253',
                'primary-50' => '249 248 251',
                'primary-100' => '243 241 246',
                'primary-200' => '229 226 235',
                'primary-300' => '209 203 219',
                'primary-400' => '156 148 175',
                'primary-500' => '107 100 128',
                'primary-600' => '75 71 99',
                'primary-700' => '51 51 85',
                'primary-800' => '30 30 59',
                'primary-900' => '15 15 42',
                'primary-950' => '2 2 23',
            ],
        ],
        'opal' => [
            'colors' => [
                'primary-25' => '252 253 254',
                'primary-50' => '248 250 252',
                'primary-100' => '241 245 249',
                'primary-200' => '226 232 240',
                'primary-300' => '203 213 225',
                'primary-400' => '148 163 184',
                'primary-500' => '100 116 139',
                'primary-600' => '71 85 105',
                'primary-700' => '51 65 85',
                'primary-800' => '30 41 59',
                'primary-900' => '15 23 42',
                'primary-950' => '8 15 32',
            ],
        ],

        'gray' => [
            'colors' => [
                'primary-25' => '250 250 250',
                'primary-50' => '249 250 251',
                'primary-100' => '243 244 246',
                'primary-200' => '229 231 235',
                'primary-300' => '209 213 219',
                'primary-400' => '156 163 175',
                'primary-500' => '107 114 128',
                'primary-600' => '75 85 99',
                'primary-700' => '55 65 81',
                'primary-800' => '31 41 55',
                'primary-900' => '17 24 39',
                'primary-950' => '8 15 29',
            ],
        ],
        'zinc' => [
            'colors' => [
                'primary-25' => '249 250 251',
                'primary-50' => '250 250 250',
                'primary-100' => '244 244 245',
                'primary-200' => '228 228 231',
                'primary-300' => '212 212 216',
                'primary-400' => '161 161 170',
                'primary-500' => '113 113 122',
                'primary-600' => '82 82 91',
                'primary-700' => '63 63 70',
                'primary-800' => '39 39 42',
                'primary-900' => '24 24 27',
                'primary-950' => '14 14 17',
            ],
        ],
        'neutral' => [
            'colors' => [
                'primary-25' => '253 253 253',
                'primary-50' => '250 250 250',
                'primary-100' => '245 245 245',
                'primary-200' => '229 229 229',
                'primary-300' => '212 212 212',
                'primary-400' => '163 163 163',
                'primary-500' => '115 115 115',
                'primary-600' => '82 82 82',
                'primary-700' => '64 64 64',
                'primary-800' => '38 38 38',
                'primary-900' => '23 23 23',
                'primary-950' => '13 13 13',
            ],
        ],
        'stone' => [
            'colors' => [
                'primary-25' => '253 253 252',
                'primary-50' => '250 250 249',
                'primary-100' => '245 245 244',
                'primary-200' => '231 229 228',
                'primary-300' => '214 211 209',
                'primary-400' => '168 162 158',
                'primary-500' => '120 113 108',
                'primary-600' => '87 83 78',
                'primary-700' => '68 64 60',
                'primary-800' => '41 37 36',
                'primary-900' => '28 25 23',
                'primary-950' => '12 10 9',
            ],
        ],
        'sandstone' => [
            'colors' => [
                'primary-25' => '252 251 250',
                'primary-50' => '249 247 245',
                'primary-100' => '243 239 235',
                'primary-200' => '229 221 215',
                'primary-300' => '209 197 188',
                'primary-400' => '156 140 128',
                'primary-500' => '107 91 79',
                'primary-600' => '75 62 52',
                'primary-700' => '51 40 32',
                'primary-800' => '30 22 16',
                'primary-900' => '15 10 6',
                'primary-950' => '2 1 0',
            ],
        ],
        'rose-quartz' => [
            'colors' => [
                'primary-25' => '252 250 250',
                'primary-50' => '249 245 245',
                'primary-100' => '243 236 237',
                'primary-200' => '229 219 221',
                'primary-300' => '209 196 199',
                'primary-400' => '156 140 144',
                'primary-500' => '107 95 99',
                'primary-600' => '75 66 70',
                'primary-700' => '51 45 49',
                'primary-800' => '30 27 30',
                'primary-900' => '15 13 15',
                'primary-950' => '2 1 2',
            ],
        ],


        'olive' => [
            'colors' => [
                'primary-25' => '251 252 249',
                'primary-50' => '247 249 243',
                'primary-100' => '239 242 233',
                'primary-200' => '226 231 217',
                'primary-300' => '208 215 195',
                'primary-400' => '177 186 160',
                'primary-500' => '143 153 124',
                'primary-600' => '94 103 82',
                'primary-700' => '65 75 55',
                'primary-800' => '48 56 40',
                'primary-900' => '30 32 20',
                'primary-950' => '14 16 10',
            ],
        ],

        'smaragd' => [
            'colors' => [
                'primary-25' => '252 255 253',
                'primary-50' => '242 247 243',
                'primary-100' => '223 236 225',
                'primary-200' => '194 214 198',
                'primary-300' => '158 183 166',
                'primary-400' => '134 156 141',
                'primary-500' => '86 113 95',
                'primary-600' => '66 87 74',
                'primary-700' => '50 63 55',
                'primary-800' => '40 48 45',
                'primary-900' => '30 32 31',
                'primary-950' => '19 20 20',
            ],
        ],


    ];

    $grayColorPalettes = [
        'slate' => [
            'colors' => [
                'gray-25' => '252 252 253',
                'gray-50' => '249 250 251',
                'gray-100' => '243 244 246',
                'gray-200' => '229 231 235',
                'gray-300' => '209 213 219',
                'gray-400' => '156 163 175',
                'gray-500' => '107 114 128',
                'gray-600' => '75 85 99',
                'gray-700' => '51 65 85',
                'gray-800' => '30 41 59',
                'gray-900' => '15 23 42',
                'gray-950' => '2 6 23',
            ],
        ],
        'dark-slate' => [
            'colors' => [
                'gray-25' => '251 251 252',
                'gray-50' => '245 246 247',
                'gray-100' => '239 240 242',
                'gray-200' => '226 228 232',
                'gray-300' => '201 205 211',
                'gray-400' => '148 155 167',
                'gray-500' => '93 100 114',
                'gray-600' => '61 71 85',
                'gray-700' => '40 53 72',
                'gray-800' => '20 30 47',
                'gray-900' => '10 17 34',
                'gray-950' => '1 4 19',
            ],
        ],
        'blackout' => [
            'colors' => [
                'gray-25' => '252 252 253',
                'gray-50' => '249 250 251',
                'gray-100' => '243 244 246',
                'gray-200' => '229 231 235',
                'gray-300' => '209 213 219',
                'gray-400' => '156 163 175',
                'gray-500' => '75 85 99',
                'gray-600' => '55 65 81',
                'gray-700' => '17 24 39',
                'gray-800' => '0 0 0',
                'gray-900' => '0 0 0',
                'gray-950' => '0 0 0',
            ],
        ],
        'obsidian' => [
            'colors' => [
                'gray-25' => '246 245 247',
                'gray-50' => '236 235 238',
                'gray-100' => '221 220 223',
                'gray-200' => '201 200 204',
                'gray-300' => '171 170 175',
                'gray-400' => '131 130 136',
                'gray-500' => '91 90 97',
                'gray-600' => '61 60 67',
                'gray-700' => '41 40 47',
                'gray-800' => '26 25 32',
                'gray-900' => '16 15 22',
                'gray-950' => '11 10 17',
            ],
        ],

        'amethyst' => [
            'colors' => [
                'gray-25' => '252 251 253',
                'gray-50' => '249 248 251',
                'gray-100' => '243 241 246',
                'gray-200' => '229 226 235',
                'gray-300' => '209 203 219',
                'gray-400' => '156 148 175',
                'gray-500' => '107 100 128',
                'gray-600' => '75 71 99',
                'gray-700' => '51 51 85',
                'gray-800' => '30 30 59',
                'gray-900' => '15 15 42',
                'gray-950' => '2 2 23',
            ],
        ],
        'opal' => [
            'colors' => [
                'gray-25' => '252 253 254',
                'gray-50' => '248 250 252',
                'gray-100' => '241 245 249',
                'gray-200' => '226 232 240',
                'gray-300' => '203 213 225',
                'gray-400' => '148 163 184',
                'gray-500' => '100 116 139',
                'gray-600' => '71 85 105',
                'gray-700' => '51 65 85',
                'gray-800' => '30 41 59',
                'gray-900' => '15 23 42',
                'gray-950' => '8 15 32',
            ],
        ],

        'gray' => [
            'colors' => [
                'gray-25' => '250 250 250',
                'gray-50' => '249 250 251',
                'gray-100' => '243 244 246',
                'gray-200' => '229 231 235',
                'gray-300' => '209 213 219',
                'gray-400' => '156 163 175',
                'gray-500' => '107 114 128',
                'gray-600' => '75 85 99',
                'gray-700' => '55 65 81',
                'gray-800' => '31 41 55',
                'gray-900' => '17 24 39',
                'gray-950' => '8 15 29',
            ],
        ],
        'zinc' => [
            'colors' => [
                'gray-25' => '249 250 251',
                'gray-50' => '250 250 250',
                'gray-100' => '244 244 245',
                'gray-200' => '228 228 231',
                'gray-300' => '212 212 216',
                'gray-400' => '161 161 170',
                'gray-500' => '113 113 122',
                'gray-600' => '82 82 91',
                'gray-700' => '63 63 70',
                'gray-800' => '39 39 42',
                'gray-900' => '24 24 27',
                'gray-950' => '14 14 17',
            ],
        ],
        'neutral' => [
            'colors' => [
                'gray-25' => '253 253 253',
                'gray-50' => '250 250 250',
                'gray-100' => '245 245 245',
                'gray-200' => '229 229 229',
                'gray-300' => '212 212 212',
                'gray-400' => '163 163 163',
                'gray-500' => '115 115 115',
                'gray-600' => '82 82 82',
                'gray-700' => '64 64 64',
                'gray-800' => '38 38 38',
                'gray-900' => '23 23 23',
                'gray-950' => '13 13 13',
            ],
        ],
        'stone' => [
            'colors' => [
                'gray-25' => '253 253 252',
                'gray-50' => '250 250 249',
                'gray-100' => '245 245 244',
                'gray-200' => '231 229 228',
                'gray-300' => '214 211 209',
                'gray-400' => '168 162 158',
                'gray-500' => '120 113 108',
                'gray-600' => '87 83 78',
                'gray-700' => '68 64 60',
                'gray-800' => '41 37 36',
                'gray-900' => '28 25 23',
                'gray-950' => '12 10 9',
            ],
        ],
        'sandstone' => [
            'colors' => [
                'gray-25' => '252 251 250',
                'gray-50' => '249 247 245',
                'gray-100' => '243 239 235',
                'gray-200' => '229 221 215',
                'gray-300' => '209 197 188',
                'gray-400' => '156 140 128',
                'gray-500' => '107 91 79',
                'gray-600' => '75 62 52',
                'gray-700' => '51 40 32',
                'gray-800' => '30 22 16',
                'gray-900' => '15 10 6',
                'gray-950' => '2 1 0',
            ],
        ],
        'rose-quartz' => [
            'colors' => [
                'gray-25' => '252 250 250',
                'gray-50' => '249 245 245',
                'gray-100' => '243 236 237',
                'gray-200' => '229 219 221',
                'gray-300' => '209 196 199',
                'gray-400' => '156 140 144',
                'gray-500' => '107 95 99',
                'gray-600' => '75 66 70',
                'gray-700' => '51 45 49',
                'gray-800' => '30 27 30',
                'gray-900' => '15 13 15',
                'gray-950' => '2 1 2',
            ],
        ],


        'olive' => [
            'colors' => [
                'gray-25' => '251 252 249',
                'gray-50' => '247 249 243',
                'gray-100' => '239 242 233',
                'gray-200' => '226 231 217',
                'gray-300' => '208 215 195',
                'gray-400' => '177 186 160',
                'gray-500' => '143 153 124',
                'gray-600' => '94 103 82',
                'gray-700' => '65 75 55',
                'gray-800' => '48 56 40',
                'gray-900' => '30 32 20',
                'gray-950' => '14 16 10',
            ],
        ],

        'smaragd' => [
            'colors' => [
                'gray-25' => '252 255 253',
                'gray-50' => '242 247 243',
                'gray-100' => '223 236 225',
                'gray-200' => '194 214 198',
                'gray-300' => '158 183 166',
                'gray-400' => '134 156 141',
                'gray-500' => '86 113 95',
                'gray-600' => '66 87 74',
                'gray-700' => '50 63 55',
                'gray-800' => '40 48 45',
                'gray-900' => '30 32 31',
                'gray-950' => '19 20 20',
            ],
        ],

    ];

    $selectedPalette = $settings['color-palette'] ?? 'aura';
    $selectedGrayPalette = $settings['gray-color-palette'] ?? 'slate';


    if ($selectedPalette === 'custom') {
        $colors = [];
        foreach ($colorShades as $shade) {
            $key = 'primary-' . $shade;
            $colors[$key] = isset($settings[$key]) ? TransformColor::hexToRgb($settings[$key]) : 'default value';
        }
        $variables = $defaultVariables;
    } else {
        $palette = $colorPalettes[$selectedPalette] ?? $colorPalettes['aura'];
        $colors = $palette['colors'];
        $variables = array_merge($defaultVariables, $palette['variables'] ?? []);
    }

    if ($selectedGrayPalette === 'custom') {
        $grayColors = [];
        foreach ($grayShades as $shade) {
            $key = 'gray-' . $shade;
            $grayColors[$key] = isset($settings[$key]) ? TransformColor::hexToRgb($settings[$key]) : 'default value';
        }
    } else {
        $grayPalette = $grayColorPalettes[$selectedGrayPalette] ?? $grayColorPalettes['slate'];
        $grayColors = $grayPalette['colors'];
    }
@endphp

<style>
    {!! $colorScope !!} {
        --aura-font-sans: {!! $fontFamily !!};

        @foreach ($designTokens as $key => $value)
            {{ $key }}: {{ $value }};
        @endforeach

        @foreach ($lightColors as $key => $value)
            --aura-color-{{ $key }}: {{ $value }};
        @endforeach

        @foreach ($colors as $key => $value)
            --{{ $key }}: {{ $value }};
        @endforeach

        @foreach ($variables as $key => $value)
            {{ $key }}: {{ $value }};
        @endforeach

        @foreach ($lightSidebarColors as $key => $value)
            {{ $key }}: {{ $value }};
        @endforeach
    }

    {!! $colorScope !!} {
        @foreach ($grayColors as $key => $value)
            --{{ $key }}: {{ $value }};
        @endforeach
    }

    {!! $darkColorScope !!} {
        @foreach ($darkColors as $key => $value)
            --aura-color-{{ $key }}: {{ $value }};
        @endforeach

        @foreach ($darkSidebarColors as $key => $value)
            {{ $key }}: {{ $value }};
        @endforeach
    }
</style>

@if($includeColorHelpers)
<script>
    function getCssVariableValue(variableName) {
        var rgb = getComputedStyle(document.documentElement).getPropertyValue(variableName);
        rgb = rgb.trim().split(" ");
        return rgbToHex(parseInt(rgb[0]), parseInt(rgb[1]), parseInt(rgb[2]));
    }

    function rgbToHex(r, g, b) {
        return "#" + ((1 << 24) + (r << 16) + (g << 8) + (+b)).toString(16).slice(1);
    }

    @if(optional($settings)['darkmode-type'] == 'dark')
    document.documentElement.classList.add('dark')
    @elseif (optional($settings)['darkmode-type'] == 'light')
    document.documentElement.classList.remove('dark')
    document.documentElement.classList.add('light')
    @else
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        document.documentElement.classList.add('dark')
    }
    @endif
</script>
@endif
