<?php

declare(strict_types=1);

namespace DDG\Models;

final readonly class EnrollmentModel
{
    public function __construct(
        public int $id,
        public int $userId,
        public int $classId,
        public int $courseId,
        public string $createdAt,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            userId: (int) $row['user_id'],
            classId: (int) $row['class_id'],
            courseId: (int) $row['course_id'],
            createdAt: (string) $row['created_at'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'class_id' => $this->classId,
            'course_id' => $this->courseId,
            'created_at' => $this->createdAt,
        ];
    }
}
