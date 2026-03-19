<?php

declare(strict_types=1);

/*
 * (c) INSPIRED MINDS
 */

namespace InspiredMinds\ContaoPersonio;

use InspiredMinds\ContaoPersonio\Model\Job;
use InspiredMinds\ContaoPersonio\Model\Jobs;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PersonioXml
{
    public function __construct(
        private readonly HttpClientInterface $personioXmlClient,
        private readonly SerializerInterface $serializer,
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * @param list<string> $fallbacks Language fallbacks
     */
    public function getJobs(string $language, array $fallbacks = []): Jobs
    {
        $mainJobs = null;
        $fallbackJobs = [];

        foreach (array_unique([$language, ...$fallbacks]) as $lang) {
            $xml = $this->cache->get(
                'personio-xml-'.$lang,
                function (ItemInterface $item) use ($lang) {
                    $item->expiresAfter(\DateInterval::createFromDateString('5 minutes'));

                    return $this->personioXmlClient->request('GET', '', ['query' => ['language' => $lang]])->getContent();
                },
            );

            /** @var Jobs $jobs */
            $jobs = $this->serializer->deserialize($xml, Jobs::class, XmlEncoder::FORMAT);

            if (!$mainJobs) {
                $mainJobs = $jobs;
            } else {
                $fallbackJobs[] = $jobs;
            }
        }

        // Process job descriptions
        if ($fallbackJobs) {
            foreach ($mainJobs->jobs as $job) {
                /** @var Job $job */
                if ($job->getDescriptions()) {
                    continue;
                }

                foreach ($fallbackJobs as $f) {
                    foreach ($f->jobs as $fallbackJob) {
                        /** @var Job $fallbackJob */
                        if ($job->id !== $fallbackJob->id || !$fallbackJob->getDescriptions()) {
                            continue;
                        }

                        $job->jobDescriptions = $fallbackJob->jobDescriptions;

                        break 2;
                    }
                }
            }
        }

        return $mainJobs;
    }
}
