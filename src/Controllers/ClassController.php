<?php

declare(strict_types=1);

namespace DDG\Controllers;

use DDG\Http\Request;
use DDG\Http\Response;
use DDG\Services\ClassService;
use DDG\Support\Validator;

final class ClassController
{
    public function __construct(private readonly ClassService $service)
    {
    }

    /**
     * @param array<string, string> $params
     */
    public function create(Request $request, array $params): Response
    {
        $courseId = Validator::requireIdParam($params, 'courseId');
        $class = $this->service->create($courseId, $request->body);
        return Response::created(['data' => $class->toArray()]);
    }

    /**
     * @param array<string, string> $params
     */
    public function update(Request $request, array $params): Response
    {
        $courseId = Validator::requireIdParam($params, 'courseId');
        $id = Validator::requireIdParam($params, 'id');
        $class = $this->service->update($courseId, $id, $request->body);
        return Response::json(['data' => $class->toArray()]);
    }

    /**
     * @param array<string, string> $params
     */
    public function delete(Request $request, array $params): Response
    {
        $courseId = Validator::requireIdParam($params, 'courseId');
        $id = Validator::requireIdParam($params, 'id');
        $this->service->delete($courseId, $id);
        return Response::noContent();
    }
}
