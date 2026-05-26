<?php

declare(strict_types=1);

namespace DDG\Tests\Unit;

use DDG\Enums\ClassStatus;
use DDG\Enums\CourseTheme;
use DDG\Exceptions\NotFoundException;
use DDG\Exceptions\ValidationException;
use DDG\Repositories\ClassRepository;
use DDG\Repositories\CourseRepository;
use DDG\Services\ClassService;
use DDG\Tests\Support\TestDatabase;
use PDO;
use PHPUnit\Framework\TestCase;

final class ClassServiceTest extends TestCase
{
    private PDO $pdo;
    private ClassService $service;
    private CourseRepository $courseRepo;

    protected function setUp(): void
    {
        $this->pdo = TestDatabase::fresh();
        $this->courseRepo = new CourseRepository($this->pdo);
        $this->service = new ClassService(new ClassRepository($this->pdo), $this->courseRepo);
    }

    public function testCreateClassForExistingCourse(): void
    {
        $course = $this->courseRepo->create('Curso', 'desc', CourseTheme::Tecnologia, 'https://example.com/img.png');

        $class = $this->service->create($course->id, [
            'title' => 'Turma 1',
            'description' => 'desc',
            'seats' => 20,
            'status' => 'disponivel',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        self::assertSame($course->id, $class->courseId);
        self::assertSame(ClassStatus::Disponivel, $class->status);
        self::assertSame(20, $class->seats);
    }

    public function testCreateClassFailsForNonexistentCourse(): void
    {
        $this->expectException(NotFoundException::class);
        $this->service->create(999, [
            'title' => 'Turma',
            'description' => 'desc',
            'seats' => 10,
            'status' => 'disponivel',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);
    }

    public function testCreateClassFailsWithInvertedDates(): void
    {
        $course = $this->courseRepo->create('Curso', 'desc', CourseTheme::Tecnologia, 'https://example.com/img.png');

        $this->expectException(ValidationException::class);
        $this->service->create($course->id, [
            'title' => 'Turma',
            'description' => 'desc',
            'seats' => 10,
            'status' => 'disponivel',
            'start_date' => '2026-12-31',
            'end_date' => '2026-01-01',
        ]);
    }

    public function testCreateClassFailsWithNegativeSeats(): void
    {
        $course = $this->courseRepo->create('Curso', 'desc', CourseTheme::Tecnologia, 'https://example.com/img.png');

        $this->expectException(ValidationException::class);
        $this->service->create($course->id, [
            'title' => 'Turma',
            'description' => 'desc',
            'seats' => -1,
            'status' => 'disponivel',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);
    }
}
