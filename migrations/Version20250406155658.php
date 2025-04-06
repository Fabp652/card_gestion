<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250406155658 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE category DROP FOREIGN KEY FK_64C19C193CB796C
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_64C19C193CB796C ON category
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE category DROP file_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE collections ADD complete TINYINT(1) NOT NULL, ADD has_rarities TINYINT(1) NOT NULL, CHANGE name name VARCHAR(45) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rarity CHANGE name name VARCHAR(45) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE storage CHANGE name name VARCHAR(45) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE storage_type CHANGE name name VARCHAR(10) NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE category ADD file_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE category ADD CONSTRAINT FK_64C19C193CB796C FOREIGN KEY (file_id) REFERENCES file_manager (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_64C19C193CB796C ON category (file_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE collections DROP complete, DROP has_rarities, CHANGE name name VARCHAR(100) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rarity CHANGE name name VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE storage CHANGE name name VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE storage_type CHANGE name name VARCHAR(255) NOT NULL
        SQL);
    }
}
