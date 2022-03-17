<?php

namespace App\Entity;

use App\Repository\ReviewRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReviewRepository::class)]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\ManyToOne(targetEntity: Yeti::class, inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Yeti $yeti;

    #[ORM\Column(type: 'integer', length: 1)]
    #[Assert\Choice([1, 0, -1])]
    private ?int $value;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $date;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getYeti(): ?Yeti
    {
        return $this->yeti;
    }

    public function setYeti(?Yeti $yeti): self
    {
        $this->yeti = $yeti;

        return $this;
    }

    public function getValue(): ?int
    {
        return $this->value;
    }

    public function setValue(int $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }
}
