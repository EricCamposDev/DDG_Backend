<?php

declare(strict_types=1);

namespace DDG\Services;

use DDG\Enums\ClassStatus;
use DDG\Exceptions\NotFoundException;
use DDG\Exceptions\ValidationException;
use DDG\Models\ClassModel;
use DDG\Repositories\ClassRepository;
use DDG\Repositories\CourseRepository;
use DDG\Support\Validator;

final class ClassService
{
    public function __construct(
        private readonly ClassRepository $classRepository,
        private readonly CourseRepository $courseRepository,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(int $courseId, array $data): ClassModel
    {
        $this->ensureCourseExists($courseId);

        $title = Validator::requireString($data, 'title', 200);
        $description = Validator::requireText($data, 'description');
        $seats = Validator::requireInt($data, 'seats', 0, 100000);
        $status = ClassStatus::fromInput($data['status'] ?? null);
        $startDate = Validator::requireDate($data, 'start_date');
        $endDate = Validator::requireDate($data, 'end_date');

        $this->ensureValidDateRange($startDate, $endDate);

        return $this->classRepository->create(
            $courseId,
            $title,
            $description,
            $seats,
            $status,
            $startDate,
            $endDate,
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $courseId, int $id, array $data): ClassModel
    {
        $this->ensureCourseExists($courseId);

        $existing = $this->classRepository->findByIdAndCourse($id, $courseId);
        if ($existing === null) {
            throw new NotFoundException(sprintf('Turma %d do curso %d não encontrada.', $id, $courseId));
        }

        $title = Validator::requireString($data, 'title', 200);
        $description = Validator::requireText($data, 'description');
        $seats = Validator::requireInt($data, 'seats', 0, 100000);
        $status = ClassStatus::fromInput($data['status'] ?? null);
        $startDate = Validator::requireDate($data, 'start_date');
        $endDate = Validator::requireDate($data, 'end_date');

        $this->ensureValidDateRange($startDate, $endDate);

        $currentEnrollments = $this->classRepository->countEnrollments($id);
        if ($seats < $currentEnrollments) {
            throw new ValidationException(sprintf(
                'A turma já possui %d matrículas e não pode ter menos vagas que isso.',
                $currentEnrollments
            ));
        }

        return $this->classRepository->update(
            $id,
            $title,
            $description,
            $seats,
            $status,
            $startDate,
            $endDate,
        );
    }

    public function delete(int $courseId, int $id): void
    {
        $this->ensureCourseExists($courseId);

        $existing = $this->classRepository->findByIdAndCourse($id, $courseId);
        if ($existing === null) {
            throw new NotFoundException(sprintf('Turma %d do curso %d não encontrada.', $id, $courseId));
        }

        $this->classRepository->delete($id);
    }

    private function ensureCourseExists(int $courseId): void
    {
        if ($this->courseRepository->findById($courseId) === null) {
            throw new NotFoundException(sprintf('Curso %d não encontrado.', $courseId));
        }
    }

    private function ensureValidDateRange(string $startDate, string $endDate): void
    {
        if (strtotime($startDate) > strtotime($endDate)) {
            throw new ValidationException('Campo "start_date" deve ser anterior ou igual a "end_date".');
        }
    }
}
