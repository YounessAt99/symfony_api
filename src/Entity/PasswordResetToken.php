<?php

namespace App\Entity;

use App\Repository\PasswordResetTokenRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: PasswordResetTokenRepository::class)]
class PasswordResetToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $token = null;

    #[ORM\Column]
    private ?\DateTime $expiresAt = null;

    #[ORM\ManyToOne(inversedBy: 'passwordResetTokens')]
    private ?User $user = null;

    // 
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->token = Uuid::v4()->toRfc4122(); // stored token as a string
        $this->expiresAt = new \DateTime('+1 hour'); // Token expires in 1 hour
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    // public function setToken(string $token): static
    // {
    //     $this->token = $token;

    //     return $this;
    // }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    // public function setExpiresAt(\DateTimeImmutable $expiresAt): static
    // {
    //     $this->expiresAt = $expiresAt;

    //     return $this;
    // }

    public function getUser(): ?User
    {
        return $this->user;
    }

    // public function setUser(?User $user): static
    // {
    //     $this->user = $user;

    //     return $this;
    // }

    // 
    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTime();
    }
}
