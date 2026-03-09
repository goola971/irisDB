<?php

namespace App\Entity;

use App\Repository\LogementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;

#[ApiResource]
#[ORM\Entity(repositoryClass: LogementRepository::class)]
class Logement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $logements_total = null;

    #[ORM\Column]
    private ?int $logements_principaux = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private ?string $logements_sociaux = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private ?string $logements_individuels = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private ?string $logements_vacants = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private ?string $loyer_social = null;

    #[ORM\ManyToOne(inversedBy: 'logements')]
    private ?Annee $id_annee = null;

    #[ORM\ManyToOne(inversedBy: 'logements')]
    private ?Departement $id_departement = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLogementsTotal(): ?int
    {
        return $this->logements_total;
    }

    public function setLogementsTotal(int $logements_total): static
    {
        $this->logements_total = $logements_total;

        return $this;
    }

    public function getLogementsPrincipaux(): ?int
    {
        return $this->logements_principaux;
    }

    public function setLogementsPrincipaux(int $logements_principaux): static
    {
        $this->logements_principaux = $logements_principaux;

        return $this;
    }

    public function getLogementsSociaux(): ?string
    {
        return $this->logements_sociaux;
    }

    public function setLogementsSociaux(string $logements_sociaux): static
    {
        $this->logements_sociaux = $logements_sociaux;

        return $this;
    }

    public function getLogementsIndividuels(): ?string
    {
        return $this->logements_individuels;
    }

    public function setLogementsIndividuels(string $logements_individuels): static
    {
        $this->logements_individuels = $logements_individuels;

        return $this;
    }

    public function getLogementsVacants(): ?string
    {
        return $this->logements_vacants;
    }

    public function setLogementsVacants(string $logements_vacants): static
    {
        $this->logements_vacants = $logements_vacants;

        return $this;
    }

    public function getLoyerSocial(): ?string
    {
        return $this->loyer_social;
    }

    public function setLoyerSocial(string $loyer_social): static
    {
        $this->loyer_social = $loyer_social;

        return $this;
    }

    public function getIdAnnee(): ?Annee
    {
        return $this->id_annee;
    }

    public function setIdAnnee(?Annee $id_annee): static
    {
        $this->id_annee = $id_annee;

        return $this;
    }

    public function getIdDepartement(): ?Departement
    {
        return $this->id_departement;
    }

    public function setIdDepartement(?Departement $id_departement): static
    {
        $this->id_departement = $id_departement;

        return $this;
    }
}
