<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231120140835 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("UPDATE `oporrak`.`event_type` set description_es='Vacaciones Addicionales', description_eu='Opor egun gehigarriak' WHERE id=5");
        $this->addSql("INSERT INTO `oporrak`.`event_type` (`id`, `description_es`, `description_eu`) VALUES ('6', 'Otros', 'Besteren bat')");
        $this->addSql("UPDATE `oporrak`.`event` set type_id=6 WHERE type_id=5");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("UPDATE `oporrak`.`event` set type_id=5 WHERE type_id=6");
        $this->addSql('DELETE FROM `oporrak`.`event_type` WHERE id=6');
        $this->addSql("UPDATE `oporrak`.`event_type` set description_es='Otros', description_eu='Besteren bat' WHERE id=5");
    }
}
