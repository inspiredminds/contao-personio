<?php

declare(strict_types=1);

/*
 * (c) INSPIRED MINDS
 */

namespace InspiredMinds\ContaoPersonio\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Intl\Locales;
use Doctrine\DBAL\Connection;

#[AsCallback('tl_content', 'fields.personio_languageOverride.options')]
#[AsCallback('tl_page', 'fields.personio_languageOverride.options')]
#[AsCallback('tl_content', 'fields.personio_languageFallbacks.options')]
#[AsCallback('tl_page', 'fields.personio_languageFallbacks.options')]
class LanguageOptionsListener
{
    public function __construct(
        private readonly Locales $locales,
        private readonly Connection $db,
    ) {
    }

    public function __invoke(): array
    {
        $rootLanguages = $this->db->fetchAllKeyValue("SELECT language, sorting FROM tl_page WHERE type = 'root' AND language != ''");
        $options = array_intersect_key($this->locales->getLocales(), $rootLanguages);

        uksort($options, static fn (string $a, string $b): int => ($rootLanguages[$a] ?? 0) - $rootLanguages[$b]);

        return $options;
    }
}
