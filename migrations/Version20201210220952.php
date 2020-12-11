<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201210220952 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE http_caches (uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', route_name VARCHAR(45) NOT NULL, type VARCHAR(15) NOT NULL, class_short_name VARCHAR(15) NOT NULL, ttl_expiration INT NOT NULL, etag_token VARCHAR(32) NOT NULL, creation_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', update_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_3E53277CF3667F83 (route_name), UNIQUE INDEX UNIQ_3E53277C4F960BD1 (etag_token), PRIMARY KEY(uuid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE http_caches');
    }
}
