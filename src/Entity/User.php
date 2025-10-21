<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $firstName = '';

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $lastName = '';

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank, Assert\Email]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    protected ?string $plainPassword = null;

    #[ORM\ManyToOne(targetEntity: Team::class, inversedBy: 'members')]
    protected ?Team $team = null;

    #[ORM\OneToMany(targetEntity: Task::class, mappedBy: 'assignedTo')]
    protected Collection $assignedTasks;

    #[ORM\OneToMany(targetEntity: Task::class, mappedBy: 'createdBy')]
    protected Collection $createdTasks;

    #[ORM\OneToMany(targetEntity: Goal::class, mappedBy: 'employee', cascade: ['persist', 'remove'])]
    private Collection $goals;

    #[ORM\OneToMany(targetEntity: PerformanceReport::class, mappedBy: 'employee', cascade: ['persist', 'remove'])]
    private Collection $performanceReports;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->assignedTasks = new ArrayCollection();
        $this->createdTasks = new ArrayCollection();
        $this->goals = new ArrayCollection();
        $this->performanceReports = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): void
    {
        $this->plainPassword = $plainPassword;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): void
    {
        $this->team = $team;
    }

    public function getAssignedTasks(): Collection
    {
        return $this->assignedTasks;
    }

    public function setAssignedTasks(Collection $assignedTasks): void
    {
        $this->assignedTasks = $assignedTasks;
    }

    public function getCreatedTasks(): Collection
    {
        return $this->createdTasks;
    }

    public function setCreatedTasks(Collection $createdTasks): void
    {
        $this->createdTasks = $createdTasks;
    }

    public function getGoals(): Collection
    {
        return $this->goals;
    }

    public function setGoals(Collection $goals): void
    {
        $this->goals = $goals;
    }

    public function getPerformanceReports(): Collection
    {
        return $this->performanceReports;
    }

    public function setPerformanceReports(Collection $performanceReports): void
    {
        $this->performanceReports = $performanceReports;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    public function __toString(): string
    {
        return $this->firstName.' '.$this->lastName;
    }
}
