<?php

declare(strict_types=1);

namespace DDG\Models;

use DDG\Enums\CourseTheme;

final readonly class CourseModel
{
    public function __construct(
        public int $id,
        public string $title,
        public string $description,
        public CourseTheme $theme,
        public string $imageUrl,
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
            title: (string) $row['title'],
            description: (string) $row['description'],
            theme: CourseTheme::from((string) $row['theme']),
            imageUrl: (string) $row['image_url'],
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
            'title' => $this->title,
            'description' => $this->description,
            'theme' => $this->theme->value,
            'image_url' => $this->imageUrl,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
