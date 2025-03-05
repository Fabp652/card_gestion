<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250304143034 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE item_storage (item_id INT NOT NULL, storage_id INT NOT NULL, INDEX IDX_5F273D60126F525E (item_id), INDEX IDX_5F273D605CC5DB90 (storage_id), PRIMARY KEY(item_id, storage_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE item_storage ADD CONSTRAINT FK_5F273D60126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE item_storage ADD CONSTRAINT FK_5F273D605CC5DB90 FOREIGN KEY (storage_id) REFERENCES storage (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE item DROP FOREIGN KEY FK_1F1B251E5CC5DB90');
        $this->addSql('DROP INDEX IDX_1F1B251E5CC5DB90 ON item');
        $this->addSql('ALTER TABLE item DROP storage_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE item_storage DROP FOREIGN KEY FK_5F273D60126F525E');
        $this->addSql('ALTER TABLE item_storage DROP FOREIGN KEY FK_5F273D605CC5DB90');
        $this->addSql('DROP TABLE item_storage');
        $this->addSql('ALTER TABLE item ADD storage_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE item ADD CONSTRAINT FK_1F1B251E5CC5DB90 FOREIGN KEY (storage_id) REFERENCES storage (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_1F1B251E5CC5DB90 ON item (storage_id)');
    }
}
