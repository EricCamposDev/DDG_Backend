<?php

declare(strict_types=1);

namespace DDG\Controllers;

use DDG\Http\Request;
use DDG\Http\Response;
use DDG\Services\EnrollmentService;
use DDG\Support\Validator;

final class EnrollmentController
{
    public function __construct(private readonly EnrollmentService $service)
    {
    }

    public function create(Request $request): Response
    {
        $enrollment = $this->service->enroll($request->body);
        return Response::created(['data' => $enrollment->toArray()]);
    }

    /**
     * @param array<string, string> $params
     */
    public function listByUser(Request $request, array $params): Response
    {
        $userId = Validator::requireIdParam($params);
        $enrollments = $this->service->listCoursesByUser($userId);
        return Response::json(['data' => $enrollments]);
    }
}
