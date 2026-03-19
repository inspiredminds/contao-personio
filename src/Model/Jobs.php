<?php

declare(strict_types=1);

/*
 * (c) INSPIRED MINDS
 */

namespace InspiredMinds\ContaoPersonio\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

class Jobs
{
    public function __construct(
        #[SerializedName('position')]
        /**
         * @var list<Job> $jobs
         */
        public array $jobs = [],
    ) {
    }
}
