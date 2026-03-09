<?php

namespace App\Entity;

use App\Repository\EconomieRepository;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;

#[ApiResource]
#[ORM\Entity(repositoryClass: EconomieRepository::class)]
class Economie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2)]
    private ?string $taux_chomage = null;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2)]
    private ?string $taux_pauvrete = null;

    #[ORM\ManyToOne(targetEntity: Annee::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Annee $id_annee = null;

    #[ORM\ManyToOne(targetEntity: Departement::class, inversedBy: 'economies')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Departement $id_departement = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTauxChomage(): ?string
    {
        return $this->taux_chomage;
    }

    public function setTauxChomage(string $taux_chomage): static
    {
        $this->taux_chomage = $taux_chomage;
        return $this;
    }

    public function getTauxPauvrete(): ?string
    {
        return $this->taux_pauvrete;
    }

    public function setTauxPauvrete(string $taux_pauvrete): static
    {
        $this->taux_pauvrete = $taux_pauvrete;
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