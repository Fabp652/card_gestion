<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250228142507 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE storage (id INT AUTO_INCREMENT NOT NULL, storage_type_id INT NOT NULL, name VARCHAR(255) NOT NULL, capacity INT DEFAULT NULL, full TINYINT(1) NOT NULL, INDEX IDX_547A1B34B270BFF1 (storage_type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE storage_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE storage ADD CONSTRAINT FK_547A1B34B270BFF1 FOREIGN KEY (storage_type_id) REFERENCES storage_type (id)');
        $this->addSql('ALTER TABLE item ADD storage_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE item ADD CONSTRAINT FK_1F1B251E5CC5DB90 FOREIGN KEY (storage_id) REFERENCES storage (id)');
        $this->addSql('CREATE INDEX IDX_1F1B251E5CC5DB90 ON item (storage_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE item DROP FOREIGN KEY FK_1F1B251E5CC5DB90');
        $this->addSql('ALTER TABLE storage DROP FOREIGN KEY FK_547A1B34B270BFF1');
        $this->addSql('DROP TABLE storage');
        $this->addSql('DROP TABLE storage_type');
        $this->addSql('DROP INDEX IDX_1F1B251E5CC5DB90 ON item');
        $this->addSql('ALTER TABLE item DROP storage_id');
    }
}
