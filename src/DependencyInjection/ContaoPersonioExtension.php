<?php

declare(strict_types=1);

/*
 * (c) INSPIRED MINDS
 */

namespace InspiredMinds\ContaoPersonio\DependencyInjection;

use InspiredMinds\ContaoPersonio\Controller\ContentElement\PersonioJobApplicationController;
use InspiredMinds\ContaoPersonio\Controller\ContentElement\PersonioJobController;
use InspiredMinds\ContaoPersonio\Controller\ContentElement\PersonioJobsController;
use InspiredMinds\ContaoPersonio\Controller\Page\PersonioJobPageController;
use InspiredMinds\ContaoPersonio\HttpClient\PersonioAuthenticatedApiClientFactory;
use InspiredMinds\ContaoPersonio\MessageHandler\PersonioApplicationHandler;
use InspiredMinds\ContaoPersonio\PersonioRecruitingApi;
use InspiredMinds\ContaoPersonio\PersonioXml;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ContaoPersonioExtension extends Extension
{
    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        return new Configuration();
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        (new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config')))
            ->load('services.yaml')
        ;

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if ($xmlFeed = $config['xml_feed']) {
            $container
                ->register('contao_personio.personio_xml_client', HttpClientInterface::class)
                ->setFactory([new Reference('http_client'), 'withOptions'])
                ->setArguments([['base_uri' => $xmlFeed]])
                ->addTag('http_client.client')
            ;

            $container
                ->getDefinition(PersonioXml::class)
                ->setArgument('$personioXmlClient', new Reference('contao_personio.personio_xml_client'))
            ;
        } else {
            $container->removeDefinition(PersonioXml::class);
            $container->removeDefinition(PersonioJobController::class);
            $container->removeDefinition(PersonioJobsController::class);
            $container->removeDefinition(PersonioJobPageController::class);
        }

        $companyId = $config['company_id'];
        $recruitingApiToken = $config['recruiting_api_token'];
        $clientId = $config['recruiting_api_client_id'];
        $clientSecret = $config['recruiting_api_client_secret'];
        $apiUrl = $config['api_url'];

        if (($clientId || $clientSecret) && !$companyId) {
            throw new LogicException('Missing configuration for contao_personio.company_id.');
        }

        if ($companyId && $clientId && $clientSecret && $apiUrl) {
            $options = [
                'base_uri' => $apiUrl,
                'headers' => [
                    'Accept' => 'application/json',
                    'X-Personio-App-ID' => 'Contao Personio',
                    'X-Company-ID' => $companyId,
                ],
            ];

            $container
                ->register('contao_personio.personio_api_client', HttpClientInterface::class)
                ->setFactory([new Reference('http_client'), 'withOptions'])
                ->setArguments([$options])
                ->addTag('http_client.client')
            ;

            $container
                ->getDefinition(PersonioAuthenticatedApiClientFactory::class)
                ->setArgument('$personioApiClient', new Reference('contao_personio.personio_api_client'))
                ->setArgument('$clientId', $clientId)
                ->setArgument('$clientSecret', $clientSecret)
            ;
        } else {
            $container->removeDefinition(PersonioAuthenticatedApiClientFactory::class);
            $container->removeDefinition('contao_personio.personio_authenticated_api_client');
        }

        if ($recruitingApiToken && !$companyId) {
            throw new LogicException('Missing configuration for contao_personio.company_id.');
        }

        if ($companyId && $recruitingApiToken && $apiUrl) {
            $options = [
                'base_uri' => $apiUrl,
                'auth_bearer' => $recruitingApiToken,
                'headers' => [
                    'Accept' => 'application/json',
                    'X-Personio-App-ID' => 'Contao Personio',
                    'X-Company-ID' => $companyId,
                ],
            ];

            $container
                ->register('contao_personio.personio_recruiting_api_client', HttpClientInterface::class)
                ->setFactory([new Reference('http_client'), 'withOptions'])
                ->setArguments([$options])
                ->addTag('http_client.client')
            ;
        } else {
            $container->removeDefinition(PersonioRecruitingApi::class);
            $container->removeDefinition(PersonioJobApplicationController::class);
            $container->removeDefinition(PersonioApplicationHandler::class);
        }
    }
}
