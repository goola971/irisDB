<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260324174859 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE admin (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(50) NOT NULL, password VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE annee (id INT AUTO_INCREMENT NOT NULL, annee INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE demographie (id INT AUTO_INCREMENT NOT NULL, habitants INT NOT NULL, densite NUMERIC(15, 2) NOT NULL, variation_population NUMERIC(15, 2) NOT NULL, solde_naturel NUMERIC(15, 2) NOT NULL, solde_migratoire NUMERIC(15, 2) NOT NULL, id_annee_id INT NOT NULL, id_departement_id INT NOT NULL, INDEX IDX_1016B0E74E52965 (id_annee_id), INDEX IDX_1016B0E7F19F5D18 (id_departement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE departement (id INT AUTO_INCREMENT NOT NULL, code_departement VARCHAR(5) NOT NULL, nom_departement VARCHAR(100) NOT NULL, id_region_id INT DEFAULT NULL, INDEX IDX_C1765B631813BD72 (id_region_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE economie (id INT AUTO_INCREMENT NOT NULL, taux_chomage NUMERIC(15, 2) NOT NULL, taux_pauvrete NUMERIC(15, 2) NOT NULL, id_annee_id INT NOT NULL, id_departement_id INT NOT NULL, INDEX IDX_327AA9724E52965 (id_annee_id), INDEX IDX_327AA972F19F5D18 (id_departement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE logement (id INT AUTO_INCREMENT NOT NULL, logements_total INT NOT NULL, logements_principaux INT NOT NULL, logements_sociaux NUMERIC(15, 2) NOT NULL, logements_individuels NUMERIC(15, 2) NOT NULL, logements_vacants NUMERIC(15, 2) NOT NULL, loyer_social NUMERIC(15, 2) NOT NULL, id_annee_id INT DEFAULT NULL, id_departement_id INT DEFAULT NULL, INDEX IDX_F0FD44574E52965 (id_annee_id), INDEX IDX_F0FD4457F19F5D18 (id_departement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE region (id INT AUTO_INCREMENT NOT NULL, code_region INT NOT NULL, nom_region VARCHAR(100) NOT NULL, id_admin_id INT DEFAULT NULL, INDEX IDX_F62F17634F06E85 (id_admin_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE demographie ADD CONSTRAINT FK_1016B0E74E52965 FOREIGN KEY (id_annee_id) REFERENCES annee (id)');
        $this->addSql('ALTER TABLE demographie ADD CONSTRAINT FK_1016B0E7F19F5D18 FOREIGN KEY (id_departement_id) REFERENCES departement (id)');
        $this->addSql('ALTER TABLE departement ADD CONSTRAINT FK_C1765B631813BD72 FOREIGN KEY (id_region_id) REFERENCES region (id)');
        $this->addSql('ALTER TABLE economie ADD CONSTRAINT FK_327AA9724E52965 FOREIGN KEY (id_annee_id) REFERENCES annee (id)');
        $this->addSql('ALTER TABLE economie ADD CONSTRAINT FK_327AA972F19F5D18 FOREIGN KEY (id_departement_id) REFERENCES departement (id)');
        $this->addSql('ALTER TABLE logement ADD CONSTRAINT FK_F0FD44574E52965 FOREIGN KEY (id_annee_id) REFERENCES annee (id)');
        $this->addSql('ALTER TABLE logement ADD CONSTRAINT FK_F0FD4457F19F5D18 FOREIGN KEY (id_departement_id) REFERENCES departement (id)');
        $this->addSql('ALTER TABLE region ADD CONSTRAINT FK_F62F17634F06E85 FOREIGN KEY (id_admin_id) REFERENCES admin (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE demographie DROP FOREIGN KEY FK_1016B0E74E52965');
        $this->addSql('ALTER TABLE demographie DROP FOREIGN KEY FK_1016B0E7F19F5D18');
        $this->addSql('ALTER TABLE departement DROP FOREIGN KEY FK_C1765B631813BD72');
        $this->addSql('ALTER TABLE economie DROP FOREIGN KEY FK_327AA9724E52965');
        $this->addSql('ALTER TABLE economie DROP FOREIGN KEY FK_327AA972F19F5D18');
        $this->addSql('ALTER TABLE logement DROP FOREIGN KEY FK_F0FD44574E52965');
        $this->addSql('ALTER TABLE logement DROP FOREIGN KEY FK_F0FD4457F19F5D18');
        $this->addSql('ALTER TABLE region DROP FOREIGN KEY FK_F62F17634F06E85');
        $this->addSql('DROP TABLE admin');
        $this->addSql('DROP TABLE annee');
        $this->addSql('DROP TABLE demographie');
        $this->addSql('DROP TABLE departement');
        $this->addSql('DROP TABLE economie');
        $this->addSql('DROP TABLE logement');
        $this->addSql('DROP TABLE region');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
