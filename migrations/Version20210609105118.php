<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210609105118 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE work_calendar DROP annual_maximum_working_hours, DROP annual_maximum_working_days, DROP daily_working_hours, DROP daily_working_minutes, DROP break, DROP annual_total_work_hours, DROP overtime_hours');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE work_calendar ADD annual_maximum_working_hours INT NOT NULL, ADD annual_maximum_working_days INT NOT NULL, ADD daily_working_hours INT NOT NULL, ADD daily_working_minutes INT NOT NULL, ADD break INT NOT NULL, ADD annual_total_work_hours INT NOT NULL, ADD overtime_hours INT NOT NULL');
    }
}
