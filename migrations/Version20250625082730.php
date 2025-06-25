<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250625082730 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE sale (id INT AUTO_INCREMENT NOT NULL, market_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, price DOUBLE PRECISION DEFAULT NULL, send TINYINT(1) DEFAULT NULL, refunded TINYINT(1) DEFAULT NULL, refund_request TINYINT(1) NOT NULL, refund_reason VARCHAR(255) DEFAULT NULL, send_at DATETIME DEFAULT NULL, refund_at DATETIME DEFAULT NULL, sold_at DATETIME DEFAULT NULL, is_order TINYINT(1) NOT NULL, sold TINYINT(1) NOT NULL, link VARCHAR(255) DEFAULT NULL, is_valid TINYINT(1) NOT NULL, validated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, INDEX IDX_E54BC005622F3F37 (market_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sale ADD CONSTRAINT FK_E54BC005622F3F37 FOREIGN KEY (market_id) REFERENCES market (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE item_quality DROP FOREIGN KEY FK_77EF2D4441236E3C
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_77EF2D4441236E3C ON item_quality
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE item_quality DROP item_sale_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE item_sale ADD sale_id INT NOT NULL, ADD item_quality_id INT NOT NULL, ADD refunded TINYINT(1) DEFAULT NULL, DROP refund, DROP link, DROP name, DROP refund_requested, DROP sold_at, CHANGE sold refund_request TINYINT(1) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE item_sale ADD CONSTRAINT FK_2D7B84FE4A7E4868 FOREIGN KEY (sale_id) REFERENCES sale (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE item_sale ADD CONSTRAINT FK_2D7B84FE14D455D FOREIGN KEY (item_quality_id) REFERENCES item_quality (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_2D7B84FE4A7E4868 ON item_sale (sale_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_2D7B84FE14D455D ON item_sale (item_quality_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE item_sale DROP FOREIGN KEY FK_2D7B84FE4A7E4868
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sale DROP FOREIGN KEY FK_E54BC005622F3F37
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE sale
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE item_quality ADD item_sale_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE item_quality ADD CONSTRAINT FK_77EF2D4441236E3C FOREIGN KEY (item_sale_id) REFERENCES item_sale (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_77EF2D4441236E3C ON item_quality (item_sale_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE item_sale DROP FOREIGN KEY FK_2D7B84FE14D455D
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_2D7B84FE4A7E4868 ON item_sale
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_2D7B84FE14D455D ON item_sale
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE item_sale ADD link VARCHAR(255) DEFAULT NULL, ADD name VARCHAR(255) NOT NULL, ADD refund_requested TINYINT(1) DEFAULT NULL, ADD sold_at DATETIME DEFAULT NULL, DROP sale_id, DROP item_quality_id, CHANGE refund_request sold TINYINT(1) NOT NULL, CHANGE refunded refund TINYINT(1) DEFAULT NULL
        SQL);
    }
}
