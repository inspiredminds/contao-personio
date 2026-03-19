<?php

declare(strict_types=1);

/*
 * (c) INSPIRED MINDS
 */

namespace InspiredMinds\ContaoPersonio\Controller\Page;

use Contao\CoreBundle\DependencyInjection\Attribute\AsPage;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Routing\ResponseContext\CoreResponseContextFactory;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Slug\Slug;
use Contao\CoreBundle\Util\LocaleUtil;
use Contao\FrontendIndex;
use Contao\Input;
use Contao\PageModel;
use Contao\StringUtil;
use InspiredMinds\ContaoPersonio\Model\Job;
use InspiredMinds\ContaoPersonio\PersonioXml;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsPage(self::TYPE)]
class PersonioJobPageController
{
    public const TYPE = 'personio_job_page';

    public function __construct(
        private readonly PersonioXml $personioApi,
        private readonly CoreResponseContextFactory $coreResponseContextFactory,
        private readonly Slug $slug,
    ) {
    }

    public function __invoke(Request $request, PageModel $pageModel): Response
    {
        if (!$autoItem = Input::get('auto_item')) {
            throw new PageNotFoundException();
        }

        $language = LocaleUtil::getPrimaryLanguage($pageModel->personio_languageOverride ?: $request->getLocale());
        $fallbacks = StringUtil::deserialize($pageModel->personio_languageFallbacks, true);
        $jobs = $this->personioApi->getJobs($language, $fallbacks)?->jobs;
        $slugParts = explode('-', $autoItem);
        $jobId = end($slugParts);

        foreach ($jobs as $job) {
            /** @var Job $job */
            if ($this->slug->generate($job->name.' '.$job->id, $pageModel->id) === $autoItem) {
                $request->attributes->set('_content', $job);

                break;
            }

            if ($job->id === $jobId) {
                $request->attributes->set('_content', $job);

                break;
            }
        }

        if (($job = $request->attributes->get('_content')) instanceof Job) {
            $responseContext = $this->coreResponseContextFactory->createContaoWebpageResponseContext($pageModel);
            $responseContext->get(HtmlHeadBag::class)
                ->setTitle($job->name)
            ;

            return (new FrontendIndex())->renderPage($pageModel);
        }

        throw new PageNotFoundException();
    }
}
