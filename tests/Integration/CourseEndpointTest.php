<?php

declare(strict_types=1);

namespace DDG\Tests\Integration;

use DDG\Bootstrap\Application;
use DDG\Http\Request;
use DDG\Tests\Support\TestDatabase;
use PDO;
use PHPUnit\Framework\TestCase;

final class CourseEndpointTest extends TestCase
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
        $request = new Request($method, $path, $query, $body, []);
        $response = $this->app->handle($request);
        return [
            'status' => $response->status,
            'body' => $response->body,
        ];
    }

    public function testCreateAndListCourse(): void
    {
        $createResponse = $this->request('POST', '/courses', [
            'title' => 'Curso de PHP',
            'description' => 'desc',
            'theme' => 'tecnologia',
            'image_url' => 'https://example.com/img.png',
        ]);
        self::assertSame(201, $createResponse['status']);
        $courseId = $createResponse['body']['data']['id'];

        $this->request('POST', '/courses/' . $courseId . '/classes', [
            'title' => 'Turma 1',
            'description' => 'desc',
            'seats' => 10,
            'status' => 'disponivel',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $listResponse = $this->request('GET', '/courses');
        self::assertSame(200, $listResponse['status']);
        self::assertCount(1, $listResponse['body']['data']);
        self::assertSame('Curso de PHP', $listResponse['body']['data'][0]['title']);
    }

    public function testListCoursesFiltersByTheme(): void
    {
        $tech = $this->request('POST', '/courses', [
            'title' => 'PHP Avançado',
            'description' => 'desc',
            'theme' => 'tecnologia',
            'image_url' => 'https://example.com/a.png',
        ])['body']['data']['id'];

        $agro = $this->request('POST', '/courses', [
            'title' => 'Plantio Sustentável',
            'description' => 'desc',
            'theme' => 'agro',
            'image_url' => 'https://example.com/b.png',
        ])['body']['data']['id'];

        foreach ([$tech, $agro] as $cid) {
            $this->request('POST', '/courses/' . $cid . '/classes', [
                'title' => 'T',
                'description' => 'd',
                'seats' => 10,
                'status' => 'disponivel',
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
            ]);
        }

        $filtered = $this->request('GET', '/courses', [], ['theme' => 'agro']);
        self::assertSame(200, $filtered['status']);
        self::assertCount(1, $filtered['body']['data']);
        self::assertSame('Plantio Sustentável', $filtered['body']['data'][0]['title']);
    }

    public function testListCoursesFiltersByTitle(): void
    {
        $id = $this->request('POST', '/courses', [
            'title' => 'PHP Avançado',
            'description' => 'desc',
            'theme' => 'tecnologia',
            'image_url' => 'https://example.com/a.png',
        ])['body']['data']['id'];

        $this->request('POST', '/courses/' . $id . '/classes', [
            'title' => 'T', 'description' => 'd', 'seats' => 10,
            'status' => 'disponivel', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31',
        ]);

        $filtered = $this->request('GET', '/courses', [], ['title' => 'php']);
        self::assertCount(1, $filtered['body']['data']);
    }

    public function testCoursesWithoutAvailableClassesAreNotListed(): void
    {
        $id = $this->request('POST', '/courses', [
            'title' => 'Curso',
            'description' => 'desc',
            'theme' => 'agro',
            'image_url' => 'https://example.com/a.png',
        ])['body']['data']['id'];

        $this->request('POST', '/courses/' . $id . '/classes', [
            'title' => 'T', 'description' => 'd', 'seats' => 10,
            'status' => 'encerrado', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31',
        ]);

        $list = $this->request('GET', '/courses');
        self::assertCount(0, $list['body']['data']);
    }

    public function testDeleteCourseRemovesIt(): void
    {
        $id = $this->request('POST', '/courses', [
            'title' => 'Curso',
            'description' => 'desc',
            'theme' => 'agro',
            'image_url' => 'https://example.com/a.png',
        ])['body']['data']['id'];

        $deleted = $this->request('DELETE', '/courses/' . $id);
        self::assertSame(204, $deleted['status']);

        $again = $this->request('DELETE', '/courses/' . $id);
        self::assertSame(404, $again['status']);
    }
}
