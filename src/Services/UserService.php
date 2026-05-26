<?php

declare(strict_types=1);

namespace DDG\Services;

use DDG\Exceptions\ConflictException;
use DDG\Exceptions\NotFoundException;
use DDG\Models\UserModel;
use DDG\Repositories\UserRepository;
use DDG\Support\Validator;

final class UserService
{
    public function __construct(private readonly UserRepository $repository)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): UserModel
    {
        $name = Validator::requireString($data, 'name', 200);
        $email = Validator::requireEmail($data, 'email');

        if ($this->repository->findByEmail($email) !== null) {
            throw new ConflictException(sprintf('Já existe um usuário com o e-mail "%s".', $email));
        }

        return $this->repository->create($name, $email);
    }

    public function delete(int $id): void
    {
        $deleted = $this->repository->delete($id);
        if (!$deleted) {
            throw new NotFoundException(sprintf('Usuário %d não encontrado.', $id));
        }
    }
}
