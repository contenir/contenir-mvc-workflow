<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\TestAsset;

use Contenir\Mvc\Workflow\Workflow\AbstractWorkflow;

/**
 * Concrete subclass of AbstractWorkflow that allows tests to drive every
 * branch of the abstract class without depending on PageWorkflow / Article
 * specific overrides.
 */
class AbstractWorkflowStub extends AbstractWorkflow
{
    protected ?string $controller = 'Application\\Controller\\IndexController';

    public function getRouteConfig(): array
    {
        return [
            'type'    => 'literal',
            'options' => [
                'route' => $this->getRoutePath(),
            ],
        ];
    }

    /**
     * Allow tests to set protected state directly.
     */
    public function setProtectedProperty(string $name, mixed $value): void
    {
        $this->{$name} = $value;
    }

    public function getProtectedProperty(string $name): mixed
    {
        return $this->{$name};
    }
}
