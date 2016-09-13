<?php
namespace MonarcCore\Service;

class ScaleServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table' => 'MonarcCore\Model\Table\ScaleTable',
        'entity' => 'MonarcCore\Model\Entity\Scale',
        'anrTable' => 'MonarcCore\Model\Table\AnrTable',
        'scaleImpactTypeService' => 'MonarcCore\Service\ScaleImpactTypeService',
    );
}
