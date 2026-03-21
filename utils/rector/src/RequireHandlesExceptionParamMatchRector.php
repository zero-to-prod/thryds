<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Ensures the exception class declared in #[HandlesException(X::class)]
 * matches the method's first parameter type. In auto mode, updates the
 * attribute argument to match the parameter type.
 */
final class RequireHandlesExceptionParamMatchRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $attributeClass = 'HandlesException';

    private string $mode = 'auto';

    private string $message = 'TODO: [RequireHandlesExceptionParamMatchRector] Attributes define properties — #[HandlesException] declares %s but the method parameter type is %s. The attribute must match the parameter type.';

    public function configure(array $configuration): void
    {
        $this->attributeClass = $configuration['attributeClass'] ?? 'HandlesException';
        $this->mode = $configuration['mode'] ?? 'auto';
        $this->message = $configuration['message'] ?? $this->message;
    }

    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $changed = false;
        $marker = strstr($this->message, '%', true) ?: $this->message;

        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof ClassMethod) {
                continue;
            }

            $attr = $this->findHandlesExceptionAttribute($stmt);
            if ($attr === null) {
                continue;
            }

            $attribute_exception_class = $this->extractClassString($attr);
            if ($attribute_exception_class === null) {
                continue;
            }

            $param_type = $this->firstParamType($stmt);
            if ($param_type === null) {
                continue;
            }

            $attribute_short = $this->shortName($attribute_exception_class);
            $param_short = $this->shortName($param_type);

            if ($attribute_short === $param_short) {
                // Match — remove stale TODO if present.
                if ($this->removeStaleComment($stmt, $marker)) {
                    $changed = true;
                }

                continue;
            }

            // Mismatch detected.
            if ($this->mode === 'auto') {
                $this->updateAttributeArg($attr, $param_type);
                $this->removeStaleComment($stmt, $marker);
                $changed = true;

                continue;
            }

            // Warn mode — add TODO if not already present.
            foreach ($stmt->getComments() as $comment) {
                if (str_contains($comment->getText(), $marker)) {
                    continue 2;
                }
            }

            $todo_text = sprintf($this->message, $attribute_short, $param_short);
            $comments = $stmt->getComments();
            array_unshift($comments, new Comment('// ' . $todo_text));
            $stmt->setAttribute('comments', $comments);
            $changed = true;
        }

        return $changed ? $node : null;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Ensure #[HandlesException(X::class)] matches the method parameter type; auto-fix by updating the attribute argument',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
use ZeroToProd\Thryds\Attributes\HandlesException;
use League\Route\Http\Exception as HttpException;
use Throwable;

class ExceptionHandler
{
    #[HandlesException(Throwable::class)]
    public function handleHttp(HttpException $Exception): void {}
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
use ZeroToProd\Thryds\Attributes\HandlesException;
use League\Route\Http\Exception as HttpException;
use Throwable;

class ExceptionHandler
{
    #[HandlesException(HttpException::class)]
    public function handleHttp(HttpException $Exception): void {}
}
CODE_SAMPLE,
                    [
                        'attributeClass' => 'ZeroToProd\\Thryds\\Attributes\\HandlesException',
                        'mode' => 'auto',
                    ],
                ),
            ]
        );
    }

    private function findHandlesExceptionAttribute(ClassMethod $method): ?Node\Attribute
    {
        $parts = explode('\\', $this->attributeClass);
        $short_name = end($parts);

        foreach ($method->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $name = $this->getName($attr->name);
                if ($name === $this->attributeClass || $name === $short_name) {
                    return $attr;
                }
            }
        }

        return null;
    }

    private function extractClassString(Node\Attribute $attr): ?string
    {
        if (!isset($attr->args[0])) {
            return null;
        }

        $arg = $attr->args[0];
        if (!$arg instanceof Arg) {
            return null;
        }

        if (!$arg->value instanceof ClassConstFetch) {
            return null;
        }

        return $this->getName($arg->value->class);
    }

    private function firstParamType(ClassMethod $method): ?string
    {
        if (!isset($method->params[0])) {
            return null;
        }

        $type = $method->params[0]->type;
        if ($type === null) {
            return null;
        }

        return $this->getName($type);
    }

    private function shortName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return end($parts);
    }

    private function updateAttributeArg(Node\Attribute $attr, string $param_type): void
    {
        if (!isset($attr->args[0]) || !$attr->args[0] instanceof Arg) {
            return;
        }

        $attr->args[0]->value = new ClassConstFetch(
            new FullyQualified($param_type),
            'class',
        );
    }

    private function removeStaleComment(ClassMethod $method, string $marker): bool
    {
        $comments = $method->getComments();
        $filtered = array_values(array_filter(
            $comments,
            static fn(Comment $c): bool => !str_contains($c->getText(), $marker)
        ));

        if (count($filtered) !== count($comments)) {
            $method->setAttribute('comments', $filtered);

            return true;
        }

        return false;
    }
}
