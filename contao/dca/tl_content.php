<?php

declare(strict_types=1);

/*
 * (c) INSPIRED MINDS
 */

use Doctrine\DBAL\Platforms\MySQLPlatform;
use InspiredMinds\ContaoPersonio\Controller\ContentElement\PersonioJobApplicationController;
use InspiredMinds\ContaoPersonio\Controller\ContentElement\PersonioJobController;
use InspiredMinds\ContaoPersonio\Controller\ContentElement\PersonioJobsController;
use InspiredMinds\ContaoPersonio\EventListener\JobsPropertyOptionsCallbackListener;
use InspiredMinds\ContaoPersonio\PersonioRecruitingApi;

$GLOBALS['TL_DCA']['tl_content']['fields']['jumpTo'] = [
    'exclude' => true,
    'inputType' => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval' => ['fieldType' => 'radio'],
    'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
    'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['personio_applicationFields'] = [
    'exclude' => true,
    'inputType' => 'checkboxWizard',
    'options' => [...PersonioRecruitingApi::$standardApplicationFields, ...PersonioRecruitingApi::$systemApplicationAttributes],
    'reference' => &$GLOBALS['TL_LANG']['MSC']['personioFields'],
    'eval' => ['multiple' => true],
    'sql' => ['type' => 'blob', 'length' => MySQLPlatform::LENGTH_LIMIT_BLOB, 'notnull' => false, 'notnull' => false],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['personio_listFilter'] = [
    'inputType' => 'group',
    'palette' => ['field', 'operator', 'value'],
    'fields' => [
        'field' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['personio_listFilter_field'],
            'inputType' => 'select',
            'options_callback' => [JobsPropertyOptionsCallbackListener::class, '__invoke'],
            'eval' => ['tl_class' => 'w50 w33'],
        ],
        'operator' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['personio_listFilter_operator'],
            'inputType' => 'select',
            'options' => [
                'is',
                'is-not',
                'contains',
                'contains-not',
            ],
            'reference' => &$GLOBALS['TL_LANG']['tl_content']['personio_listFilter_operators'],
            'eval' => ['tl_class' => 'w50 w33'],
        ],
        'value' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['personio_listFilter_value'],
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50 w33'],
        ],
    ],
    'order' => false,
    'eval' => ['tl_class' => 'clr'],
    'sql' => ['type' => 'blob', 'length' => MySQLPlatform::LENGTH_LIMIT_BLOB, 'notnull' => false, 'notnull' => false],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['personio_sortField'] = [
    'inputType' => 'select',
    'options_callback' => [JobsPropertyOptionsCallbackListener::class, '__invoke'],
    'eval' => ['tl_class' => 'w50', 'includeBlankOption' => true],
    'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['personio_sortDir'] = [
    'inputType' => 'select',
    'options' => ['asc', 'desc'],
    'reference' => &$GLOBALS['TL_LANG']['tl_content']['personio_sortDirs'],
    'eval' => ['tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 4, 'default' => ''],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['personio_languageOverride'] = [
    'inputType' => 'select',
    'eval' => ['tl_class' => 'w50', 'includeBlankOption' => true],
    'sql' => ['type' => 'string', 'length' => 16, 'default' => ''],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['personio_languageFallbacks'] = [
    'inputType' => 'checkboxWizard',
    'eval' => ['tl_class' => 'clr', 'multiple' => true, 'helpwizard' => true],
    'explanation' => 'personio_languageFallbacks',
    'sql' => ['type' => 'text', 'length' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'notnull' => false],
];

$GLOBALS['TL_DCA']['tl_content']['palettes'][PersonioJobsController::TYPE] = '{type_legend},type,headline;{config_legend},personio_languageOverride,personio_languageFallbacks;{filter_legend},personio_listFilter;{sort_legend},personio_sortField,personio_sortDir;{redirect_legend},jumpTo;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop';
$GLOBALS['TL_DCA']['tl_content']['palettes'][PersonioJobController::TYPE] = '{type_legend},type,headline;{redirect_legend},jumpTo;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop';
$GLOBALS['TL_DCA']['tl_content']['palettes'][PersonioJobApplicationController::TYPE] = '{type_legend},type,headline;{config_legend},personio_applicationFields;{redirect_legend},jumpTo;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop';
