<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250714125341 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE item_file_manager (item_id INT NOT NULL, file_manager_id INT NOT NULL, INDEX IDX_6E186143126F525E (item_id), INDEX IDX_6E186143C1D6EFD5 (file_manager_id), PRIMARY KEY(item_id, file_manager_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE item_quality_file_manager (item_quality_id INT NOT NULL, file_manager_id INT NOT NULL, INDEX IDX_A0EB168014D455D (item_quality_id), INDEX IDX_A0EB1680C1D6EFD5 (file_manager_id), PRIMARY KEY(item_quality_id, file_manager_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE item_file_manager ADD CONSTRAINT FK_6E186143126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE item_file_manager ADD CONSTRAINT FK_6E186143C1D6EFD5 FOREIGN KEY (file_manager_id) REFERENCES file_manager (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE item_quality_file_manager ADD CONSTRAINT FK_A0EB168014D455D FOREIGN KEY (item_quality_id) REFERENCES item_quality (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE item_quality_file_manager ADD CONSTRAINT FK_A0EB1680C1D6EFD5 FOREIGN KEY (file_manager_id) REFERENCES file_manager (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE item_quality DROP FOREIGN KEY FK_77EF2D4493CB796C');
        $this->addSql('DROP INDEX IDX_77EF2D4493CB796C ON item_quality');
        $this->addSql('ALTER TABLE item_quality DROP file_id');
        $this->addSql('ALTER TABLE purchase ADD file_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE purchase ADD CONSTRAINT FK_6117D13B93CB796C FOREIGN KEY (file_id) REFERENCES file_manager (id)');
        $this->addSql('CREATE INDEX IDX_6117D13B93CB796C ON purchase (file_id)');
        $this->addSql('ALTER TABLE sale ADD file_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE sale ADD CONSTRAINT FK_E54BC00593CB796C FOREIGN KEY (file_id) REFERENCES file_manager (id)');
        $this->addSql('CREATE INDEX IDX_E54BC00593CB796C ON sale (file_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE item_file_manager DROP FOREIGN KEY FK_6E186143126F525E');
        $this->addSql('ALTER TABLE item_file_manager DROP FOREIGN KEY FK_6E186143C1D6EFD5');
        $this->addSql('ALTER TABLE item_quality_file_manager DROP FOREIGN KEY FK_A0EB168014D455D');
        $this->addSql('ALTER TABLE item_quality_file_manager DROP FOREIGN KEY FK_A0EB1680C1D6EFD5');
        $this->addSql('DROP TABLE item_file_manager');
        $this->addSql('DROP TABLE item_quality_file_manager');
        $this->addSql('ALTER TABLE item_quality ADD file_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE item_quality ADD CONSTRAINT FK_77EF2D4493CB796C FOREIGN KEY (file_id) REFERENCES file_manager (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_77EF2D4493CB796C ON item_quality (file_id)');
        $this->addSql('ALTER TABLE purchase DROP FOREIGN KEY FK_6117D13B93CB796C');
        $this->addSql('DROP INDEX IDX_6117D13B93CB796C ON purchase');
        $this->addSql('ALTER TABLE purchase DROP file_id');
        $this->addSql('ALTER TABLE sale DROP FOREIGN KEY FK_E54BC00593CB796C');
        $this->addSql('DROP INDEX IDX_E54BC00593CB796C ON sale');
        $this->addSql('ALTER TABLE sale DROP file_id');
    }
}
