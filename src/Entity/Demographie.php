<?php

namespace App\Entity;

use App\Repository\DemographieRepository;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;

#[ApiResource]
#[ORM\Entity(repositoryClass: DemographieRepository::class)]
class Demographie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $habitants = null;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2)]
    private ?string $densite = null;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2)]
    private ?string $variation_population = null;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2)]
    private ?string $solde_naturel = null;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2)]
    private ?string $solde_migratoire = null;

    #[ORM\ManyToOne(targetEntity: Annee::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Annee $id_annee = null;

    #[ORM\ManyToOne(targetEntity: Departement::class, inversedBy: 'demographies')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Departement $id_departement = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHabitants(): ?int
    {
        return $this->habitants;
    }

    public function setHabitants(int $habitants): static
    {
        $this->habitants = $habitants;
        return $this;
    }

    public function getDensite(): ?string
    {
        return $this->densite;
    }

    public function setDensite(string $densite): static
    {
        $this->densite = $densite;
        return $this;
    }

    public function getVariationPopulation(): ?string
    {
        return $this->variation_population;
    }

    public function setVariationPopulation(string $variation_population): static
    {
        $this->variation_population = $variation_population;
        return $this;
    }

    public function getSoldeNaturel(): ?string
    {
        return $this->solde_naturel;
    }

    public function setSoldeNaturel(string $solde_naturel): static
    {
        $this->solde_naturel = $solde_naturel;
        return $this;
    }

    public function getSoldeMigratoire(): ?string
    {
        return $this->solde_migratoire;
    }

    public function setSoldeMigratoire(string $solde_migratoire): static
    {
        $this->solde_migratoire = $solde_migratoire;
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