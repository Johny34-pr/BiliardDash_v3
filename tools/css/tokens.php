<?php

declare(strict_types=1);

/**
 * Design tokenek a stíluslap generátorhoz
 * =============================================================================
 *
 * Ez a fájl tartalmazza a weboldal színskáláit, méretlépcsőit és egyéb
 * alapértékeit. Korábban ugyanezek egy JavaScript konfigurációban éltek, és a
 * böngésző dolgozta fel őket; most a tools/build-css.php olvassa be, és
 * ebből állítja elő a public/assets/css/tailwind.css fájlt.
 *
 * Új színt vagy méretet itt kell felvenni, majd újra kell futtatni:
 *
 *     php tools/build-css.php
 */

return [
    /*
     * A tartalomsáv törésponthoz tartozó felső korlátai. Nélkülük a
     * container a legnagyobb töréspontnál túl szélesre nyílna, amitől a
     * fejléc két széle túl messze kerülne egymástól.
     */
    'screens' => [
        'sm' => '640px',
        'md' => '768px',
        'lg' => '1024px',
        'xl' => '1180px',
        '2xl' => '1180px',
    ],

    'colors' => [
        'inherit' => 'inherit',
        'current' => 'currentColor',
        'transparent' => 'transparent',
        'black' => '#000',
        'white' => '#fff',

        /* Biliárdposztó zöld - hűvös, mély, telt tónusskála */
        'billiard-green' => [
            '50' => '#f0faf5',
            '100' => '#daf3e5',
            '200' => '#b7e6cd',
            '300' => '#86d1ac',
            '400' => '#4eb586',
            '500' => '#299868',
            '600' => '#1a7a53',
            '700' => '#166145',
            '800' => '#154d38',
            '900' => '#0c2f22',
            '950' => '#061a13',
        ],

        /* Meleg sárgaréz arany - akcentus szín */
        'billiard-gold' => [
            '50' => '#fdfaef',
            '100' => '#faf1d3',
            '200' => '#f4e0a4',
            '300' => '#eccb70',
            '400' => '#e5b544',
            '500' => '#d99a26',
            '600' => '#bf761d',
            '700' => '#9e561b',
            '800' => '#81441c',
            '900' => '#6b391a',
        ],

        /* Meleg semleges alapszínek - kevésbé steril, mint a szürke */
        'sand' => [
            '50' => '#fbfaf8',
            '100' => '#f5f3ef',
            '200' => '#e9e5dd',
            '300' => '#d7d1c5',
            '400' => '#b3aa9a',
            '500' => '#8c8272',
            '600' => '#6b6254',
            '700' => '#524b40',
            '800' => '#38332c',
            '900' => '#1f1c18',
        ],

        /* Hibajelzésekhez: a szabványos vörös skála */
        'red' => [
            '50' => '#fef2f2',
            '100' => '#fee2e2',
            '200' => '#fecaca',
            '300' => '#fca5a5',
            '400' => '#f87171',
            '500' => '#ef4444',
            '600' => '#dc2626',
            '700' => '#b91c1c',
            '800' => '#991b1b',
            '900' => '#7f1d1d',
        ],
    ],

    /*
     * Méretlépcső: a margók, belső margók, szélességek és rések alapja.
     * A kulcs az osztálynévben szereplő szám (pl. p-4 → '4').
     */
    'spacing' => [
        '0' => '0px',
        'px' => '1px',
        '0.5' => '0.125rem',
        '1' => '0.25rem',
        '1.5' => '0.375rem',
        '2' => '0.5rem',
        '2.5' => '0.625rem',
        '3' => '0.75rem',
        '3.5' => '0.875rem',
        '4' => '1rem',
        '5' => '1.25rem',
        '6' => '1.5rem',
        '7' => '1.75rem',
        '8' => '2rem',
        '9' => '2.25rem',
        '10' => '2.5rem',
        '11' => '2.75rem',
        '12' => '3rem',
        '14' => '3.5rem',
        '16' => '4rem',
        '20' => '5rem',
        '24' => '6rem',
        '28' => '7rem',
        '32' => '8rem',
        '36' => '9rem',
        '40' => '10rem',
        '44' => '11rem',
        '48' => '12rem',
        '52' => '13rem',
        '56' => '14rem',
        '60' => '15rem',
        '64' => '16rem',
        '72' => '18rem',
        '80' => '20rem',
        '96' => '24rem',
    ],

    /** Betűméretek a hozzá tartozó alap sortávolsággal */
    'fontSize' => [
        'xs' => ['0.75rem', '1rem'],
        'sm' => ['0.875rem', '1.25rem'],
        'base' => ['1rem', '1.5rem'],
        'lg' => ['1.125rem', '1.75rem'],
        'xl' => ['1.25rem', '1.75rem'],
        '2xl' => ['1.5rem', '2rem'],
        '3xl' => ['1.875rem', '2.25rem'],
        '4xl' => ['2.25rem', '2.5rem'],
        '5xl' => ['3rem', '1'],
        '6xl' => ['3.75rem', '1'],
        '7xl' => ['4.5rem', '1'],
    ],

    'fontWeight' => [
        'thin' => '100',
        'extralight' => '200',
        'light' => '300',
        'normal' => '400',
        'medium' => '500',
        'semibold' => '600',
        'bold' => '700',
        'extrabold' => '800',
        'black' => '900',
    ],

    'lineHeight' => [
        'none' => '1',
        'tight' => '1.25',
        'snug' => '1.375',
        'normal' => '1.5',
        'relaxed' => '1.625',
        'loose' => '2',
        '3' => '.75rem',
        '4' => '1rem',
        '5' => '1.25rem',
        '6' => '1.5rem',
        '7' => '1.75rem',
        '8' => '2rem',
        '9' => '2.25rem',
        '10' => '2.5rem',
    ],

    'letterSpacing' => [
        'tighter' => '-0.05em',
        'tight' => '-0.025em',
        'tightest' => '-0.035em',
        'normal' => '0em',
        'wide' => '0.025em',
        'wider' => '0.05em',
        'widest' => '0.1em',
    ],

    'borderRadius' => [
        'none' => '0px',
        'sm' => '0.125rem',
        'DEFAULT' => '0.25rem',
        'md' => '0.375rem',
        'lg' => '0.5rem',
        'xl' => '0.75rem',
        '2xl' => '1rem',
        '3xl' => '1.5rem',
        '4xl' => '1.75rem',
        'full' => '9999px',
    ],

    'borderWidth' => [
        'DEFAULT' => '1px',
        '0' => '0px',
        '2' => '2px',
        '4' => '4px',
        '8' => '8px',
    ],

    /*
     * Árnyékok. A soft/lift/inset-line saját érték, a többi szabványos.
     * Az érték párban áll: [alap, színezhető változat] - a második a
     * shadow-<szín> segédosztályokhoz kell.
     */
    'boxShadow' => [
        'sm' => ['0 1px 2px 0 rgba(0,0,0,.05)', '0 1px 2px 0 var(--tw-shadow-color)'],
        'DEFAULT' => [
            '0 1px 3px 0 rgba(0,0,0,.1),0 1px 2px -1px rgba(0,0,0,.1)',
            '0 1px 3px 0 var(--tw-shadow-color),0 1px 2px -1px var(--tw-shadow-color)',
        ],
        'md' => [
            '0 4px 6px -1px rgba(0,0,0,.1),0 2px 4px -2px rgba(0,0,0,.1)',
            '0 4px 6px -1px var(--tw-shadow-color),0 2px 4px -2px var(--tw-shadow-color)',
        ],
        'lg' => [
            '0 10px 15px -3px rgba(0,0,0,.1),0 4px 6px -4px rgba(0,0,0,.1)',
            '0 10px 15px -3px var(--tw-shadow-color),0 4px 6px -4px var(--tw-shadow-color)',
        ],
        'xl' => [
            '0 20px 25px -5px rgba(0,0,0,.1),0 8px 10px -6px rgba(0,0,0,.1)',
            '0 20px 25px -5px var(--tw-shadow-color),0 8px 10px -6px var(--tw-shadow-color)',
        ],
        'none' => ['0 0 #0000', '0 0 #0000'],
        'soft' => [
            '0 1px 2px rgba(6,26,19,.04),0 4px 16px -6px rgba(6,26,19,.08)',
            '0 1px 2px var(--tw-shadow-color),0 4px 16px -6px var(--tw-shadow-color)',
        ],
        'lift' => [
            '0 2px 4px rgba(6,26,19,.04),0 16px 32px -12px rgba(6,26,19,.16)',
            '0 2px 4px var(--tw-shadow-color),0 16px 32px -12px var(--tw-shadow-color)',
        ],
        'inset-line' => [
            'inset 0 1px 0 rgba(255,255,255,.06)',
            'inset 0 1px 0 var(--tw-shadow-color)',
        ],
    ],

    'maxWidth' => [
        'none' => 'none',
        '0' => '0rem',
        'xs' => '20rem',
        'sm' => '24rem',
        'md' => '28rem',
        'lg' => '32rem',
        'xl' => '36rem',
        '2xl' => '42rem',
        '3xl' => '48rem',
        '4xl' => '56rem',
        '5xl' => '64rem',
        '6xl' => '72rem',
        '7xl' => '80rem',
        'full' => '100%',
        'min' => 'min-content',
        'max' => 'max-content',
        'fit' => 'fit-content',
        'prose' => '65ch',
        'reading' => '44rem',
    ],

    'transitionTimingFunction' => [
        'linear' => 'linear',
        'in' => 'cubic-bezier(0.4, 0, 1, 1)',
        'out' => 'cubic-bezier(0, 0, 0.2, 1)',
        'in-out' => 'cubic-bezier(0.4, 0, 0.2, 1)',
        'out-soft' => 'cubic-bezier(0.22, 1, 0.36, 1)',
    ],

    'blur' => [
        'none' => '',
        'sm' => '4px',
        'DEFAULT' => '8px',
        'md' => '12px',
        'lg' => '16px',
        'xl' => '24px',
        '2xl' => '40px',
        '3xl' => '64px',
    ],

    'fontFamily' => [
        'sans' => "Inter,ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif",
        'serif' => "ui-serif,Georgia,Cambria,Times New Roman,Times,serif",
        'mono' => "ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,Liberation Mono,Courier New,monospace",
    ],
];
