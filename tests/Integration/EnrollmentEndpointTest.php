<?php

declare(strict_types=1);

namespace DDG\Tests\Integration;

use DDG\Bootstrap\Application;
use DDG\Http\Request;
use DDG\Tests\Support\TestDatabase;
use PDO;
use PHPUnit\Framework\TestCase;

final class EnrollmentEndpointTest extends TestCase
{
    private PDO $pdo;
    private Application $app;

    protected function setUp(): void
    {
        $this->pdo = TestDatabase::fresh();
        $this->app = new Application($this->pdo);
    }

    private function request(string $method, string $path, array $body = [], array $query = []): array
    {
        $response = $this->app->handle(new Request($method, $path, $query, $body, []));
        return ['status' => $response->status, 'body' => $response->body];
    }

    private function makeCourseWithOpenClass(string $startDate = '2020-01-01', string $endDate = '2099-12-31', int $seats = 10): array
    {
        $courseId = $this->request('POST', '/courses', [
            'title' => 'Curso',
            'description' => 'desc',
            'theme' => 'tecnologia',
            'image_url' => 'https://example.com/i.png',
        ])['body']['data']['id'];

        $classId = $this->request('POST', '/courses/' . $courseId . '/classes', [
            'title' => 'Turma',
            'description' => 'desc',
            'seats' => $seats,
            'status' => 'disponivel',
            'start_date' => $startDate,
            'end_date' => $endDate,
        ])['body']['data']['id'];

        return ['course_id' => $courseId, 'class_id' => $classId];
    }

    private function makeUser(string $email = 'a@example.com', string $name = 'Eric'): int
    {
        return $this->request('POST', '/users', [
            'name' => $name,
            'email' => $email,
        ])['body']['data']['id'];
    }

    public function testCreateUser(): void
    {
        $r = $this->request('POST', '/users', ['name' => 'Eric', 'email' => 'eric@example.com']);
        self::assertSame(201, $r['status']);
        self::assertSame('eric@example.com', $r['body']['data']['email']);
    }

    public function testCreateUserDuplicateEmailReturns409(): void
    {
        $this->makeUser();
        $r = $this->request('POST', '/users', ['name' => 'Eric 2', 'email' => 'a@example.com']);
        self::assertSame(409, $r['status']);
    }

    public function testEnrollSucceeds(): void
    {
        $ids = $this->makeCourseWithOpenClass();
        $userId = $this->makeUser();

        $r = $this->request('POST', '/enrollments', [
            'user_id' => $userId,
            'class_id' => $ids['class_id'],
        ]);
        self::assertSame(201, $r['status']);
        self::assertSame($userId, $r['body']['data']['user_id']);
    }

    public function testCannotEnrollTwiceInSameCourse(): void
    {
        $courseId = $this->request('POST', '/courses', [
            'title' => 'C', 'description' => 'd', 'theme' => 'agro', 'image_url' => 'https://example.com/x.png',
        ])['body']['data']['id'];
        $classA = $this->request('POST', '/courses/' . $courseId . '/classes', [
            'title' => 'A', 'description' => 'd', 'seats' => 5,
            'status' => 'disponivel', 'start_date' => '2020-01-01', 'end_date' => '2099-12-31',
        ])['body']['data']['id'];
        $classB = $this->request('POST', '/courses/' . $courseId . '/classes', [
            'title' => 'B', 'description' => 'd', 'seats' => 5,
            'status' => 'disponivel', 'start_date' => '2020-01-01', 'end_date' => '2099-12-31',
        ])['body']['data']['id'];

        $userId = $this->makeUser();
        $this->request('POST', '/enrollments', ['user_id' => $userId, 'class_id' => $classA]);
        $r = $this->request('POST', '/enrollments', ['user_id' => $userId, 'class_id' => $classB]);

        self::assertSame(422, $r['status']);
        self::assertSame('enrollment_error', $r['body']['error']);
    }

    public function testCannotEnrollInClosedClass(): void
    {
        $courseId = $this->request('POST', '/courses', [
            'title' => 'C', 'description' => 'd', 'theme' => 'agro', 'image_url' => 'https://example.com/x.png',
        ])['body']['data']['id'];
        $classId = $this->request('POST', '/courses/' . $courseId . '/classes', [
            'title' => 'T', 'description' => 'd', 'seats' => 5,
            'status' => 'encerrado', 'start_date' => '2020-01-01', 'end_date' => '2099-12-31',
        ])['body']['data']['id'];

        $userId = $this->makeUser();
        $r = $this->request('POST', '/enrollments', ['user_id' => $userId, 'class_id' => $classId]);
        self::assertSame(422, $r['status']);
    }

    public function testCannotEnrollOutsideDateRange(): void
    {
        $ids = $this->makeCourseWithOpenClass('1999-01-01', '1999-12-31');
        $userId = $this->makeUser();

        $r = $this->request('POST', '/enrollments', [
            'user_id' => $userId,
            'class_id' => $ids['class_id'],
        ]);
        self::assertSame(422, $r['status']);
    }

    public function testListEnrollmentsByUser(): void
    {
        $ids = $this->makeCourseWithOpenClass();
        $userId = $this->makeUser();
        $this->request('POST', '/enrollments', ['user_id' => $userId, 'class_id' => $ids['class_id']]);

        $r = $this->request('GET', '/users/' . $userId . '/enrollments');
        self::assertSame(200, $r['status']);
        self::assertCount(1, $r['body']['data']);
        self::assertSame($ids['course_id'], $r['body']['data'][0]['course']['id']);
    }

    public function testListEnrollmentsForUnknownUser(): void
    {
        $r = $this->request('GET', '/users/999/enrollments');
        self::assertSame(404, $r['status']);
    }
}
