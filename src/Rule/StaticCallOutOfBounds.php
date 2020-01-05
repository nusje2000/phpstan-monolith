<?php

declare(strict_types=1);

namespace Nusje2000\PHPStan\Monolith\Rule;

use Nusje2000\PHPStan\Monolith\Exception\OutOfBoundsException;
use Nusje2000\PHPStan\Monolith\Helper\OutOfBoundsValidator;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

/**
 * @phpstan-implements Rule<Node\Expr\StaticCall>
 */
final class StaticCallOutOfBounds implements Rule
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
        return Node\Expr\StaticCall::class;
    }

    /**
     * @inheritDoc
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof Node\Expr\StaticCall) {
            return [];
        }

        $class = $node->class;
        if (!$class instanceof Node\Name) {
            return [];
        }

        try {
            $this->validator->validate($scope, $class);
        } catch (OutOfBoundsException $exception) {
            $methodName = 'unknown';
            if ($node->name instanceof Node\Identifier) {
                $methodName = (string)$node->name;
            }

            return [sprintf('Invalid static function call %s::%s() (%s)', $class, $methodName, $exception->getMessage())];
        }

        return [];
    }
}
