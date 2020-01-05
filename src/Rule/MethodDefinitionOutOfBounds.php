<?php

declare(strict_types=1);

namespace Nusje2000\PHPStan\Monolith\Rule;

use Nusje2000\PHPStan\Monolith\Exception\OutOfBoundsException;
use Nusje2000\PHPStan\Monolith\Helper\OutOfBoundsValidator;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

/**
 * @phpstan-implements Rule<Node\Stmt\ClassMethod>
 */
final class MethodDefinitionOutOfBounds implements Rule
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
        return Node\Stmt\ClassMethod::class;
    }

    /**
     * @inheritDoc
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof Node\Stmt\ClassMethod) {
            return [];
        }

        $errors = [];

        $params = $node->params;
        $returnType = $node->returnType;

        foreach ($params as $param) {
            $parameterType = $param->type;

            if ($parameterType instanceof Node\Name) {
                try {
                    $this->validator->validate($scope, $parameterType);
                } catch (OutOfBoundsException $exception) {
                    $parameterName = 'unknown';
                    if ($param->var instanceof Node\Expr\Variable && is_string($param->var->name)) {
                        $parameterName = $param->var->name;
                    }

                    $errors[] = sprintf('Invalid parameter $%s (%s)', $parameterName, $exception->getMessage());
                }
            }
        }

        if ($returnType instanceof Node\Name) {
            try {
                $this->validator->validate($scope, $returnType);
            } catch (OutOfBoundsException $exception) {
                $errors[] = sprintf('Invalid return type of %s() (%s)', $node->name, $exception->getMessage());
            }
        }

        return $errors;
    }
}
