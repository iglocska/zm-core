<?php
namespace MonarcCore\Service;

class ObjectCategoryServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table'=> '\MonarcCore\Model\Table\ObjectCategoryTable',
        'entity'=> '\MonarcCore\Model\Entity\ObjectCategory',
        'anrTable'=> '\MonarcCore\Model\Table\AnrTable',
    );

}
