<?php

namespace App\Entity;

use App\Repository\SaleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SaleRepository::class)]
class Sale
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private int $total = 0;

    #[ORM\Column(length: 255)]
    private string $state;

    /**
     * @var Collection<int, LineSale>
     */
    #[ORM\OneToMany(targetEntity: LineSale::class, mappedBy: 'sale', cascade: ['persist'])]
    private Collection $linesSales;

    public function __construct()
    {
        $this->linesSales = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    //    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    //    {
    //        $this->createdAt = $createdAt;
    //
    //        return $this;
    //    }

    public function getTotal(): \App\ValueObject\Money
    {
        //        return $this->total;
        return new \App\ValueObject\Money($this->total);
    }

    //    public function setTotal(int $total): static
    //    {
    //        $this->total = $total;
    //
    //        return $this;
    //    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): static
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return Collection<int, LineSale>
     */
    public function getLinesSales(): Collection
    {
        return $this->linesSales;
    }

    public function addLineSale(LineSale $lineSale): static
    {
        if (!$this->linesSales->contains($lineSale)) {
            $this->linesSales->add($lineSale);
            $lineSale->setSale($this);
            $total = new \App\ValueObject\Money($this->total)->add($lineSale->getTotal());
            $this->total = $total->getValue();
        }

        return $this;
    }

    public function removeLineSale(LineSale $lineSale): static
    {
        if ($this->linesSales->removeElement($lineSale)) {
            // set the owning side to null (unless already changed)
            if ($lineSale->getSale() === $this) {
                $lineSale->setSale(null);

                $total = new \App\ValueObject\Money($this->total)->subtract($lineSale->getTotal());
                $this->total = $total->getValue();
            }
        }

        return $this;
    }
}
