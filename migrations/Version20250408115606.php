<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250408115606 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE item_purchase (id INT AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, name VARCHAR(255) NOT NULL, price DOUBLE PRECISION NOT NULL, quantity INT NOT NULL, link VARCHAR(255) DEFAULT NULL, received TINYINT(1) NOT NULL, refunded TINYINT(1) DEFAULT NULL, refund_request TINYINT(1) DEFAULT NULL, refund_reason LONGTEXT DEFAULT NULL, received_at DATETIME DEFAULT NULL, refund_at DATETIME DEFAULT NULL, buy_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, INDEX IDX_D1A19F0126F525E (item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE item_purchase ADD CONSTRAINT FK_D1A19F0126F525E FOREIGN KEY (item_id) REFERENCES item (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE collections CHANGE category_id category_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE criteria ADD sign VARCHAR(1) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE item CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE item_quality ADD deleted_at DATETIME DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE item_sale ADD refund TINYINT(1) DEFAULT NULL, ADD refund_requested TINYINT(1) DEFAULT NULL, ADD refund_reason LONGTEXT DEFAULT NULL, ADD send TINYINT(1) NOT NULL, ADD send_at DATETIME DEFAULT NULL, ADD sold_at DATETIME DEFAULT NULL, ADD refund_at DATETIME DEFAULT NULL, ADD deleted_at DATETIME DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_85F39C3C5E237E06 ON storage_type (name)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE item_purchase DROP FOREIGN KEY FK_D1A19F0126F525E
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE item_purchase
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE item_quality DROP deleted_at
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE collections CHANGE category_id category_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE item_sale DROP refund, DROP refund_requested, DROP refund_reason, DROP send, DROP send_at, DROP sold_at, DROP refund_at, DROP deleted_at
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE criteria DROP sign
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE item CHANGE created_at created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE updated_at updated_at DATETIME DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_85F39C3C5E237E06 ON storage_type
        SQL);
    }
}
