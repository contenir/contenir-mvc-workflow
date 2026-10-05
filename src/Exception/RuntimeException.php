<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Exception;

use RuntimeException as PhpRuntimeException;

/**
 * Thrown when a workflow is used before it has what it needs, such as a
 * resource or a controller.
 *
 * @api
 */
class RuntimeException extends PhpRuntimeException implements ExceptionInterface {}
