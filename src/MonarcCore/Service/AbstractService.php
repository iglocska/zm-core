<?php
namespace MonarcCore\Service;

abstract class AbstractService extends AbstractServiceFactory
{
    use \MonarcCore\Model\GetAndSet;

    protected $serviceFactory;

    public function __construct($serviceFactory = null)
    {
        /*if($serviceFactory instanceof \MonarcCore\Model\Table\AbstractEntityTable || $serviceFactory instanceof \MonarcCore\Model\Entity\AbstractEntity){
            $this->serviceFactory = $serviceFactory;
        }elseif(is_array($serviceFactory)){
            foreach($serviceFactory as $k => $v){
                if($v instanceof \MonarcCore\Model\Table\AbstractEntityTable || $v instanceof \MonarcCore\Model\Entity\AbstractEntity){
                    $this->set($k,$v);
                }
            }
        }*/


        if (is_array($serviceFactory)){
            foreach($serviceFactory as $k => $v){
                $this->set($k,$v);
            }
        } else {
            $this->serviceFactory = $serviceFactory;
        }
    }

    protected function getServiceFactory()
    {
        return $this->serviceFactory;
    }

    protected function parseFrontendOrder($order) {
        if ($order == null) {
            return null;
        } else if (substr($order, 0, 1) == '-') {
            return array(substr($order, 1), 'ASC');
        } else {
            return array($order, 'DESC');
        }
    }

    protected function parseFrontOrder($order) {
        if ($order == null) {
            return null;
        } else if (substr($order, 0, 1) == '-') {
            return array(substr($order, 1) => 'ASC');
        } else {
            return array($order => 'DESC');
        }
    }

    protected function parseFrontendFilter($filter, $columns = array()) {
        $output = array();

        foreach ($columns as $c) {
            $output[$c] = $filter;
        }

        return $output;
    }
}
