<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Exception;

use InvalidArgumentException as PhpInvalidArgumentException;

/**
 * Thrown for missing or invalid workflow configuration and services.
 *
 * @api
 */
class InvalidArgumentException extends PhpInvalidArgumentException implements ExceptionInterface {}
