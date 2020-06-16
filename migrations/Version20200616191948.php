<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200616191948 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE clients (uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', partner_uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', type VARCHAR(45) NOT NULL, name VARCHAR(45) NOT NULL, email VARCHAR(320) NOT NULL, creation_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', update_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_C82E74814B63BE (partner_uuid), PRIMARY KEY(uuid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE offers (uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', partner_uuid CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', product_uuid CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', creation_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_DA460427814B63BE (partner_uuid), UNIQUE INDEX UNIQ_DA4604275C977207 (product_uuid), PRIMARY KEY(uuid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE partners (uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', type VARCHAR(45) NOT NULL, name VARCHAR(45) NOT NULL, email VARCHAR(320) NOT NULL, encoded_password VARCHAR(60) NOT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', creation_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', update_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(uuid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE products (uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', type VARCHAR(45) NOT NULL, brand VARCHAR(45) NOT NULL, model VARCHAR(45) NOT NULL, color VARCHAR(45) NOT NULL, description LONGTEXT NOT NULL, price DOUBLE PRECISION NOT NULL, creation_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', update_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(uuid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE clients ADD CONSTRAINT FK_C82E74814B63BE FOREIGN KEY (partner_uuid) REFERENCES partners (uuid)');
        $this->addSql('ALTER TABLE offers ADD CONSTRAINT FK_DA460427814B63BE FOREIGN KEY (partner_uuid) REFERENCES partners (uuid)');
        $this->addSql('ALTER TABLE offers ADD CONSTRAINT FK_DA4604275C977207 FOREIGN KEY (product_uuid) REFERENCES products (uuid)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE clients DROP FOREIGN KEY FK_C82E74814B63BE');
        $this->addSql('ALTER TABLE offers DROP FOREIGN KEY FK_DA460427814B63BE');
        $this->addSql('ALTER TABLE offers DROP FOREIGN KEY FK_DA4604275C977207');
        $this->addSql('DROP TABLE clients');
        $this->addSql('DROP TABLE offers');
        $this->addSql('DROP TABLE partners');
        $this->addSql('DROP TABLE products');
    }
}
