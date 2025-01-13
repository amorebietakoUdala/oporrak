<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250109094138 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add minutes and hours as integer a fixed previous values';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE event_backup as SELECT * from event');
        $this->addSql('ALTER TABLE event ADD minutes INT DEFAULT NULL, CHANGE hours hours INT DEFAULT NULL');
        $this->addSql('UPDATE event INNER JOIN event_backup ON event.id= event_backup.id SET event.hours=FLOOR(event_backup.hours), event.minutes=(event_backup.hours-FLOOR(event_backup.hours))*60');
        $this->addSql('ALTER TABLE oporrak.event MODIFY COLUMN minutes INTEGER AFTER hours');
        $this->addSql('DROP TABLE event_backup');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE event');
        $this->addSql('CREATE TABLE event as SELECT * from event_backup');
        $this->addSql('ALTER TABLE event CHANGE COLUMN `id` `id` INT NOT NULL AUTO_INCREMENT , ADD PRIMARY KEY (`id`)');
        $this->addSql('DROP TABLE event_backup');
    }
}
