<?php

declare(strict_types=1);

namespace DDG\Repositories;

use DDG\Enums\ClassStatus;
use DDG\Models\ClassModel;
use PDO;

final class ClassRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findById(int $id): ?ClassModel
    {
        $stmt = $this->pdo->prepare('SELECT * FROM classes WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : ClassModel::fromRow($row);
    }

    public function findByIdAndCourse(int $id, int $courseId): ?ClassModel
    {
        $stmt = $this->pdo->prepare('SELECT * FROM classes WHERE id = :id AND course_id = :course_id');
        $stmt->execute(['id' => $id, 'course_id' => $courseId]);
        $row = $stmt->fetch();
        return $row === false ? null : ClassModel::fromRow($row);
    }

    public function create(
        int $courseId,
        string $title,
        string $description,
        int $seats,
        ClassStatus $status,
        string $startDate,
        string $endDate,
    ): ClassModel {
        $stmt = $this->pdo->prepare(
            'INSERT INTO classes (course_id, title, description, seats, status, start_date, end_date)
             VALUES (:course_id, :title, :description, :seats, :status, :start_date, :end_date)'
        );
        $stmt->execute([
            'course_id' => $courseId,
            'title' => $title,
            'description' => $description,
            'seats' => $seats,
            'status' => $status->value,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
        $id = (int) $this->pdo->lastInsertId();
        return $this->findById($id) ?? throw new \RuntimeException('Falha ao recuperar turma recém-criada.');
    }

    public function update(
        int $id,
        string $title,
        string $description,
        int $seats,
        ClassStatus $status,
        string $startDate,
        string $endDate,
    ): ClassModel {
        $stmt = $this->pdo->prepare(
            'UPDATE classes
                SET title = :title,
                    description = :description,
                    seats = :seats,
                    status = :status,
                    start_date = :start_date,
                    end_date = :end_date,
                    updated_at = datetime(\'now\')
              WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'title' => $title,
            'description' => $description,
            'seats' => $seats,
            'status' => $status->value,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
        return $this->findById($id) ?? throw new \RuntimeException('Falha ao recuperar turma atualizada.');
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM classes WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function countEnrollments(int $classId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM enrollments WHERE class_id = :class_id');
        $stmt->execute(['class_id' => $classId]);
        return (int) $stmt->fetchColumn();
    }
}
