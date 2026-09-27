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
 * a hiányzó adat nem okoz csonka megjelenést. Ami még hiányzik:
 *
 *   - venue.opening_hours       A terem nyitvatartása
 *   - venue.phone               Kikommentezve: a terem külön száma, ha van.
 *                               Nélküle a kapcsolattartó száma az elérhetőség.
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
                . 'versenyrendszerért felelős egyesület, a Magyar Biliárd Szövetség bábus szakága. Itt találhatók a szezonra '
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
     *
     * A "versenyszervező" megjelölés átkerült a competition_organizer
     * bejegyzésre: két, versenyszervezőként feltüntetett név félrevezető
     * lenne, a látogatónak tudnia kell, versenyügyben kit keressen.
     */
    'contact_person' => [
        'name' => 'Hőgyes Attila',
        'role' => 'Versenyszervező',
        'phone' => '+36 30 279 2828',
        'email' => 'info@okanyibiliard.hu',
    ],

    /*
     * Versenyszervező: a versenykiírásokkal, a nevezéssel és a lebonyolítással
     * kapcsolatos kérdések címzettje.
     *
     * A telefonszám nemzetközi alakban áll, mert a kapcsolat oldal tel:
     * hivatkozást készít belőle, és a +36 előtag külföldről is hívható.
     * A 06 30 409 1869 és a +36 30 409 1869 ugyanaz a szám.
     */
    'competition_organizer' => [
        'name' => 'Kincses László',
        'role' => 'Verseny lebonyolításában segítséget nyújt',
        'phone' => '+36 30 409 1869',
        'email' => 'kincses.lszl@gmail.com',
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
