<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211014142403 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('INSERT INTO `event_type` (`id`,`description_es`,`description_eu`) VALUES (1,"Vacaciones","Oporrak")');
        $this->addSql('INSERT INTO `event_type` (`id`,`description_es`,`description_eu`) VALUES (2,"Asuntos particulares","Norbere kontuetarako baimena")');
        $this->addSql('INSERT INTO `event_type` (`id`,`description_es`,`description_eu`) VALUES (3,"Exceso jornada","Gehiegizko lanaldia")');
        $this->addSql('INSERT INTO `event_type` (`id`,`description_es`,`description_eu`) VALUES (4,"Días antigüedad","Antzinatasun egunak")');
        $this->addSql('INSERT INTO `event_type` (`id`,`description_es`,`description_eu`) VALUES (5,"Otros","Besteren bat")');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DELETE FROM `event_type` WHERE id=1');
        $this->addSql('DELETE FROM `event_type` WHERE id=2');
        $this->addSql('DELETE FROM `event_type` WHERE id=3');
        $this->addSql('DELETE FROM `event_type` WHERE id=4');
        $this->addSql('DELETE FROM `event_type` WHERE id=5');
    }
}
