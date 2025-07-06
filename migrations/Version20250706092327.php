<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250706092327 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE collections ADD deleted_at DATETIME DEFAULT NULL, CHANGE category_id category_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE criteria ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE file_manager ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE item ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE market ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE rarity ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE storage ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE storage_type ADD deleted_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category DROP deleted_at');
        $this->addSql('ALTER TABLE collections DROP deleted_at, CHANGE category_id category_id INT NOT NULL');
        $this->addSql('ALTER TABLE criteria DROP deleted_at');
        $this->addSql('ALTER TABLE file_manager DROP deleted_at');
        $this->addSql('ALTER TABLE item DROP deleted_at');
        $this->addSql('ALTER TABLE market DROP deleted_at');
        $this->addSql('ALTER TABLE rarity DROP deleted_at');
        $this->addSql('ALTER TABLE storage DROP deleted_at');
        $this->addSql('ALTER TABLE storage_type DROP deleted_at');
    }
}
