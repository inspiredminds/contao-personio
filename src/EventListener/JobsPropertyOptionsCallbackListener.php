<?php

declare(strict_types=1);

/*
 * (c) INSPIRED MINDS
 */

namespace InspiredMinds\ContaoPersonio\EventListener;

use InspiredMinds\ContaoPersonio\Model\Job;

class JobsPropertyOptionsCallbackListener
{
    public function __invoke(): array
    {
        $reflection = new \ReflectionClass(Job::class);

        $options = array_filter(array_values(array_map(
            static function (\ReflectionProperty $property): string|null {
                if ('id' === $property->getName()) {
                    return null;
                }

                return 'string' === $property->getType()->getName() ? $property->getName() : null;
            },
            $reflection->getProperties(\ReflectionProperty::IS_PUBLIC),
        )));

        sort($options);

        return $options;
    }
}
