<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250109092256 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add working minutes to workcalendar and set hours and minutes as integers';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE work_calendar ADD working_minutes INT NOT NULL, CHANGE working_hours working_hours INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE work_calendar DROP working_minutes, CHANGE working_hours working_hours NUMERIC(5, 2) NOT NULL');
    }
}
