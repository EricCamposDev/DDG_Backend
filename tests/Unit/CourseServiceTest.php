<?php

declare(strict_types=1);

namespace DDG\Tests\Unit;

use DDG\Enums\CourseTheme;
use DDG\Exceptions\NotFoundException;
use DDG\Exceptions\ValidationException;
use DDG\Repositories\CourseRepository;
use DDG\Services\CourseService;
use DDG\Tests\Support\TestDatabase;
use PHPUnit\Framework\TestCase;

final class CourseServiceTest extends TestCase
{
    private CourseService $service;
    private CourseRepository $repository;

    protected function setUp(): void
    {
        $pdo = TestDatabase::fresh();
        $this->repository = new CourseRepository($pdo);
        $this->service = new CourseService($this->repository);
    }

    public function testCreateCourseSuccessfully(): void
    {
        $course = $this->service->create([
            'title' => 'Curso de PHP',
            'description' => 'Aprenda PHP do zero',
            'theme' => 'tecnologia',
            'image_url' => 'https://example.com/img.png',
        ]);

        self::assertSame('Curso de PHP', $course->title);
        self::assertSame(CourseTheme::Tecnologia, $course->theme);
        self::assertGreaterThan(0, $course->id);
    }

    public function testCreateCourseFailsWithoutTitle(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->create([
            'description' => 'desc',
            'theme' => 'tecnologia',
            'image_url' => 'https://example.com/img.png',
        ]);
    }

    public function testCreateCourseFailsWithInvalidTheme(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->create([
            'title' => 'Curso',
            'description' => 'desc',
            'theme' => 'esportes',
            'image_url' => 'https://example.com/img.png',
        ]);
    }

    public function testCreateCourseFailsWithInvalidUrl(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->create([
            'title' => 'Curso',
            'description' => 'desc',
            'theme' => 'agro',
            'image_url' => 'not-a-url',
        ]);
    }

    public function testUpdateExistingCourse(): void
    {
        $created = $this->service->create([
            'title' => 'Original',
            'description' => 'desc',
            'theme' => 'agro',
            'image_url' => 'https://example.com/img.png',
        ]);

        $updated = $this->service->update($created->id, [
            'title' => 'Atualizado',
            'description' => 'nova desc',
            'theme' => 'marketing',
            'image_url' => 'https://example.com/new.png',
        ]);

        self::assertSame('Atualizado', $updated->title);
        self::assertSame(CourseTheme::Marketing, $updated->theme);
    }

    public function testUpdateNonexistentCourseThrows(): void
    {
        $this->expectException(NotFoundException::class);
        $this->service->update(999, [
            'title' => 'X',
            'description' => 'desc',
            'theme' => 'agro',
            'image_url' => 'https://example.com/img.png',
        ]);
    }

    public function testDeleteNonexistentCourseThrows(): void
    {
        $this->expectException(NotFoundException::class);
        $this->service->delete(123);
    }
}
