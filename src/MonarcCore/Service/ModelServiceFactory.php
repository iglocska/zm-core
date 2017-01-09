<?php
namespace MonarcCore\Service;

class ModelServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table' => 'MonarcCore\Model\Table\ModelTable',
        'entity' => 'MonarcCore\Model\Entity\Model',
        'anrService' => 'MonarcCore\Service\AnrService',
        'anrTable' => 'MonarcCore\Model\Table\AnrTable',
        //'instanceService' => 'MonarcCore\Service\instanceService',
        'instanceRiskTable' => 'MonarcCore\Model\Table\InstanceRiskTable',
        'instanceRiskOpTable' => 'MonarcCore\Model\Table\InstanceRiskOpTable',
        'objectTable' => 'MonarcCore\Model\Table\ObjectTable',
        'amvTable' => 'MonarcCore\Model\Table\AmvTable',
    );
}
