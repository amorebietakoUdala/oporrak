<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210525092729 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE work_calendar (id INT AUTO_INCREMENT NOT NULL, annual_maximum_working_hours INT NOT NULL, annual_maximum_working_days INT NOT NULL, daily_working_hours INT NOT NULL, daily_working_minutes INT NOT NULL, break INT NOT NULL, annual_total_work_hours INT NOT NULL, overtime_hours INT NOT NULL, vacation_days INT NOT NULL, particular_business_leave INT NOT NULL, overtime_days INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE work_calendar');
    }
}
