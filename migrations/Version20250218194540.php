<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250218194540 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE file_manager (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, folder VARCHAR(45) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE item_quality (id INT AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, file_id INT DEFAULT NULL, quality INT NOT NULL, INDEX IDX_77EF2D44126F525E (item_id), INDEX IDX_77EF2D4493CB796C (file_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE item_quality_criteria (item_quality_id INT NOT NULL, criteria_id INT NOT NULL, INDEX IDX_9A1D10EA14D455D (item_quality_id), INDEX IDX_9A1D10EA990BEA15 (criteria_id), PRIMARY KEY(item_quality_id, criteria_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE item_quality ADD CONSTRAINT FK_77EF2D44126F525E FOREIGN KEY (item_id) REFERENCES item (id)');
        $this->addSql('ALTER TABLE item_quality ADD CONSTRAINT FK_77EF2D4493CB796C FOREIGN KEY (file_id) REFERENCES file_manager (id)');
        $this->addSql('ALTER TABLE item_quality_criteria ADD CONSTRAINT FK_9A1D10EA14D455D FOREIGN KEY (item_quality_id) REFERENCES item_quality (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE item_quality_criteria ADD CONSTRAINT FK_9A1D10EA990BEA15 FOREIGN KEY (criteria_id) REFERENCES criteria (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE category ADD file_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C193CB796C FOREIGN KEY (file_id) REFERENCES file_manager (id)');
        $this->addSql('CREATE INDEX IDX_64C19C193CB796C ON category (file_id)');
        $this->addSql('ALTER TABLE collections ADD file_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE collections ADD CONSTRAINT FK_D325D3EE93CB796C FOREIGN KEY (file_id) REFERENCES file_manager (id)');
        $this->addSql('CREATE INDEX IDX_D325D3EE93CB796C ON collections (file_id)');
        $this->addSql('ALTER TABLE item DROP quality');
        $this->addSql('ALTER TABLE rarity ADD file_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE rarity ADD CONSTRAINT FK_B7C0BE4693CB796C FOREIGN KEY (file_id) REFERENCES file_manager (id)');
        $this->addSql('CREATE INDEX IDX_B7C0BE4693CB796C ON rarity (file_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C193CB796C');
        $this->addSql('ALTER TABLE collections DROP FOREIGN KEY FK_D325D3EE93CB796C');
        $this->addSql('ALTER TABLE rarity DROP FOREIGN KEY FK_B7C0BE4693CB796C');
        $this->addSql('ALTER TABLE item_quality DROP FOREIGN KEY FK_77EF2D44126F525E');
        $this->addSql('ALTER TABLE item_quality DROP FOREIGN KEY FK_77EF2D4493CB796C');
        $this->addSql('ALTER TABLE item_quality_criteria DROP FOREIGN KEY FK_9A1D10EA14D455D');
        $this->addSql('ALTER TABLE item_quality_criteria DROP FOREIGN KEY FK_9A1D10EA990BEA15');
        $this->addSql('DROP TABLE file_manager');
        $this->addSql('DROP TABLE item_quality');
        $this->addSql('DROP TABLE item_quality_criteria');
        $this->addSql('DROP INDEX IDX_64C19C193CB796C ON category');
        $this->addSql('ALTER TABLE category DROP file_id');
        $this->addSql('DROP INDEX IDX_D325D3EE93CB796C ON collections');
        $this->addSql('ALTER TABLE collections DROP file_id');
        $this->addSql('ALTER TABLE item ADD quality INT NOT NULL');
        $this->addSql('DROP INDEX IDX_B7C0BE4693CB796C ON rarity');
        $this->addSql('ALTER TABLE rarity DROP file_id');
    }
}
