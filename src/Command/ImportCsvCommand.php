<?php

namespace App\Command;

use App\Entity\Admin;
use App\Entity\Annee;
use App\Entity\Region;
use App\Entity\Departement;
use App\Entity\Demographie;
use App\Entity\Economie;
use App\Entity\Logement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-csv',
    description: 'Importe les données du CSV irisDB',
)]
class ImportCsvCommand extends Command
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Démarrage de l\'importation irisDB');

        $projectDir = $this->getApplication()->getKernel()->getProjectDir();
        $file = $projectDir . '/logements-et-logements-sociaux-dans-les-departements.csv';

        if (!file_exists($file)) {
            $io->error('Fichier CSV introuvable à la racine.');
            return Command::FAILURE;
        }

        $handle = fopen($file, 'r');
        fgetcsv($handle, 0, ';'); // Sauter l'entête

        // 1. Gérer l'Admin par défaut (obligatoire pour Region)
        $admin = $this->em->getRepository(Admin::class)->findOneBy([]) ?? new Admin();
        if (!$admin->getId()) {
            $admin->setUsername('admin_system');
            $admin->setPassword('password_hash'); 
            $this->em->persist($admin);
            $this->em->flush(); // On flush l'admin tout de suite pour avoir son ID
        }

        $count = 0;
        $io->progressStart();

        while (($data = fgetcsv($handle, 0, ';')) !== FALSE) {
            if (empty($data[0])) continue;

            // 1. Année
            $anneeVal = (int)$data[0];
            $annee = $this->em->getRepository(Annee::class)->findOneBy(['annee' => $anneeVal]) ?? new Annee();
            $annee->setAnnee($anneeVal);
            $this->em->persist($annee);

            // 2. Région
            $codeReg = (int)$data[3];
            $region = $this->em->getRepository(Region::class)->findOneBy(['code_region' => $codeReg]) ?? new Region();
            $region->setCodeRegion($codeReg);
            $region->setNomRegion($data[4]); // Corrigé : setNomRegion au lieu de setNom_Region
            $region->setIdAdmin($admin);
            $this->em->persist($region);

            // 3. Département
            $codeDep = $data[1];
            $dept = $this->em->getRepository(Departement::class)->findOneBy(['code_departement' => $codeDep]) ?? new Departement();
            $dept->setCodeDepartement($codeDep);
            $dept->setNomDepartement($data[2]);
            $dept->setIdRegion($region);
            $this->em->persist($dept);

            // 4. Démographie
            $demo = new Demographie();
            $demo->setHabitants((int)$data[5]);
            $demo->setDensite($this->parseFloat($data[6]));
            $demo->setVariationPopulation($this->parseFloat($data[7]));
            $demo->setSoldeNaturel($this->parseFloat($data[8]));
            $demo->setSoldeMigratoire($this->parseFloat($data[9]));
            $demo->setIdAnnee($annee);
            $demo->setIdDepartement($dept);
            $this->em->persist($demo);

            // 5. Économie
            $eco = new Economie();
            $eco->setTauxChomage($this->parseFloat($data[12]));
            $eco->setTauxPauvrete($this->parseFloat($data[13]));
            $eco->setIdAnnee($annee);
            $eco->setIdDepartement($dept);
            $this->em->persist($eco);

            // 6. Logement
            $log = new Logement();
            $log->setLogementsTotal((int)$data[14]);
            $log->setLogementsPrincipaux((int)$data[15]);
            $log->setLogementsSociaux($this->parseFloat($data[16]));
            $log->setLogementsVacants($this->parseFloat($data[17]));
            $log->setLogementsIndividuels($this->parseFloat($data[18]));
            $log->setLoyerSocial($this->parseFloat($data[27]));
            $log->setIdAnnee($annee);
            $log->setIdDepartement($dept);
            $this->em->persist($log);

            if (($count % 50) === 0) {
                $this->em->flush();
            }
            $count++;
            $io->progressAdvance();
        }

        $this->em->flush(); // Flush final
        fclose($handle);
        $io->progressFinish();
        $io->success("Importation réussie ! $count lignes persistées en base.");

        return Command::SUCCESS;
    }

    /**
     * Nettoie les nombres (remplace virgule par point)
     */
    private function parseFloat(?string $value): float
    {
        if (!$value) return 0.0;
        return (float)str_replace(',', '.', $value);
    }
}