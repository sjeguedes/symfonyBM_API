<?php

declare(strict_types=1);

namespace App\Services\Faker;

use App\Entity\Phone;
use Faker\Generator;
use Faker\Provider\Base as BaseProvider;
use App\Entity\Partner;

/**
 * Class DataProvider.
 *
 * Prepare french customized and random set of data.
 *
 * @see https://github.com/fzaninotto/Faker#user-content-faker-internals-understanding-providers
 * @see https://github.com/mbezhanov/faker-provider-collection/blob/master/src/Faker/Provider/Device.php
 */
final class DataProvider extends BaseProvider
{
    /**
    * Define a set of company status.
    */
    const COMPANY_STATUS = ['E.U.R.L', 'S.A', 'S.A.R.L', 'S.A.S', 'S.A.S.U', 'S.C.O.P'];

    /**
     *  Define a set of partner company departments.
     */
    const PARTNER_COMPANY_DEPARTMENTS = ['business', 'contact', 'direction', 'information', 'prestataire'];

    /**
     * Define set of partners companies.
     */
    const PARTNER_COMPANY_SETS = [
        // Partner label
        'label'      => 'Partenaire',
        // 40 partners
        'references' => [
            // Retailers references
            Partner::PARTNER_TYPES[0] => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14],
            // phone network providers
            Partner::PARTNER_TYPES[1] => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13],
            // e-shops
            Partner::PARTNER_TYPES[2] => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13]
        ]
    ];

    /**
     * Define phone brands.
     */
    const PHONE_BRANDS = [
        // Phone label
        'label'      => 'Smartphone de la marque',
        'references' => [
            // Phone brands references (10 brands)
            [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
        ]

    ];

    /**
     * Define phone colors.
     */
    const PHONE_COLORS = ['Doré', 'Gris', 'Noir', 'Beige', 'Blanc', 'Rouge', 'Mauve', 'Bleu'];

    /**
     * Define a set of phone descriptions to produce a random content.
     */
    const PHONE_DESCRIPTIONS = [
        'révolutionne votre expérience de la vitesse et de la puissance',
        'intègre une puce plus puissante et plus intelligente',
        'permet un affichage et des prises de vue qui sont reconnues comme incroyables',
        'propose une des technologies les mieux équilibrées du marché',
        'combine toutes les dernières technologies disponibles avec un design innovant',
        'redéfinit l\'expérience mobile, la photographie et la vidéographie',
        'possède un design moderne avec un écran immersif de qualité',
        'répond aux besoins des utilisateurs à tout moment et en tout lieu',
        'séduit par un design à la fois solide et élégant qui convient à toute circonstance',
        'offre une expérience photo et vidéo époustouflante avec un grand degré de performance',
        'capture chaque instant pour les partager de la même façon que vous les vivez et que vous les ressentez',
        'conviendra à tous guel que soit l\'usage de votre téléphone portable',
        'demeure Simple d\'utilisation, fluide et intuitif à tout moment quel que soit le besoin',
        'rend l\'expérience visuelle divertissante et confortable grâce à son ergonomie',
        'permet une autonomie longue durée avec de grandes capacités techniques',
        'résiste au quotidien en étant Simple d\'utilisation, fluide et intuitif et conçu autour de la vidéo',
        'se démarque avec un design original et très soigné tout en offrant des performances de premier plan',
        's\'illustre en étant toujours plus innovant, avec un appareil photo grand angle et un zoom performant'
    ];

    /**
     * Define phone models.
     */
    const PHONE_MODELS = [
        'label' => 'Modèle avec référence',
        // 40 references
        'references' => [
            ['Xs 31', 'Se 765', 'r11 Lite', 'Xr 97'], // Brand 1
            ['P40', 'Y6 2019', 'P3 smart 2020', 'L56 Pro'], // Brand 2
            ['Gx S10', 'GnNote 10 Lite', 'Gx S20', 'Ga71'], // etc...
            ['Ta72', 'Fn X2 Lite', 'Rn 10x Zoom', 'Fd X2 Pro'],
            ['Ea L4', 'Ea 10 II', 'Kl-76', 'Mdf 2019'],
            ['Cr-M4', 'Tk-X4', 'Si-w6', 'V7 2020'],
            ['Fx 203', 'Tm 765', '2D Lite', 'Eg-42 97'],
            ['Rf 6000', 'JK 70', 'SQT Trekker', 'Zx 845'],
            ['Hy 2019', 'Min34 Lite', 'Tech-XS', 'VM Pro'],
            ['ATK-9 easy', 'Hi-Low 57', 'Pn90 2020', 'XP 10 Advanced']
        ]
    ];

    /**
     * Define phone prices per brand.
     */
    const PHONE_PRICES = [
        'references' => [
            [129.90, 199.90, 259.90, 299.90], // Brand 1
            [319.90, 359.90, 389.90, 419.90], // Brand 2
            [439.90, 479.90, 529.90, 589.90], // etc...
            [619.90, 649.90, 729.90, 759.90],
            [819.90, 859.90, 889.90, 929.90],
            [949.90, 979.90, 1029.90, 1100.90],
            [149.90, 279.90, 519.90, 1190.90],
            [239.90, 379.90, 419.90, 769.90],
            [449.90, 579.90, 629.90, 849.90],
            [749.90, 849.90, 939.90, 1169.90]
        ]
    ];

    /**
     * Define phone storage references.
     */
    const PHONE_STORAGE_REFERENCES = ['32Go', '64Go', '128Go', '256Go', '512Go'];

    /**
     * @var Generator
     */
    protected $faker;

    /**
     * AbstractFixtures constructor.
     *
     * @param Generator $generator
     */
    public function __construct(Generator $generator)
    {
        parent::__construct($generator);
    }

    /**
     * Get phone custom color.
     *
     * @return string
     */
    public function customPhoneColor(): string
    {
        return array_rand(array_flip(self::PHONE_COLORS));
    }

    /**
     * Get phone custom description content with 6 random elements by default.
     *
     * @param int $elementLength
     *
     * @return string
     */
    public function customPhoneDescription(int $elementLength = 6): string
    {
        return implode(",\n", array_rand(array_flip(self::PHONE_DESCRIPTIONS), $elementLength));
    }

    /**
     * Get phone custom coherent price.
     *
     * @param int $phoneBrandIndex
     * @param int $phoneModelIndex
     *
     * @return string
     */
    public function customPhonePrice(int $phoneBrandIndex, int $phoneModelIndex): string
    {
        // Get price english notation to be return as string
        return number_format(
            // float value
            self::PHONE_PRICES['references'][$phoneBrandIndex][$phoneModelIndex],
            2,
            '.',
            ''
        );
    }

    /**
     * Get phone custom fake type with type.
     *
     * @param string $phoneType)
     *
     * @return string
     */
    public function customPhoneStorage(string $phoneType): string
    {
        switch ($phoneType) {
            case Phone::PHONE_TYPES[4]: // Petit prix
                return DataProvider::PHONE_STORAGE_REFERENCES[0]; // 32Go
            case Phone::PHONE_TYPES[3]:  // Bon plan
                return DataProvider::PHONE_STORAGE_REFERENCES[1]; // 64GO
            case Phone::PHONE_TYPES[2]: // Reconditionné
                return DataProvider::PHONE_STORAGE_REFERENCES[2]; // 128Go
            case Phone::PHONE_TYPES[1]: // Exclusivité
                return DataProvider::PHONE_STORAGE_REFERENCES[3]; // 256Go
            case Phone::PHONE_TYPES[0]: // Premium
                return DataProvider::PHONE_STORAGE_REFERENCES[4]; // 512Mo
        }
    }

    /**
     * Get phone custom fake type with price.
     *
     * @param string $phonePrice
     *
     * @return string
     */
    public function customPhoneType(string $phonePrice): string
    {
        // Get integer value to compare price
        $phonePrice = (int) bcmul($phonePrice, '100');
        switch ($phonePrice) {
            case 0 < $phonePrice && $phonePrice < 20000:
                return Phone::PHONE_TYPES[4]; // Petit prix
            case 20000 <= $phonePrice && $phonePrice < 30000:
                return Phone::PHONE_TYPES[3]; // Bon plan
            case 30000 <= $phonePrice && $phonePrice < 50000:
                return Phone::PHONE_TYPES[2]; // Reconditionné
            case 50000 <= $phonePrice && $phonePrice < 80000:
                return Phone::PHONE_TYPES[1]; // Exclusivité
            case 80000 <= $phonePrice:
                return Phone::PHONE_TYPES[0]; // Premium
        }
    }

    /**
     * Sanitize a string with transliterator and replace some characters to make a kind of slug.
     *
     * @param string $string
     * @param string $delimiter
     *
     * @return string|null
     *
     * @see https://www.php.net/manual/en/transliterator.transliterate.php
     */
    public function customSanitizedString(string $string, string $delimiter = '-') : ?string
    {
        // Replace latin characters and lower-case string, remove non spacing mark but not punctuation...
        $string = transliterator_transliterate("Any-Latin; Latin-ASCII; NFD; [:Nonspacing Mark:] Remove; NFC; Lower();", $string);
        // Delete all characters which are not in this list (delimiter is excluded.)
        $string = preg_replace('/[^a-z0-9\/_|+\s-]/i', '', $string);
        // Replace this characters list with delimiter
        $string = preg_replace('/[\/_|+\s-]+/', $delimiter, $string);
        // Trim delimiter (left and right)
        return trim($string, $delimiter);
    }
}
