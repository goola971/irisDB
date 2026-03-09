<?php

namespace App\Entity;

use App\Repository\DepartementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;

#[ApiResource]
#[ORM\Entity(repositoryClass: DepartementRepository::class)]
class Departement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 5)]
    private ?string $code_departement = null;

    #[ORM\Column(length: 100)]
    private ?string $nom_departement = null;

    #[ORM\ManyToOne(inversedBy: 'departements')]
    private ?Region $id_region = null;

    /**
     * @var Collection<int, Demographie>
     */
    #[ORM\OneToMany(targetEntity: Demographie::class, mappedBy: 'id_departement')]
    private Collection $demographies;

    /**
     * @var Collection<int, Economie>
     */
    #[ORM\OneToMany(targetEntity: Economie::class, mappedBy: 'id_departement')]
    private Collection $economies;

    /**
     * @var Collection<int, Logement>
     */
    #[ORM\OneToMany(targetEntity: Logement::class, mappedBy: 'id_departement')]
    private Collection $logements;

    public function __construct()
    {
        $this->demographies = new ArrayCollection();
        $this->economies = new ArrayCollection();
        $this->logements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodeDepartement(): ?string
    {
        return $this->code_departement;
    }

    public function setCodeDepartement(string $code_departement): static
    {
        $this->code_departement = $code_departement;

        return $this;
    }

    public function getNomDepartement(): ?string
    {
        return $this->nom_departement;
    }

    public function setNomDepartement(string $nom_departement): static
    {
        $this->nom_departement = $nom_departement;

        return $this;
    }

    public function getIdRegion(): ?Region
    {
        return $this->id_region;
    }

    public function setIdRegion(?Region $id_region): static
    {
        $this->id_region = $id_region;

        return $this;
    }

    /**
     * @return Collection<int, Demographie>
     */
    public function getDemographies(): Collection
    {
        return $this->demographies;
    }

    public function addDemography(Demographie $demography): static
    {
        if (!$this->demographies->contains($demography)) {
            $this->demographies->add($demography);
            $demography->setIdDepartement($this);
        }

        return $this;
    }

    public function removeDemography(Demographie $demography): static
    {
        if ($this->demographies->removeElement($demography)) {
            // set the owning side to null (unless already changed)
            if ($demography->getIdDepartement() === $this) {
                $demography->setIdDepartement(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Economie>
     */
    public function getEconomies(): Collection
    {
        return $this->economies;
    }

    public function addEconomy(Economie $economy): static
    {
        if (!$this->economies->contains($economy)) {
            $this->economies->add($economy);
            $economy->setIdDepartement($this);
        }

        return $this;
    }

    public function removeEconomy(Economie $economy): static
    {
        if ($this->economies->removeElement($economy)) {
            // set the owning side to null (unless already changed)
            if ($economy->getIdDepartement() === $this) {
                $economy->setIdDepartement(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Logement>
     */
    public function getLogements(): Collection
    {
        return $this->logements;
    }

    public function addLogement(Logement $logement): static
    {
        if (!$this->logements->contains($logement)) {
            $this->logements->add($logement);
            $logement->setIdDepartement($this);
        }

        return $this;
    }

    public function removeLogement(Logement $logement): static
    {
        if ($this->logements->removeElement($logement)) {
            // set the owning side to null (unless already changed)
            if ($logement->getIdDepartement() === $this) {
                $logement->setIdDepartement(null);
            }
        }

        return $this;
    }
}
