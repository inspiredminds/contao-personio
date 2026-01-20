<?php

declare(strict_types=1);

/*
 * (c) INSPIRED MINDS
 */

namespace InspiredMinds\ContaoPersonio\Model;

use InspiredMinds\ContaoPersonio\Serializer\EmptyArrayDenormalizer;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;

class Job
{
    public function __construct(
        public string $id,
        public string|null $subcompany,
        public string $office,
        public string $department,
        public string|null $recruitingCategory,
        public string $name,
        #[Context([EmptyArrayDenormalizer::class => JobDescriptions::class])]
        #[SerializedName('jobDescriptions')]
        public JobDescriptions|null $jobDescriptions,
        public string $employmentType,
        public string $seniority,
        public string $schedule,
        public string|null $keywords,
        public string $occupation,
        public string $occupationCategory,
        public \DateTimeInterface $createdAt,
    ) {
    }

    /**
     * @return list<JobDescription>
     */
    public function getDescriptions(): array
    {
        return $this->jobDescriptions?->descriptions ?? [];
    }
}
