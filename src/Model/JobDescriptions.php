<?php

declare(strict_types=1);

/*
 * (c) INSPIRED MINDS
 */

namespace InspiredMinds\ContaoPersonio\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

class JobDescriptions
{
    public function __construct(
        #[SerializedName('jobDescription')]
        /**
         * @var list<JobDescription> $descriptions
         */
        public array $descriptions,
    ) {
    }
}
