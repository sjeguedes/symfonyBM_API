<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210101201853 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE jwt_refresh_tokens (uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', refresh_token VARCHAR(128) NOT NULL, username VARCHAR(255) NOT NULL, valid DATETIME NOT NULL, creation_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_4D2236BDC74F2195 (refresh_token), PRIMARY KEY(uuid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE jwt_refresh_tokens');
    }
}
