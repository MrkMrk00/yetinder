<?php

namespace App\Entity;

use App\Repository\YetiRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: YetiRepository::class)]
class Yeti
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\Length(min: 1, max: 255)]
//    #[Assert\Regex(
//        pattern: '/[\x{00C1}-\x{017E}A-Za-z ]/',
//        message: '',
//        match: true
//    )] TODO: aby ten regex fungoval
    private ?string $name;

    #[ORM\Column(type: 'integer')]
    #[Assert\GreaterThan(50)]
    #[Assert\LessThan(2000)]
    private ?int $weight;

    #[ORM\Column(type: 'integer')]
    #[Assert\GreaterThan(50)]
    #[Assert\LessThan(350)]
    private ?int $height;

    #[ORM\Column(type: 'integer')]
    #[Assert\All([
        new Assert\GreaterThan(0),
        new Assert\LessThan(120),
    ])]
    private ?int $age;

    #[ORM\ManyToOne(targetEntity: Color::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Color $color;

    #[ORM\OneToMany(mappedBy: 'yeti', targetEntity: Review::class, orphanRemoval: true)]
    #[Ignore]
    private $reviews;

    #[ORM\Column(type: 'string', length: 6)]
    #[Assert\Choice(['male', 'female'])]
    private ?string $sex;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[Assert\All([
        new Assert\NotBlank(),
        new Assert\NotNull(),
    ])]
    private ?User $createdBy;

    public function __toString(): string {
        return "ID$this->id - $this->color $this->name";
    }


    public function __construct()
    {
        $this->reviews = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getWeight(): ?int
    {
        return $this->weight;
    }

    public function setWeight(int $weight): self
    {
        $this->weight = $weight;

        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(int $height): self
    {
        $this->height = $height;

        return $this;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(int $age): self
    {
        $this->age = $age;

        return $this;
    }

    public function getColor(): ?Color
    {
        return $this->color;
    }

    public function setColor(?Color $color): self
    {
        $this->color = $color;

        return $this;
    }

    /**
     * @return Collection<int, Review>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(Review $review): self
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews[] = $review;
            $review->setYeti($this);
        }

        return $this;
    }

    public function removeReview(Review $review): self
    {
        if ($this->reviews->removeElement($review)) {
            // set the owning side to null (unless already changed)
            if ($review->getYeti() === $this) {
                $review->setYeti(null);
            }
        }

        return $this;
    }

    public function getSex(): ?string
    {
        return $this->sex;
    }

    public function setSex(string $sex): self
    {
        $this->sex = $sex;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }
}
