<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240616080310 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, name VARCHAR(45) NOT NULL, INDEX IDX_64C19C1727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE category_criteria (category_id INT NOT NULL, criteria_id INT NOT NULL, INDEX IDX_1651FFA12469DE2 (category_id), INDEX IDX_1651FFA990BEA15 (criteria_id), PRIMARY KEY(category_id, criteria_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE collections (id INT AUTO_INCREMENT NOT NULL, category_id INT DEFAULT NULL, name VARCHAR(100) NOT NULL, INDEX IDX_D325D3EE12469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE criteria (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(45) NOT NULL, description LONGTEXT DEFAULT NULL, point INT NOT NULL, UNIQUE INDEX UNIQ_B61F9B815E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE item (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, category_id INT DEFAULT NULL, rarity_id INT DEFAULT NULL, collection_id INT DEFAULT NULL, name VARCHAR(100) NOT NULL, reference VARCHAR(45) DEFAULT NULL, number INT NOT NULL, price DOUBLE PRECISION NOT NULL, link VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL, quality INT NOT NULL, INDEX IDX_1F1B251E727ACA70 (parent_id), INDEX IDX_1F1B251E12469DE2 (category_id), INDEX IDX_1F1B251EF3747573 (rarity_id), INDEX IDX_1F1B251E514956FD (collection_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rarity (id INT AUTO_INCREMENT NOT NULL, collection_id INT NOT NULL, name VARCHAR(255) NOT NULL, grade INT NOT NULL, INDEX IDX_B7C0BE46514956FD (collection_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1727ACA70 FOREIGN KEY (parent_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE category_criteria ADD CONSTRAINT FK_1651FFA12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE category_criteria ADD CONSTRAINT FK_1651FFA990BEA15 FOREIGN KEY (criteria_id) REFERENCES criteria (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE collections ADD CONSTRAINT FK_D325D3EE12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE item ADD CONSTRAINT FK_1F1B251E727ACA70 FOREIGN KEY (parent_id) REFERENCES item (id)');
        $this->addSql('ALTER TABLE item ADD CONSTRAINT FK_1F1B251E12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE item ADD CONSTRAINT FK_1F1B251EF3747573 FOREIGN KEY (rarity_id) REFERENCES rarity (id)');
        $this->addSql('ALTER TABLE item ADD CONSTRAINT FK_1F1B251E514956FD FOREIGN KEY (collection_id) REFERENCES collections (id)');
        $this->addSql('ALTER TABLE rarity ADD CONSTRAINT FK_B7C0BE46514956FD FOREIGN KEY (collection_id) REFERENCES collections (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1727ACA70');
        $this->addSql('ALTER TABLE category_criteria DROP FOREIGN KEY FK_1651FFA12469DE2');
        $this->addSql('ALTER TABLE category_criteria DROP FOREIGN KEY FK_1651FFA990BEA15');
        $this->addSql('ALTER TABLE collections DROP FOREIGN KEY FK_D325D3EE12469DE2');
        $this->addSql('ALTER TABLE item DROP FOREIGN KEY FK_1F1B251E727ACA70');
        $this->addSql('ALTER TABLE item DROP FOREIGN KEY FK_1F1B251E12469DE2');
        $this->addSql('ALTER TABLE item DROP FOREIGN KEY FK_1F1B251EF3747573');
        $this->addSql('ALTER TABLE item DROP FOREIGN KEY FK_1F1B251E514956FD');
        $this->addSql('ALTER TABLE rarity DROP FOREIGN KEY FK_B7C0BE46514956FD');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE category_criteria');
        $this->addSql('DROP TABLE collections');
        $this->addSql('DROP TABLE criteria');
        $this->addSql('DROP TABLE item');
        $this->addSql('DROP TABLE rarity');
    }
}
