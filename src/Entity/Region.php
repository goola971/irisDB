<?php

namespace App\Entity;

use App\Repository\RegionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
// Import indispensable pour couper la boucle infinie
use Symfony\Component\Serializer\Annotation\Ignore;

#[ApiResource]
#[ORM\Entity(repositoryClass: RegionRepository::class)]
class Region
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $code_region = null;

    #[ORM\Column(length: 100)]
    private ?string $nom_region = null;

    #[Ignore]
    #[ORM\ManyToOne(inversedBy: 'regions')]
    private ?Admin $idAdmin = null;

    #[Ignore]
    #[ORM\OneToMany(targetEntity: Departement::class, mappedBy: 'id_region')]
    private Collection $departements;

    public function __construct()
    {
        $this->departements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodeRegion(): ?int
    {
        return $this->code_region;
    }

    public function setCodeRegion(int $code_region): static
    {
        $this->code_region = $code_region;
        return $this;
    }

    public function getNomRegion(): ?string
    {
        return $this->nom_region;
    }

    public function setNomRegion(string $nom_region): static
    {
        $this->nom_region = $nom_region;
        return $this;
    }

    public function getIdAdmin(): ?Admin
    {
        return $this->idAdmin;
    }

    public function setIdAdmin(?Admin $idAdmin): static
    {
        $this->idAdmin = $idAdmin;
        return $this;
    }

    public function getDepartements(): Collection
    {
        return $this->departements;
    }
}