<?php

declare(strict_types=1);

namespace DDG\Models;

use DDG\Enums\ClassStatus;

final readonly class ClassModel
{
    public function __construct(
        public int $id,
        public int $courseId,
        public string $title,
        public string $description,
        public int $seats,
        public ClassStatus $status,
        public string $startDate,
        public string $endDate,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            courseId: (int) $row['course_id'],
            title: (string) $row['title'],
            description: (string) $row['description'],
            seats: (int) $row['seats'],
            status: ClassStatus::from((string) $row['status']),
            startDate: (string) $row['start_date'],
            endDate: (string) $row['end_date'],
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->courseId,
            'title' => $this->title,
            'description' => $this->description,
            'seats' => $this->seats,
            'status' => $this->status->value,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
