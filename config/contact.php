<?php

declare(strict_types=1);

/**
 * Kapcsolati adatok és társhonlapok
 * =============================================================================
 *
 * A Társhonlapok oldal (/tarshonlapok) és a lábléc ebből a fájlból olvassa az
 * elérhetőségeket és a külső hivatkozásokat. Az oldal szándékosan statikus:
 * nem a szervezői felületről szerkeszthető, hanem itt, a kódban.
 *
 * KITÖLTÉSRE VÁRÓ ÉRTÉKEK
 * -----------------------
 * Az üresen hagyott ('') mezők egyszerűen nem jelennek meg az oldalon, ezért
 * a hiányzó adat nem okoz csonka megjelenést. Az alábbiakat érdemes kitölteni:
 *
 *   - partners.mbbe.url         Az MBBE hivatalos címe. Figyelem: az mbbe.hu
 *                               domain a Magyar Betonburkolat Egyesületé, tehát
 *                               NEM ez a keresett oldal.
 *   - venue.address, phone, opening_hours
 *   - contact_person.phone
 *   - social.*.url              Facebook oldal, csoport, Eurokegel csoport
 *
 * A már ismert adatok (Eurokegel címe, info@okanyibiliard.hu) ki vannak töltve.
 */

return [
    /*
     * Társhonlapok: a hozzánk kapcsolódó szervezetek oldalai, rövid
     * leírással, hogy a látogató tudja, mit talál a másik oldalon.
     */
    'partners' => [
        'eurokegel' => [
            'name' => 'EuroKegel',
            'url' => 'https://www.eurokegel.eu/',
            'description' => 'A nemzetközi bábus biliárd, azaz az EuroKegel honlapja. '
                . 'Az oldal a hazai és a nemzetközi EuroKegel versenyek szervezését és '
                . 'lebonyolítását segíti. A játék 2015-ben dán, német és magyar '
                . 'összefogással jött létre, a magyar 120-as bábus biliárd közeli '
                . 'rokonaként; a játék- és versenyszabályzat első stabil változata '
                . '2016 áprilisában készült el.',
        ],
        'mbbe' => [
            'name' => 'MBBE',
            'url' => 'https://www.magyarbiliard.com',
            'description' => 'A versenyszezon kiírásáért és a hivatalos '
                . 'versenyrendszerért felelős szövetség. Itt találhatók a szezonra '
                . 'vonatkozó döntések, a versenynaptár és a hivatalos szabályzatok.',
        ],
    ],

    /*
     * A biliárdterem elérhetőségei.
     */
    'venue' => [
        'name' => 'Okányi Biliárdterem',
        'address' => '5534, Vasút utca 13.',
        'city' => 'Okány',
        // 'phone' => '06-30-279-2828',
        'email' => 'info@okanyibiliard.hu',
        'opening_hours' => '',
    ],

    /*
     * Kapcsolattartó személy.
     */
    'contact_person' => [
        'name' => 'Hőgyes Attila',
        'role' => 'Kapcsolattartó, versenyszervező',
        'phone' => '06-30-279-2828',
        'email' => 'hogyes.attila@okanyibiliard.hu',
    ],

    /*
     * Közösségi oldalak. A láblécben és a Társhonlapok oldalon is
     * megjelennek; az üres címűek kimaradnak.
     */
    'social' => [
        'facebook_page' => [
            'label' => 'Facebook oldal',
            'description' => 'Hírek, versenykiírások és eredmények',
            'url' => 'https://www.facebook.com/profile.php?id=61583462284212',
        ],
        'facebook_group' => [
            'label' => 'Facebook csoport',
            'description' => 'A helyi közösség beszélgetései',
            'url' => 'https://www.facebook.com/groups/6717827344992161',
        ],
        'eurokegel_group' => [
            'label' => 'EuroKegel Facebook csoport',
            'description' => 'A nemzetközi bábus biliárd közössége',
            'url' => 'https://www.facebook.com/groups/1496656687256334',
        ],
    ],
];
