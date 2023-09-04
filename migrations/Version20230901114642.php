<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230901114642 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE status ADD description_eu VARCHAR(255) NOT NULL, CHANGE description description_es VARCHAR(255) NOT NULL');
        $this->addSql('UPDATE status SET description_eu="Eskatuak", description_es="Solicitados" WHERE id=1');
        $this->addSql('UPDATE status SET description_eu="Onartuak", description_es="Aprobados" WHERE id=2');
        $this->addSql('UPDATE status SET description_eu="Ez onartuak", description_es="No aprobados" WHERE id=3');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE status ADD description VARCHAR(255) NOT NULL, DROP description_es, DROP description_eu');
        $this->addSql('UPDATE status SET description="Reserved" WHERE id=1');
        $this->addSql('UPDATE status SET description="Approved" WHERE id=2');
        $this->addSql('UPDATE status SET description="Not Approved" WHERE id=3');
    }
}
