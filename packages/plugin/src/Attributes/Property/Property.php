<?php

namespace Solspace\Freeform\Attributes\Property;

use Symfony\Component\Serializer\Annotation\Ignore;

/**
 * @template T
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
abstract class Property
{
    #[Ignore] public ?TransformerInterface $transformer = null;
    #[Ignore] public ?ValueGeneratorInterface $valueGenerator = null;

    /** @var PropertyValidatorInterface[] */
    #[Ignore] public array $validators = [];

    /** @var Middleware[] */
    public array $middleware = [];

    /** @var VisibilityFilter[] */
    public array $visibilityFilters = [];

    public bool $visible = true;
    public bool $translatable = false;

    /** @var Edition[] */
    public ?array $editions = null;

    /** @var Flag[] */
    public array $flags = [];
    public array $messages = [];

    public ?string $section = null;
    public bool $required = false;
    public ?string $handle = null;
    public ?string $type = null;

    /**
     * @param T $value
     */
    public function __construct(
        public ?string $label = null,
        public ?string $instructions = null,
        public ?int $order = null,
        public mixed $value = null,
        public ?string $placeholder = null,
        public ?int $width = null,
        public ?bool $disabled = null,
    ) {}

    #[Ignore]
    public function hasFlag(...$name): bool
    {
        foreach ($this->flags as $flag) {
            if (\in_array($flag->name, $name, true)) {
                return true;
            }
        }

        return false;
    }

    public function matchesEdition(string $edition): bool
    {
        if (empty($this->editions)) {
            return true;
        }

        foreach ($this->editions as $item) {
            if ($item === $edition) {
                return true;
            }
        }

        return false;
    }

    #[Ignore]
    public function hasMiddlewareInstalled(...$name): bool
    {
        foreach ($this->middleware as $middleware) {
            if (\in_array($middleware->name, $name, true)) {
                return true;
            }
        }

        return false;
    }
}
