<?php

declare(strict_types=1);

/*
 * (c) INSPIRED MINDS
 */

namespace InspiredMinds\ContaoPersonio\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Slug\Slug;
use Contao\CoreBundle\Util\LocaleUtil;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\Template;
use InspiredMinds\ContaoPersonio\Controller\Page\PersonioJobPageController;
use InspiredMinds\ContaoPersonio\Model\Job;
use InspiredMinds\ContaoPersonio\PersonioXml;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(self::TYPE, template: 'ce_personio_jobs')]
class PersonioJobsController extends AbstractContentElementController
{
    public const TYPE = 'personio_jobs';

    public function __construct(
        private readonly PersonioXml $personioApi,
        private readonly Slug $slug,
    ) {
    }

    protected function getResponse(Template $template, ContentModel $model, Request $request): Response
    {
        try {
            $language = LocaleUtil::getPrimaryLanguage($model->personio_languageOverride ?: $request->getLocale());
            $fallbacks = StringUtil::deserialize($model->personio_languageFallbacks, true);
            $jobs = $this->personioApi->getJobs($language, $fallbacks)?->jobs;
        } catch (\Throwable $e) {
            if ($this->container->get('contao.routing.scope_matcher')->isBackendRequest($request)) {
                return new Response('<p class="tl_error">'.$e->getMessage().'</p>');
            }

            return new Response();
        }

        // Filter the jobs
        if ($filter = StringUtil::deserialize($model->personio_listFilter, true)) {
            $jobs = array_filter(
                $jobs,
                static function (Job $job) use ($filter): bool {
                    foreach ($filter as $f) {
                        $field = $f['field'] ?? null;
                        $value = $f['value'] ?? '';

                        if (!$field || !property_exists($job, $field)) {
                            continue;
                        }

                        $result = match ($f['operator'] ?? 'is') {
                            'is' => $job->{$field} === $value,
                            'is-not' => $job->{$field} !== $value,
                            'contains' => str_contains((string) $job->{$field}, $value),
                            'contains-not' => !str_contains((string) $job->{$field}, $value),
                        };

                        if (false === $result) {
                            return false;
                        }
                    }

                    return true;
                },
            );
        }

        // Sort the jobs
        if ($sortField = $model->personio_sortField) {
            $sortDir = $model->personio_sortDir;

            usort(
                $jobs,
                static function (Job $a, Job $b) use ($sortField, $sortDir): int {
                    if (!property_exists($a, $sortField) || !property_exists($b, $sortField)) {
                        return 0;
                    }

                    if ('desc' === $sortDir) {
                        return strcasecmp((string) $b->{$sortField}, (string) $a->{$sortField});
                    }

                    return strcasecmp((string) $a->{$sortField}, (string) $b->{$sortField});
                },
            );
        }

        $template->jobs = $jobs;

        $template->getJobDetailUrl = function (Job $job) use ($model): string|null {
            if (!($jumpTo = PageModel::findById($model->jumpTo)) || PersonioJobPageController::TYPE !== $jumpTo->type) {
                return null;
            }

            return $jumpTo->getFrontendUrl('/'.$this->slug->generate($job->name.' '.$job->id, $this->getPageModel()?->id ?? []));
        };

        return $template->getResponse();
    }
}
