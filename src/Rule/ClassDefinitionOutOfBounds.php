<?php

declare(strict_types=1);

namespace Nusje2000\PHPStan\Monolith\Rule;

use Nusje2000\PHPStan\Monolith\Exception\OutOfBoundsException;
use Nusje2000\PHPStan\Monolith\Helper\OutOfBoundsValidator;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

/**
 * @phpstan-implements Rule<Node\Stmt\Class_>
 */
final class ClassDefinitionOutOfBounds implements Rule
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
        return Node\Stmt\Class_::class;
    }

    /**
     * @inheritDoc
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof Node\Stmt\Class_) {
            return [];
        }

        $errors = [];

        $implements = $node->implements;
        $extends = $node->extends;

        foreach ($implements as $implement) {
            if ($implement instanceof Node\Name) {
                try {
                    $this->validator->validate($scope, $implement);
                } catch (OutOfBoundsException $exception) {
                    $errors[] = sprintf('Invalid extends (%s)', $exception->getMessage());
                }
            }
        }

        if ($extends instanceof Node\Name) {
            try {
                $this->validator->validate($scope, $extends);
            } catch (OutOfBoundsException $exception) {
                $errors[] = sprintf('Invalid implements (%s)', $exception->getMessage());
            }
        }

        return $errors;
    }
}
