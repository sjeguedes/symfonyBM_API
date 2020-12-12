<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20200710104220
 *
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200710104220 extends AbstractMigration
{

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE clients (uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', partner_uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', type VARCHAR(45) NOT NULL, name VARCHAR(45) NOT NULL, email VARCHAR(320) NOT NULL, creation_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', update_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_C82E74E7927C74 (email), INDEX IDX_C82E74814B63BE (partner_uuid), PRIMARY KEY(uuid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE offers (uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', partner_uuid CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', phone_uuid CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', creation_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_DA460427814B63BE (partner_uuid), INDEX IDX_DA460427327B02B4 (phone_uuid), PRIMARY KEY(uuid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE partners (uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', type VARCHAR(45) NOT NULL, username VARCHAR(45) NOT NULL, email VARCHAR(320) NOT NULL, password VARCHAR(98) NOT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', creation_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', update_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_EFEB5164E7927C74 (email), UNIQUE INDEX UNIQ_EFEB516435C246D5 (password), PRIMARY KEY(uuid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE phones (uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', type VARCHAR(45) NOT NULL, brand VARCHAR(45) NOT NULL, model VARCHAR(45) NOT NULL, color VARCHAR(45) NOT NULL, description LONGTEXT NOT NULL, price NUMERIC(6, 2) NOT NULL, creation_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', update_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_E3282EF5D79572D9 (model), PRIMARY KEY(uuid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE clients ADD CONSTRAINT FK_C82E74814B63BE FOREIGN KEY (partner_uuid) REFERENCES partners (uuid)');
        $this->addSql('ALTER TABLE offers ADD CONSTRAINT FK_DA460427814B63BE FOREIGN KEY (partner_uuid) REFERENCES partners (uuid)');
        $this->addSql('ALTER TABLE offers ADD CONSTRAINT FK_DA460427327B02B4 FOREIGN KEY (phone_uuid) REFERENCES phones (uuid)');
    }

    /**
     * {@inheritdoc}
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE clients DROP FOREIGN KEY FK_C82E74814B63BE');
        $this->addSql('ALTER TABLE offers DROP FOREIGN KEY FK_DA460427814B63BE');
        $this->addSql('ALTER TABLE offers DROP FOREIGN KEY FK_DA460427327B02B4');
        $this->addSql('DROP TABLE clients');
        $this->addSql('DROP TABLE offers');
        $this->addSql('DROP TABLE partners');
        $this->addSql('DROP TABLE phones');
    }
}
