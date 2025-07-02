<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250702114415 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE item_quality ADD available_sale TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE item_sale DROP INDEX UNIQ_2D7B84FE14D455D, ADD INDEX IDX_2D7B84FE14D455D (item_quality_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE item_quality DROP available_sale');
        $this->addSql('ALTER TABLE item_sale DROP INDEX IDX_2D7B84FE14D455D, ADD UNIQUE INDEX UNIQ_2D7B84FE14D455D (item_quality_id)');
    }
}
