<?php

declare(strict_types=1);

namespace DDG\Repositories;

use DDG\Enums\CourseTheme;
use DDG\Models\CourseModel;
use PDO;

final class CourseRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findById(int $id): ?CourseModel
    {
        $stmt = $this->pdo->prepare('SELECT * FROM courses WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : CourseModel::fromRow($row);
    }

    public function create(string $title, string $description, CourseTheme $theme, string $imageUrl): CourseModel
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO courses (title, description, theme, image_url) VALUES (:title, :description, :theme, :image_url)'
        );
        $stmt->execute([
            'title' => $title,
            'description' => $description,
            'theme' => $theme->value,
            'image_url' => $imageUrl,
        ]);
        $id = (int) $this->pdo->lastInsertId();
        return $this->findById($id) ?? throw new \RuntimeException('Falha ao recuperar curso recém-criado.');
    }

    public function update(int $id, string $title, string $description, CourseTheme $theme, string $imageUrl): CourseModel
    {
        $stmt = $this->pdo->prepare(
            'UPDATE courses
                SET title = :title,
                    description = :description,
                    theme = :theme,
                    image_url = :image_url,
                    updated_at = datetime(\'now\')
              WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'title' => $title,
            'description' => $description,
            'theme' => $theme->value,
            'image_url' => $imageUrl,
        ]);
        return $this->findById($id) ?? throw new \RuntimeException('Falha ao recuperar curso atualizado.');
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM courses WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Cursos com pelo menos uma turma "disponivel".
     *
     * @return array<int, CourseModel>
     */
    public function listAvailable(?string $titleFilter = null, ?CourseTheme $themeFilter = null): array
    {
        $sql = 'SELECT DISTINCT c.*
                  FROM courses c
                  INNER JOIN classes cl ON cl.course_id = c.id
                 WHERE cl.status = :status';
        $bindings = ['status' => 'disponivel'];

        if ($titleFilter !== null && $titleFilter !== '') {
            $sql .= ' AND LOWER(c.title) LIKE :title';
            $bindings['title'] = '%' . strtolower($titleFilter) . '%';
        }

        if ($themeFilter !== null) {
            $sql .= ' AND c.theme = :theme';
            $bindings['theme'] = $themeFilter->value;
        }

        $sql .= ' ORDER BY c.id ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);

        $rows = $stmt->fetchAll();
        return array_map(static fn (array $row) => CourseModel::fromRow($row), $rows);
    }
}
