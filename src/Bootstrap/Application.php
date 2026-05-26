<?php

declare(strict_types=1);

namespace DDG\Bootstrap;

use DDG\Controllers\ClassController;
use DDG\Controllers\CourseController;
use DDG\Controllers\EnrollmentController;
use DDG\Controllers\UserController;
use DDG\Exceptions\AppException;
use DDG\Http\Request;
use DDG\Http\Response;
use DDG\Repositories\ClassRepository;
use DDG\Repositories\CourseRepository;
use DDG\Repositories\EnrollmentRepository;
use DDG\Repositories\UserRepository;
use DDG\Router;
use DDG\Services\ClassService;
use DDG\Services\CourseService;
use DDG\Services\EnrollmentService;
use DDG\Services\UserService;
use PDO;
use Throwable;

final class Application
{
    private Router $router;

    public function __construct(?PDO $pdo = null)
    {
        $pdo ??= Database::connect();
        $this->router = $this->buildRouter($pdo);
    }

    public function run(): void
    {
        try {
            $request = Request::fromGlobals();
            $response = $this->handle($request);
        } catch (AppException $e) {
            $response = Response::json([
                'error' => $e->errorCode(),
                'message' => $e->getMessage(),
            ], $e->httpStatus());
        } catch (Throwable $e) {
            $response = Response::json([
                'error' => 'internal_server_error',
                'message' => 'Erro interno do servidor.',
            ], 500);
        }

        $response->send();
    }

    public function handle(Request $request): Response
    {
        try {
            return $this->router->dispatch($request);
        } catch (AppException $e) {
            return Response::json([
                'error' => $e->errorCode(),
                'message' => $e->getMessage(),
            ], $e->httpStatus());
        }
    }

    private function buildRouter(PDO $pdo): Router
    {
        $router = new Router();

        $courseRepo = new CourseRepository($pdo);
        $classRepo = new ClassRepository($pdo);
        $userRepo = new UserRepository($pdo);
        $enrollmentRepo = new EnrollmentRepository($pdo);

        $courseService = new CourseService($courseRepo);
        $classService = new ClassService($classRepo, $courseRepo);
        $userService = new UserService($userRepo);
        $enrollmentService = new EnrollmentService(
            $enrollmentRepo,
            $classRepo,
            $userRepo,
            $courseRepo,
        );

        $courseController = new CourseController($courseService);
        $classController = new ClassController($classService);
        $userController = new UserController($userService);
        $enrollmentController = new EnrollmentController($enrollmentService);

        $router->get('/', static fn () => Response::json([
            'data' => [
                'name' => 'DDG Backend API',
                'version' => '1.0.0',
                'docs' => '/docs',
            ],
        ]));

        $router->get('/docs', static function (): Response {
            $html = file_get_contents(dirname(__DIR__, 2) . '/public/docs.html');
            return new Response(200, $html ?: '', ['Content-Type' => 'text/html; charset=utf-8']);
        });

        $router->get('/openapi.yaml', static function (): Response {
            $yaml = file_get_contents(dirname(__DIR__, 2) . '/public/openapi.yaml');
            return new Response(200, $yaml ?: '', ['Content-Type' => 'application/yaml; charset=utf-8']);
        });

        $router->post('/courses', fn (Request $r) => $courseController->create($r));
        $router->get('/courses', fn (Request $r) => $courseController->listAvailable($r));
        $router->put('/courses/{id}', fn (Request $r, array $p) => $courseController->update($r, $p));
        $router->delete('/courses/{id}', fn (Request $r, array $p) => $courseController->delete($r, $p));

        $router->post('/courses/{courseId}/classes', fn (Request $r, array $p) => $classController->create($r, $p));
        $router->put('/courses/{courseId}/classes/{id}', fn (Request $r, array $p) => $classController->update($r, $p));
        $router->delete('/courses/{courseId}/classes/{id}', fn (Request $r, array $p) => $classController->delete($r, $p));

        $router->post('/users', fn (Request $r) => $userController->create($r));
        $router->delete('/users/{id}', fn (Request $r, array $p) => $userController->delete($r, $p));
        $router->get('/users/{id}/enrollments', fn (Request $r, array $p) => $enrollmentController->listByUser($r, $p));

        $router->post('/enrollments', fn (Request $r) => $enrollmentController->create($r));

        return $router;
    }
}
