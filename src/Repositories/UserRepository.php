<?php

declare(strict_types=1);

namespace DDG\Repositories;

use DDG\Models\UserModel;
use PDO;

final class UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findById(int $id): ?UserModel
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : UserModel::fromRow($row);
    }

    public function findByEmail(string $email): ?UserModel
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row === false ? null : UserModel::fromRow($row);
    }

    public function create(string $name, string $email): UserModel
    {
        $stmt = $this->pdo->prepare('INSERT INTO users (name, email) VALUES (:name, :email)');
        $stmt->execute(['name' => $name, 'email' => $email]);
        $id = (int) $this->pdo->lastInsertId();
        return $this->findById($id) ?? throw new \RuntimeException('Falha ao recuperar usuário recém-criado.');
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
