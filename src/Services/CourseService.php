<?php

declare(strict_types=1);

namespace DDG\Services;

use DDG\Enums\CourseTheme;
use DDG\Exceptions\NotFoundException;
use DDG\Models\CourseModel;
use DDG\Repositories\CourseRepository;
use DDG\Support\Validator;

final class CourseService
{
    public function __construct(private readonly CourseRepository $repository)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): CourseModel
    {
        $title = Validator::requireString($data, 'title', 200);
        $description = Validator::requireText($data, 'description');
        $theme = CourseTheme::fromInput($data['theme'] ?? null);
        $imageUrl = Validator::requireUrl($data, 'image_url');

        return $this->repository->create($title, $description, $theme, $imageUrl);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): CourseModel
    {
        $existing = $this->repository->findById($id);
        if ($existing === null) {
            throw new NotFoundException(sprintf('Curso %d não encontrado.', $id));
        }

        $title = Validator::requireString($data, 'title', 200);
        $description = Validator::requireText($data, 'description');
        $theme = CourseTheme::fromInput($data['theme'] ?? null);
        $imageUrl = Validator::requireUrl($data, 'image_url');

        return $this->repository->update($id, $title, $description, $theme, $imageUrl);
    }

    public function delete(int $id): void
    {
        $deleted = $this->repository->delete($id);
        if (!$deleted) {
            throw new NotFoundException(sprintf('Curso %d não encontrado.', $id));
        }
    }

    /**
     * @return array<int, CourseModel>
     */
    public function listAvailable(?string $titleFilter, ?string $themeFilter): array
    {
        $theme = null;
        if ($themeFilter !== null && $themeFilter !== '') {
            $theme = CourseTheme::fromInput($themeFilter);
        }
        return $this->repository->listAvailable($titleFilter, $theme);
    }
}
