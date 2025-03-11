<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250311100500 extends AbstractMigration
{
    private $items;

    public function getDescription(): string
    {
        return '';
    }

    public function preUp(Schema $schema): void
    {
        parent::preUp($schema);

        $query = "SELECT GROUP_CONCAT(item_quality.id SEPARATOR ',') AS iq ";
        $query .= "FROM item INNER JOIN item_quality ON item.id = item_quality.item_id GROUP BY item.id";
        $this->items = $this->connection->executeQuery($query)->fetchFirstColumn();
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE item_quality ADD sort INT NOT NULL');
    }

    public function postUp(Schema $schema): void
    {
        parent::postUp($schema);

        foreach ($this->items as $item) {
            $sort = 1;
            $itemQualities = explode(',', $item);
            foreach ($itemQualities as $iq) {
                $this->connection->executeStatement('UPDATE item_quality SET sort = ' . $sort . ' WHERE id = ' . $iq);
                $sort++;
            }
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE item_quality DROP sort');
    }
}
