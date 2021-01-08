<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Services\Faker\Provider\DataProvider;
use App\Entity\Partner;
use Doctrine\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class PartnerFixtures
 *
 * Load partner initial set of data.
 */
class PartnerFixtures extends BaseFixture
{
    /**
     * Define log state to look at generated partner credentials.
     */
    const LOG_PARTNER_CREDENTIALS = false;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $encoder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * PartnerFixtures constructor.
     *
     * @param UserPasswordEncoderInterface $encoder
     * @param LoggerInterface              $logger
     */
    public function __construct(UserPasswordEncoderInterface $encoder, LoggerInterface $logger)
    {
        parent::__construct();
        $this->encoder = $encoder;
        $this->logger = $logger;
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
                $partnerName = $this->faker->lastName . ' ' . array_rand(array_flip(DataProvider::COMPANY_STATUS));
                // Get a company department randomly
                $partnerCompanyDepartment = array_rand(array_flip(DataProvider::PARTNER_COMPANY_DEPARTMENTS));
                // Get corresponding custom company web domain
                $domainWithoutTLD = $this->getCustomFakerProvider()->customSanitizedString($partnerName);
                $partnerCompanyWebDomain = $domainWithoutTLD . '.' . $this->faker->tld;
                // Get a unique identifier for email construction
                $unique = $this->faker->shuffle($this->faker->randomLetter . $this->faker->numberBetween(100, 999));
                // Get partner email
                $partnerEmail = strtolower($partnerCompanyDepartment) . '-' . $unique . '@' . $partnerCompanyWebDomain;
                // Get partner roles
                $partnerRoles = [Partner::DEFAULT_PARTNER_ROLE];
                // add admin role for first created partner (corresponds to rank "11")
                11 !== (int) ($index . ($i + 1)) ?: $partnerRoles[] = Partner::API_ADMIN_ROLE;
                $partner = new Partner();
                // Get partner credentials information with log
                !self::LOG_PARTNER_CREDENTIALS ?: $this->logPartnerCredentials($index, $i, $partnerEmail);
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
           $index++;
        }
        $manager->flush();
    }

    /**
     * Log partner credentials to look at generated information.
     *
     * @param int    $index
     * @param int    $i
     * @param string $partnerEmail
     *
     * @return void
     */
    private function logPartnerCredentials(int $index, int $i, string $partnerEmail): void
    {
        // Log consumer type, email and password information
        $consumerType = 11 !== (int) ($index . ($i + 1)) ? 'consumer' : 'admin';
        $this->logger->info($consumerType . ': ' . $partnerEmail . ' | pass_' . $index . ($i + 1));
    }
}
