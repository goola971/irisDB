<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260307182911 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE demographie CHANGE id_annee_id id_annee_id INT NOT NULL, CHANGE id_departement_id id_departement_id INT NOT NULL');
        $this->addSql('ALTER TABLE economie CHANGE id_annee_id id_annee_id INT NOT NULL, CHANGE id_departement_id id_departement_id INT NOT NULL');
        $this->addSql('ALTER TABLE region ADD nom_region VARCHAR(100) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE demographie CHANGE id_annee_id id_annee_id INT DEFAULT NULL, CHANGE id_departement_id id_departement_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE economie CHANGE id_annee_id id_annee_id INT DEFAULT NULL, CHANGE id_departement_id id_departement_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE region DROP nom_region');
    }
}
