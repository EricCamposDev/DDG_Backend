<?php

declare(strict_types=1);

namespace DDG\Controllers;

use DDG\Http\Request;
use DDG\Http\Response;
use DDG\Services\CourseService;
use DDG\Support\Validator;

final class CourseController
{
    public function __construct(private readonly CourseService $service)
    {
    }

    public function create(Request $request): Response
    {
        $course = $this->service->create($request->body);
        return Response::created(['data' => $course->toArray()]);
    }

    /**
     * @param array<string, string> $params
     */
    public function update(Request $request, array $params): Response
    {
        $id = Validator::requireIdParam($params);
        $course = $this->service->update($id, $request->body);
        return Response::json(['data' => $course->toArray()]);
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

    public function listAvailable(Request $request): Response
    {
        $title = $request->queryParam('title');
        $theme = $request->queryParam('theme');

        $title = is_string($title) ? $title : null;
        $theme = is_string($theme) ? $theme : null;

        $courses = $this->service->listAvailable($title, $theme);
        return Response::json([
            'data' => array_map(static fn ($c) => $c->toArray(), $courses),
        ]);
    }
}
