<?php

namespace App\Entity;

use App\Repository\AdviceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AdviceRepository::class)]
class Advice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le mois est obligatoire (de 1 à 12)')]
    #[Assert\Range(notInRangeMessage: "La valeur {{ value }} n'est pas dans la plage de valeur valide ({{ min }} - {{ max }})", min: 1, max: 12)]
    private ?int $month = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le texte pour un conseil est obligatoire')]
    private ?string $adviceText = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMonth(): ?int
    {
        return $this->month;
    }

    public function setMonth(int $month): static
    {
        $this->month = $month;

        return $this;
    }

    public function getAdviceText(): ?string
    {
        return $this->adviceText;
    }

    public function setAdviceText(string $adviceText): static
    {
        $this->adviceText = $adviceText;

        return $this;
    }
}
