<?php
namespace MonarcCore\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class AssetServiceFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $connectedUser = $serviceLocator->get('MonarcCore\Service\ConnectedUserService')->getConnectedUser();
        $modelService = $serviceLocator->get('\MonarcCore\Service\ModelService');

        $service = new AssetService();
        $service->setConnectedUser($connectedUser);
        $service->setModelService($modelService);

        return $service;
    }
}