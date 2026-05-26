<?php

declare(strict_types=1);

namespace DDG\Controllers;

use DDG\Http\Request;
use DDG\Http\Response;
use DDG\Services\UserService;
use DDG\Support\Validator;

final class UserController
{
    public function __construct(private readonly UserService $service)
    {
    }

    public function create(Request $request): Response
    {
        $user = $this->service->create($request->body);
        return Response::created(['data' => $user->toArray()]);
    }

    /**
     * @param array<string, string> $params
     */
    public function delete(Request $request, array $params): Response
    {
        $id = Validator::requireIdParam($params);
        $this->service->delete($id);
        return Response::noContent();
    }
}
