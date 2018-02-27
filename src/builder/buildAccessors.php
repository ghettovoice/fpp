<?php

declare(strict_types=1);

namespace Fpp\Builder;

use Fpp\Constructor;
use Fpp\Definition;
use Fpp\DefinitionCollection;
use Fpp\Deriving;
use function Fpp\buildArgumentConstructorFromPayload;
use function Fpp\buildArgumentReturnType;

const buildAccessors = '\Fpp\Builder\buildAccessors';

function buildAccessors(Definition $definition, ?Constructor $constructor, DefinitionCollection $collection): string
{
    if (null === $constructor
        || 0 === count($constructor->arguments())
    ) {
        return '';
    }

    foreach ($definition->derivings() as $deriving) {
        if ($deriving->equals(new Deriving\AggregateChanged())
            || $deriving->equals(new Deriving\DomainEvent())
        ) {
            $accessors = '';

            foreach ($constructor->arguments() as $argument) {
                $returnType = buildArgumentReturnType($argument, $definition);
                $argumentConstructor = buildArgumentConstructorFromPayload($argument, $definition, $collection);
                if ($argument->nullable()) {
                    $check = "if (! isset(\$this->{$argument->name()}) && isset(\$this->payload['{$argument->name()}'])) {";
                } else {
                    $check = "if (! isset(\$this->{$argument->name()})) {";
                }
                $accessors .= <<<CODE
        public function {$argument->name()}()$returnType
        {
            $check
                \$this->{$argument->name()} = $argumentConstructor;
            }

            return \$this->{$argument->name()};
        }


CODE;
            }

            return ltrim(substr($accessors, 0, -2));
        } elseif ($deriving->equals(new Deriving\Command())
            || $deriving->equals(new Deriving\Query())
        ) {
            $accessors = '';

            foreach ($constructor->arguments() as $argument) {
                $returnType = buildArgumentReturnType($argument, $definition);
                $argumentConstructor = buildArgumentConstructorFromPayload($argument, $definition, $collection);
                if ($argument->nullable()) {
                    $return = "isset(\$this->payload['{$argument->name()}']) ? $argumentConstructor : null";
                } else {
                    $return = "$argumentConstructor";
                }
                $accessors .= <<<CODE
        public function {$argument->name()}()$returnType
        {
            return $return;
        }


CODE;
            }

            return ltrim(substr($accessors, 0, -2));
        }
    }

    // default
    $accessors = '';

    foreach ($constructor->arguments() as $argument) {
        $returnType = buildArgumentReturnType($argument, $definition);
        $accessors .= <<<CODE
        public function {$argument->name()}()$returnType
        {
            return \$this->{$argument->name()};
        }


CODE;
    }

    return ltrim(substr($accessors, 0, -2));
}
