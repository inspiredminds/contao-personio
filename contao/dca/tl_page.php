<?php

declare(strict_types=1);

/*
 * (c) INSPIRED MINDS
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use InspiredMinds\ContaoPersonio\Controller\Page\PersonioJobPageController;

$GLOBALS['TL_DCA']['tl_page']['fields']['personio_languageOverride'] = [
    'inputType' => 'select',
    'eval' => ['tl_class' => 'w50', 'includeBlankOption' => true],
    'sql' => ['type' => 'string', 'length' => 16, 'default' => ''],
];

$GLOBALS['TL_DCA']['tl_page']['palettes'][PersonioJobPageController::TYPE] = $GLOBALS['TL_DCA']['tl_page']['palettes']['regular'];

PaletteManipulator::create()
    ->addLegend('personio_legend', 'routing_legend', PaletteManipulator::POSITION_BEFORE)
    ->addField('personio_languageOverride', 'personio_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette(PersonioJobPageController::TYPE, 'tl_page')
;
