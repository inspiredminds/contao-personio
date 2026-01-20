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
use Contao\PageModel;
use Contao\Template;
use InspiredMinds\ContaoPersonio\Controller\Page\PersonioJobPageController;
use InspiredMinds\ContaoPersonio\Model\Job;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(self::TYPE, template: 'ce_personio_job')]
class PersonioJobController extends AbstractContentElementController
{
    public const TYPE = 'personio_job';

    public function __construct(private readonly Slug $slug)
    {
    }

    protected function getResponse(Template $template, ContentModel $model, Request $request): Response
    {
        if (!($job = $request->attributes->get('_content')) instanceof Job) {
            return new Response();
        }

        $template->setData([...(array) $job, ...$template->getData()]);
        $template->job = $job;
        $template->date = $job->createdAt->format($this->getPageModel()->datimFormat);

        if (($jumpTo = PageModel::findById($model->jumpTo)) && PersonioJobPageController::TYPE === $jumpTo->type) {
            $template->applyLink = $jumpTo->getFrontendUrl('/'.$this->slug->generate($job->name.' '.$job->id, $this->getPageModel()?->id ?? []));
        }

        return $template->getResponse();
    }
}
