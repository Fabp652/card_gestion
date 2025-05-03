<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250503092547 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE purchase (id INT AUTO_INCREMENT NOT NULL, price DOUBLE PRECISION NOT NULL, name VARCHAR(255) NOT NULL, received TINYINT(1) NOT NULL, refunded TINYINT(1) DEFAULT NULL, refund_request TINYINT(1) NOT NULL, refunded_reason VARCHAR(255) DEFAULT NULL, received_at DATETIME DEFAULT NULL, refunded_at DATETIME DEFAULT NULL, buy_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE item_purchase ADD purchase_id INT NOT NULL, DROP buy_at, DROP name, CHANGE refund_reason refund_reason VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE item_purchase ADD CONSTRAINT FK_D1A19F0558FBEB9 FOREIGN KEY (purchase_id) REFERENCES purchase (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D1A19F0558FBEB9 ON item_purchase (purchase_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE item_purchase DROP FOREIGN KEY FK_D1A19F0558FBEB9
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE purchase
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_D1A19F0558FBEB9 ON item_purchase
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE item_purchase ADD buy_at DATETIME DEFAULT NULL, ADD name VARCHAR(255) NOT NULL, DROP purchase_id, CHANGE refund_reason refund_reason LONGTEXT DEFAULT NULL
        SQL);
    }
}
