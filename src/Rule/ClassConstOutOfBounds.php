<?php

declare(strict_types=1);

namespace Nusje2000\PHPStan\Monolith\Rule;

use Nusje2000\PHPStan\Monolith\Exception\OutOfBoundsException;
use Nusje2000\PHPStan\Monolith\Helper\OutOfBoundsValidator;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

/**
 * @phpstan-implements Rule<Node\Expr\ClassConstFetch>
 */
final class ClassConstOutOfBounds implements Rule
{
    /**
     * @var OutOfBoundsValidator
     */
    private $validator;

    public function __construct(OutOfBoundsValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @inheritDoc
     */
    public function getNodeType(): string
    {
        return Node\Expr\ClassConstFetch::class;
    }

    /**
     * @inheritDoc
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof Node\Expr\ClassConstFetch) {
            return [];
        }

        $name = $node->class;
        if (!$name instanceof Node\Name) {
            return [];
        }

        try {
            $this->validator->validate($scope, $name);
        } catch (OutOfBoundsException $exception) {
            return [sprintf('Invalid class reference to %s (%s)', $name, $exception->getMessage())];
        }

        return [];
    }
}
