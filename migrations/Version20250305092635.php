<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250305092635 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE item_sale (id INT AUTO_INCREMENT NOT NULL, price DOUBLE PRECISION NOT NULL, sold TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE item_storage DROP FOREIGN KEY FK_5F273D60126F525E');
        $this->addSql('ALTER TABLE item_storage DROP FOREIGN KEY FK_5F273D605CC5DB90');
        $this->addSql('DROP TABLE item_storage');
        $this->addSql('ALTER TABLE item_quality ADD item_sale_id INT DEFAULT NULL, ADD storage_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE item_quality ADD CONSTRAINT FK_77EF2D4441236E3C FOREIGN KEY (item_sale_id) REFERENCES item_sale (id)');
        $this->addSql('ALTER TABLE item_quality ADD CONSTRAINT FK_77EF2D445CC5DB90 FOREIGN KEY (storage_id) REFERENCES storage (id)');
        $this->addSql('CREATE INDEX IDX_77EF2D4441236E3C ON item_quality (item_sale_id)');
        $this->addSql('CREATE INDEX IDX_77EF2D445CC5DB90 ON item_quality (storage_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE item_quality DROP FOREIGN KEY FK_77EF2D4441236E3C');
        $this->addSql('CREATE TABLE item_storage (item_id INT NOT NULL, storage_id INT NOT NULL, INDEX IDX_5F273D60126F525E (item_id), INDEX IDX_5F273D605CC5DB90 (storage_id), PRIMARY KEY(item_id, storage_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE item_storage ADD CONSTRAINT FK_5F273D60126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE item_storage ADD CONSTRAINT FK_5F273D605CC5DB90 FOREIGN KEY (storage_id) REFERENCES storage (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('DROP TABLE item_sale');
        $this->addSql('ALTER TABLE item_quality DROP FOREIGN KEY FK_77EF2D445CC5DB90');
        $this->addSql('DROP INDEX IDX_77EF2D4441236E3C ON item_quality');
        $this->addSql('DROP INDEX IDX_77EF2D445CC5DB90 ON item_quality');
        $this->addSql('ALTER TABLE item_quality DROP item_sale_id, DROP storage_id');
    }
}
