<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\PerformanceReportRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PerformanceReportRepository::class)]
#[ApiResource]
class PerformanceReport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'performanceReports')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $employee = null;

    #[ORM\Column(type: 'integer')]
    private ?int $week = null;

    #[ORM\Column(type: 'integer')]
    private ?int $year = null;

    #[ORM\Column(type: 'float')]
    private ?float $score = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $pdfPath = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $tasksCompleted = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $tasksTotal = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $goalsCompleted = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $goalsTotal = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $taskCompletionRate = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $goalCompletionRate = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $onTimeCompletionRate = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $averageTaskCompletionTime = null; // in hours

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $averagePriority = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $summary = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $generatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getEmployee(): ?User
    {
        return $this->employee;
    }

    public function setEmployee(?User $employee): void
    {
        $this->employee = $employee;
    }

    public function getWeek(): ?int
    {
        return $this->week;
    }

    public function setWeek(?int $week): void
    {
        $this->week = $week;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(?int $year): void
    {
        $this->year = $year;
    }

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function setScore(?float $score): void
    {
        $this->score = $score;
    }

    public function getPdfPath(): ?string
    {
        return $this->pdfPath;
    }

    public function setPdfPath(?string $pdfPath): void
    {
        $this->pdfPath = $pdfPath;
    }

    public function getTasksCompleted(): ?int
    {
        return $this->tasksCompleted;
    }

    public function setTasksCompleted(?int $tasksCompleted): void
    {
        $this->tasksCompleted = $tasksCompleted;
    }

    public function getTasksTotal(): ?int
    {
        return $this->tasksTotal;
    }

    public function setTasksTotal(?int $tasksTotal): void
    {
        $this->tasksTotal = $tasksTotal;
    }

    public function getGoalsCompleted(): ?int
    {
        return $this->goalsCompleted;
    }

    public function setGoalsCompleted(?int $goalsCompleted): void
    {
        $this->goalsCompleted = $goalsCompleted;
    }

    public function getGoalsTotal(): ?int
    {
        return $this->goalsTotal;
    }

    public function setGoalsTotal(?int $goalsTotal): void
    {
        $this->goalsTotal = $goalsTotal;
    }

    public function getTaskCompletionRate(): ?float
    {
        return $this->taskCompletionRate;
    }

    public function setTaskCompletionRate(?float $taskCompletionRate): void
    {
        $this->taskCompletionRate = $taskCompletionRate;
    }

    public function getGoalCompletionRate(): ?float
    {
        return $this->goalCompletionRate;
    }

    public function setGoalCompletionRate(?float $goalCompletionRate): void
    {
        $this->goalCompletionRate = $goalCompletionRate;
    }

    public function getOnTimeCompletionRate(): ?float
    {
        return $this->onTimeCompletionRate;
    }

    public function setOnTimeCompletionRate(?float $onTimeCompletionRate): void
    {
        $this->onTimeCompletionRate = $onTimeCompletionRate;
    }

    public function getAverageTaskCompletionTime(): ?float
    {
        return $this->averageTaskCompletionTime;
    }

    public function setAverageTaskCompletionTime(?float $averageTaskCompletionTime): void
    {
        $this->averageTaskCompletionTime = $averageTaskCompletionTime;
    }

    public function getAveragePriority(): ?float
    {
        return $this->averagePriority;
    }

    public function setAveragePriority(?float $averagePriority): void
    {
        $this->averagePriority = $averagePriority;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): void
    {
        $this->summary = $summary;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getGeneratedAt(): ?\DateTimeImmutable
    {
        return $this->generatedAt;
    }

    public function setGeneratedAt(?\DateTimeImmutable $generatedAt): void
    {
        $this->generatedAt = $generatedAt;
    }

    public function __toString(): string
    {
        return $this->getEmployee() ?? '';
    }
}
