<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250612120420 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE market (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(45) NOT NULL, is_web_site TINYINT(1) NOT NULL, link VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE purchase ADD market_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE purchase ADD CONSTRAINT FK_6117D13B622F3F37 FOREIGN KEY (market_id) REFERENCES market (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_6117D13B622F3F37 ON purchase (market_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE purchase DROP FOREIGN KEY FK_6117D13B622F3F37
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE market
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_6117D13B622F3F37 ON purchase
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE purchase DROP market_id
        SQL);
    }
}
