<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\Exception;

use Contenir\Mvc\Workflow\Exception\ExceptionInterface;
use Contenir\Mvc\Workflow\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class InvalidArgumentExceptionTest extends TestCase
{
    public function testExtendsBuiltInInvalidArgumentException(): void
    {
        $exception = new InvalidArgumentException('boom');

        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
        $this->assertSame('boom', $exception->getMessage());
    }

    public function testImplementsLocalExceptionInterface(): void
    {
        $exception = new InvalidArgumentException();
        $this->assertInstanceOf(ExceptionInterface::class, $exception);
    }

    public function testCanBeThrownAndCaughtViaInterface(): void
    {
        $caught = null;
        try {
            throw new InvalidArgumentException('caught');
        } catch (ExceptionInterface $e) {
            $caught = $e;
        }
        $this->assertInstanceOf(InvalidArgumentException::class, $caught);
    }
}
