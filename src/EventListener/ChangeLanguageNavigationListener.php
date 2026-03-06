<?php

declare(strict_types=1);

/*
 * (c) INSPIRED MINDS
 */

namespace InspiredMinds\ContaoPersonio\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Input;
use InspiredMinds\ContaoPersonio\Controller\Page\PersonioJobPageController;
use Terminal42\ChangeLanguage\Event\ChangelanguageNavigationEvent;

#[AsHook('changelanguageNavigation')]
class ChangeLanguageNavigationListener
{
    public function __invoke(ChangelanguageNavigationEvent $event): void
    {
        if (!$autoItem = Input::get('auto_item')) {
            return;
        }

        if (PersonioJobPageController::TYPE !== $event->getNavigationItem()->getTargetPage()->type) {
            return;
        }

        $event->getUrlParameterBag()->setUrlAttribute('items', $autoItem);
    }
}
