<?php

declare(strict_types=1);

namespace DDG\Tests\Unit;

use DateTimeImmutable;
use DDG\Enums\ClassStatus;
use DDG\Enums\CourseTheme;
use DDG\Exceptions\EnrollmentException;
use DDG\Repositories\ClassRepository;
use DDG\Repositories\CourseRepository;
use DDG\Repositories\EnrollmentRepository;
use DDG\Repositories\UserRepository;
use DDG\Services\EnrollmentService;
use DDG\Tests\Support\TestDatabase;
use PDO;
use PHPUnit\Framework\TestCase;

final class EnrollmentServiceTest extends TestCase
{
    private PDO $pdo;
    private CourseRepository $courseRepo;
    private ClassRepository $classRepo;
    private UserRepository $userRepo;
    private EnrollmentRepository $enrollmentRepo;

    protected function setUp(): void
    {
        $this->pdo = TestDatabase::fresh();
        $this->courseRepo = new CourseRepository($this->pdo);
        $this->classRepo = new ClassRepository($this->pdo);
        $this->userRepo = new UserRepository($this->pdo);
        $this->enrollmentRepo = new EnrollmentRepository($this->pdo);
    }

    private function makeService(string $today = '2026-06-15'): EnrollmentService
    {
        return new EnrollmentService(
            $this->enrollmentRepo,
            $this->classRepo,
            $this->userRepo,
            $this->courseRepo,
            new DateTimeImmutable($today),
        );
    }

    public function testValidEnrollmentSucceeds(): void
    {
        $user = $this->userRepo->create('Eric', 'eric@example.com');
        $course = $this->courseRepo->create('Curso', 'desc', CourseTheme::Tecnologia, 'https://example.com/img.png');
        $class = $this->classRepo->create($course->id, 'Turma 1', 'desc', 5, ClassStatus::Disponivel, '2026-01-01', '2026-12-31');

        $enrollment = $this->makeService()->enroll([
            'user_id' => $user->id,
            'class_id' => $class->id,
        ]);

        self::assertSame($user->id, $enrollment->userId);
        self::assertSame($class->id, $enrollment->classId);
        self::assertSame($course->id, $enrollment->courseId);
    }

    public function testEnrollmentInClosedClassIsRejected(): void
    {
        $user = $this->userRepo->create('Eric', 'eric@example.com');
        $course = $this->courseRepo->create('Curso', 'desc', CourseTheme::Tecnologia, 'https://example.com/img.png');
        $class = $this->classRepo->create($course->id, 'Turma', 'desc', 5, ClassStatus::Encerrado, '2026-01-01', '2026-12-31');

        $this->expectException(EnrollmentException::class);
        $this->expectExceptionMessage('turma encerrada');

        $this->makeService()->enroll(['user_id' => $user->id, 'class_id' => $class->id]);
    }

    public function testEnrollmentOutsideDateRangeIsRejected(): void
    {
        $user = $this->userRepo->create('Eric', 'eric@example.com');
        $course = $this->courseRepo->create('Curso', 'desc', CourseTheme::Tecnologia, 'https://example.com/img.png');
        $class = $this->classRepo->create($course->id, 'Turma', 'desc', 5, ClassStatus::Disponivel, '2026-01-01', '2026-03-31');

        $this->expectException(EnrollmentException::class);
        $this->expectExceptionMessage('fora do período');

        $this->makeService('2026-06-15')->enroll(['user_id' => $user->id, 'class_id' => $class->id]);
    }

    public function testEnrollmentTwiceInSameCourseIsRejected(): void
    {
        $user = $this->userRepo->create('Eric', 'eric@example.com');
        $course = $this->courseRepo->create('Curso', 'desc', CourseTheme::Tecnologia, 'https://example.com/img.png');
        $classA = $this->classRepo->create($course->id, 'Turma A', 'desc', 5, ClassStatus::Disponivel, '2026-01-01', '2026-12-31');
        $classB = $this->classRepo->create($course->id, 'Turma B', 'desc', 5, ClassStatus::Disponivel, '2026-01-01', '2026-12-31');

        $service = $this->makeService();
        $service->enroll(['user_id' => $user->id, 'class_id' => $classA->id]);

        $this->expectException(EnrollmentException::class);
        $this->expectExceptionMessage('já está matriculado');

        $service->enroll(['user_id' => $user->id, 'class_id' => $classB->id]);
    }

    public function testEnrollmentFailsWhenNoSeatsAvailable(): void
    {
        $course = $this->courseRepo->create('Curso', 'desc', CourseTheme::Tecnologia, 'https://example.com/img.png');
        $class = $this->classRepo->create($course->id, 'Turma', 'desc', 1, ClassStatus::Disponivel, '2026-01-01', '2026-12-31');

        $userA = $this->userRepo->create('A', 'a@example.com');
        $userB = $this->userRepo->create('B', 'b@example.com');

        $service = $this->makeService();
        $service->enroll(['user_id' => $userA->id, 'class_id' => $class->id]);

        $this->expectException(EnrollmentException::class);
        $this->expectExceptionMessage('não possui vagas');

        $service->enroll(['user_id' => $userB->id, 'class_id' => $class->id]);
    }

    public function testListCoursesByUserReturnsEnrolledCourses(): void
    {
        $user = $this->userRepo->create('Eric', 'eric@example.com');
        $course = $this->courseRepo->create('Curso PHP', 'desc', CourseTheme::Tecnologia, 'https://example.com/img.png');
        $class = $this->classRepo->create($course->id, 'Turma', 'desc', 5, ClassStatus::Disponivel, '2026-01-01', '2026-12-31');

        $service = $this->makeService();
        $service->enroll(['user_id' => $user->id, 'class_id' => $class->id]);

        $list = $service->listCoursesByUser($user->id);

        self::assertCount(1, $list);
        self::assertSame('Curso PHP', $list[0]['course']['title']);
        self::assertSame($class->id, $list[0]['class']['id']);
    }
}
