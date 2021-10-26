<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211015110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('UPDATE event set type_id=1 where NAME="Vacaciones/Oporrak"');
        $this->addSql('UPDATE event set type_id=2 where NAME="Asuntos particulares/Norbere kontuetarako baimena"');
        $this->addSql('UPDATE event set type_id=3 where NAME="Exceso jornada/Gehiegizko lanaldia"');
        $this->addSql('UPDATE event set type_id=4 where NAME="Días antigüedad/Antzinatasun egunak"');
        $this->addSql('UPDATE event set type_id=5 where NAME="Otros/Besteren bat"');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('UPDATE event set type_id=null');
    }
}
