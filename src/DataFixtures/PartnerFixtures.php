<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Services\Faker\DataProvider;
use App\Entity\Partner;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class PartnerFixtures
 *
 * Load partner initial set of data.
 */
class PartnerFixtures extends BaseFixture
{
    /**
     * @var UserPasswordEncoderInterface
     */
    private $encoder;

    /**
     * PartnerFixtures constructor.
     * 
     * @param UserPasswordEncoderInterface $encoder
     */
    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        parent::__construct();
        $this->encoder = $encoder;
    }

    /**
     * {@inheritdoc}
     */
    public function loadData(ObjectManager $manager): void
    {
        // Get existing companies sets
        $data = DataProvider::PARTNER_COMPANY_SETS['references'];
        // Use a row index per partner type (will be used for Proxy objects references)
        $index = 1;
        // Create partner (API consumer)
        foreach ($data as $partnerType => $partnersIndexes) {
            $this->createFixtures(Partner::class, \count($partnersIndexes), function ($i) use ($partnerType, $index) {
                // Get corresponding partner name (professional only)
                $unique = substr(md5(uniqid()), 0, 4);
                $partnerName = $this->faker->lastName . ' ' . array_rand(array_flip(DataProvider::COMPANY_STATUS));
                // Get a company department randomly
                $partnerCompanyDepartment = array_rand(array_flip(DataProvider::PARTNER_COMPANY_DEPARTMENTS));
                // Get corresponding custom company web domain
                $domainWithoutTLD = $this->getCustomFakerProvider()->customSanitizedString($partnerName);
                $partnerCompanyWebDomain = $domainWithoutTLD . '.' . $this->faker->tld;
                // Get partner email
                $partnerEmail = strtolower($partnerCompanyDepartment) . '-' . $unique . '@' . $partnerCompanyWebDomain;
                // Get partner roles
                $partnerRoles = [Partner::DEFAULT_PARTNER_ROLE];
                $partner = new Partner();
                // Get partner password
                $partnerPassword = $this->encoder->encodePassword($partner, 'pass_' . $index . ($i + 1));
                return $partner
                    ->setCreationDate(new \DateTimeImmutable(sprintf("+%d days", -$i)))
                    ->setUpdateDate(new \DateTimeImmutable(sprintf("+%d days", -$i)))
                    ->setType($partnerType)
                    ->setUsername($partnerName)
                    ->setEmail($partnerEmail)
                    ->setPassword($partnerPassword)
                    ->setRoles($partnerRoles);
            }, $index);
            // Manage multiple series
           $index ++;
        }
        $manager->flush();
    }
}
