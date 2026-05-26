<?php

declare(strict_types=1);

namespace DDG\Services;

use DateTimeImmutable;
use DDG\Enums\ClassStatus;
use DDG\Exceptions\EnrollmentException;
use DDG\Exceptions\NotFoundException;
use DDG\Models\EnrollmentModel;
use DDG\Repositories\ClassRepository;
use DDG\Repositories\CourseRepository;
use DDG\Repositories\EnrollmentRepository;
use DDG\Repositories\UserRepository;
use DDG\Support\Validator;

final class EnrollmentService
{
    public function __construct(
        private readonly EnrollmentRepository $enrollmentRepository,
        private readonly ClassRepository $classRepository,
        private readonly UserRepository $userRepository,
        private readonly CourseRepository $courseRepository,
        private readonly ?DateTimeImmutable $today = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function enroll(array $data): EnrollmentModel
    {
        $userId = Validator::requireInt($data, 'user_id', 1);
        $classId = Validator::requireInt($data, 'class_id', 1);

        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            throw new NotFoundException(sprintf('Usuário %d não encontrado.', $userId));
        }

        $class = $this->classRepository->findById($classId);
        if ($class === null) {
            throw new NotFoundException(sprintf('Turma %d não encontrada.', $classId));
        }

        if ($class->status !== ClassStatus::Disponivel) {
            throw new EnrollmentException('Não é permitido matricular usuário em turma encerrada.');
        }

        $today = ($this->today ?? new DateTimeImmutable('today'))->format('Y-m-d');
        if ($today < $class->startDate || $today > $class->endDate) {
            throw new EnrollmentException(sprintf(
                'A turma está fora do período de vigência (%s a %s).',
                $class->startDate,
                $class->endDate,
            ));
        }

        if ($this->enrollmentRepository->existsByUserAndCourse($userId, $class->courseId)) {
            throw new EnrollmentException('Usuário já está matriculado em uma turma deste curso.');
        }

        $occupied = $this->classRepository->countEnrollments($classId);
        if ($occupied >= $class->seats) {
            throw new EnrollmentException('A turma não possui vagas disponíveis.');
        }

        return $this->enrollmentRepository->create($userId, $classId, $class->courseId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listCoursesByUser(int $userId): array
    {
        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            throw new NotFoundException(sprintf('Usuário %d não encontrado.', $userId));
        }

        $rows = $this->enrollmentRepository->listCoursesByUser($userId);

        return array_map(static fn (array $row) => [
            'enrollment_id' => (int) $row['enrollment_id'],
            'enrolled_at' => (string) $row['enrolled_at'],
            'course' => [
                'id' => (int) $row['course_id'],
                'title' => (string) $row['course_title'],
                'description' => (string) $row['course_description'],
                'theme' => (string) $row['course_theme'],
                'image_url' => (string) $row['course_image_url'],
            ],
            'class' => [
                'id' => (int) $row['class_id'],
                'title' => (string) $row['class_title'],
                'status' => (string) $row['class_status'],
                'start_date' => (string) $row['class_start_date'],
                'end_date' => (string) $row['class_end_date'],
                'seats' => (int) $row['class_seats'],
            ],
        ], $rows);
    }
}
