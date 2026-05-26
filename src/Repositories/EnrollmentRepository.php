<?php

declare(strict_types=1);

namespace DDG\Repositories;

use DDG\Models\EnrollmentModel;
use PDO;

final class EnrollmentRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findById(int $id): ?EnrollmentModel
    {
        $stmt = $this->pdo->prepare('SELECT * FROM enrollments WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : EnrollmentModel::fromRow($row);
    }

    public function existsByUserAndCourse(int $userId, int $courseId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM enrollments WHERE user_id = :user_id AND course_id = :course_id LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId, 'course_id' => $courseId]);
        return $stmt->fetchColumn() !== false;
    }

    public function create(int $userId, int $classId, int $courseId): EnrollmentModel
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO enrollments (user_id, class_id, course_id) VALUES (:user_id, :class_id, :course_id)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'class_id' => $classId,
            'course_id' => $courseId,
        ]);
        $id = (int) $this->pdo->lastInsertId();
        return $this->findById($id) ?? throw new \RuntimeException('Falha ao recuperar matrícula recém-criada.');
    }

    /**
     * Retorna cursos em que o usuário está matriculado (com a turma específica da matrícula).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listCoursesByUser(int $userId): array
    {
        $sql = 'SELECT
                    e.id AS enrollment_id,
                    e.created_at AS enrolled_at,
                    c.id AS course_id,
                    c.title AS course_title,
                    c.description AS course_description,
                    c.theme AS course_theme,
                    c.image_url AS course_image_url,
                    cl.id AS class_id,
                    cl.title AS class_title,
                    cl.status AS class_status,
                    cl.start_date AS class_start_date,
                    cl.end_date AS class_end_date,
                    cl.seats AS class_seats
                  FROM enrollments e
                  INNER JOIN courses c ON c.id = e.course_id
                  INNER JOIN classes cl ON cl.id = e.class_id
                 WHERE e.user_id = :user_id
                 ORDER BY e.created_at DESC, e.id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }
}
