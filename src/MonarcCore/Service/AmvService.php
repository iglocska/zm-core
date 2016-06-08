<?php
namespace MonarcCore\Service;

/**
 * Amv Service
 *
 * Class AmvService
 * @package MonarcCore\Service
 */
class AmvService extends AbstractService
{
    protected $assetTable;
    protected $measureTable;
    protected $threatTable;
    protected $vulnerabilityTable;

    protected $historicalService;

    protected $errorMessage;

    protected $filterColumns = array();

    protected $dependencies = ['asset', 'threat', 'vulnerability', 'measure1', 'measure2', 'measure3'];



    /**
     * Create
     *
     * @param $data
     * @throws \Exception
     */
    public function create($data) {

        $entity = $this->get('entity');

        $entity->exchangeArray($data);

        foreach($this->dependencies as $dependency) {
            $value = $entity->get($dependency);
            if (!empty($value)) {
                $tableName = preg_replace("/[0-9]/", "", $dependency)  . 'Table';
                $method = 'set' . ucfirst($dependency);
                $dependencyEntity = $this->get($tableName)->getEntity($value);
                $entity->$method($dependencyEntity);
            }
        }

        $authorized = $this->compliesRequirement($entity);

        if (!$authorized) {
            throw new \Exception($this->errorMessage);
        }

        $id = $this->get('table')->save($entity);

        //historisation
        $newEntity = $this->getEntity($id);

        //virtual name for historisation
        $label = [];
        for($i =1; $i<=4; $i++) {
            $name = [];
            $lab = 'label' . $i;
            if ($newEntity['asset']->$lab) {
                $name[] = $newEntity['asset']->$lab;
            }
            if ($newEntity['threat']->$lab) {
                $name[] = $newEntity['threat']->$lab;
            }
            if ($newEntity['vulnerability']->$lab) {
                $name[] = $newEntity['vulnerability']->$lab;
            }
            $label[] = implode('-', $name);
        }
        $this->label = $label;

        $this->historizeCreate('amv', $newEntity);

        return $id;
    }

    /**
     *
     * Update
     *
     * @param $id
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public function update($id, $data){

        $entity = $this->get('table')->getEntity($id);

        //clone current entity for retrieve difference with new
        $oldEntity = clone $entity;

        //virtual name for historisation
        $label = [];
        for($i =1; $i<=4; $i++) {
            $name = [];
            $lab = 'label' . $i;
            if ($entity->asset->$lab) {
                $name[] = $entity->asset->$lab;
            }
            if ($entity->threat->$lab) {
                $name[] = $entity->threat->$lab;
            }
            if ($entity->vulnerability->$lab) {
                $name[] = $entity->vulnerability->$lab;
            }
            $label[] = implode('-', $name);
        }
        $this->label = $label;

        $entity->exchangeArray($data);

        $newEntity = clone $entity;

        foreach($this->dependencies as $dependency) {
            $fieldValue = isset($data[$dependency]) ? $data[$dependency] : array();

            if (!empty($fieldValue)) {
                $tableName = preg_replace("/[0-9]/", "", $dependency)  . 'Table';
                $method = 'set' . ucfirst($dependency);
                $dependencyEntity = $this->get($tableName)->getEntity($fieldValue);
                $entity->$method($dependencyEntity);
            }
        }

        $authorized = $this->compliesRequirement($entity);

        if (!$authorized) {
            throw new \Exception($this->errorMessage);
        }

        //historisation
        $this->historizeUpdate('amv', $newEntity, $oldEntity);

        return $this->get('table')->save($entity);
    }

    /**
     * Delete
     *
     * @param $id
     */
    public function delete($id) {

        //historisation
        $entity = $this->getEntity($id);

        if ($entity) {

            //virtual name for historisation
            $label = [];
            for ($i = 1; $i <= 4; $i++) {
                $name = [];
                $lab = 'label' . $i;
                if ($entity['asset']->$lab) {
                    $name[] = $entity['asset']->$lab;
                }
                if ($entity['threat']->$lab) {
                    $name[] = $entity['threat']->$lab;
                }
                if ($entity['vulnerability']->$lab) {
                    $name[] = $entity['vulnerability']->$lab;
                }
                $label[] = implode('-', $name);
            }
            $this->label = $label;

            $this->historizeDelete('amv', $entity);


            //$this->get('table')->delete($id);
        }
    }

    /**
     * Complies Requirement
     *
     * @param $amv
     * @return bool
     */
    public function compliesRequirement($amv) {

        $assetMode = $amv->getAsset()->mode;
        $threatMode = $amv->getThreat()->mode;
        $vulnerabilityMode = $amv->getVulnerability()->mode;

        $assetModels = $amv->getAsset()->getModels();
        $assetModelsIds = [];
        $assetModelsIsRegulator = [];
        foreach ($assetModels as $model) {
            $assetModelsIds[] = $model->id;
            $assetModelsIsRegulator[] = $model->isRegulator;
        }

        $threatModels = $amv->getThreat()->getModels();
        $threatModelsIds = [];
        foreach ($threatModels as $model) {
            $threatModelsIds[] = $model->id;
        }

        $vulnerabilityModels = $amv->getVulnerability()->getModels();
        $vulnerabilityModelsIds = [];
        foreach ($vulnerabilityModels as $model) {
            $vulnerabilityModelsIds[] = $model->id;
        }

        return $this->compliesControl($assetMode, $threatMode, $vulnerabilityMode, $assetModelsIds, $threatModelsIds, $vulnerabilityModelsIds, $assetModelsIsRegulator);
    }


    /**
     * Complies control
     *
     * @param $assetMode
     * @param $threatMode
     * @param $vulnerabilityMode
     * @param $assetModelsIds
     * @param $threatModelsIds
     * @param $vulnerabilityModelsIds
     * @param $assetModelsIsRegulator
     * @return bool
     */
    public function compliesControl($assetMode, $threatMode, $vulnerabilityMode, $assetModelsIds, $threatModelsIds, $vulnerabilityModelsIds, $assetModelsIsRegulator) {

        if (!is_array($assetModelsIds)) {
            $assetModelsIds = [$assetModelsIds];
        }
        if (!is_array($threatModelsIds)) {
            $threatModelsIds = [$threatModelsIds];
        }
        if (!is_array($vulnerabilityModelsIds)) {
            $vulnerabilityModelsIds = [$vulnerabilityModelsIds];
        }
        if (!is_array($assetModelsIsRegulator)) {
            $assetModelsIsRegulator = [$assetModelsIsRegulator];
        }

        if ((!$assetMode) && (!$threatMode) && (!$vulnerabilityMode)) {
            return true;
        } else if (!$assetMode) {
            $this->errorMessage = 'Asset mode can\'t be null';
            return false;
        } else  if ($assetMode && $threatMode && $vulnerabilityMode) {
            foreach ($assetModelsIds as $modelId) {
                if ((in_array($modelId, $threatModelsIds)) && (in_array($modelId, $vulnerabilityModelsIds))) {
                    return true;
                }
            }
            $this->errorMessage = 'One model must be common to asset, threat and vulnerability';
            return false;
        } else {
            foreach ($assetModelsIsRegulator as $modelIsRegulator) {
                if ($modelIsRegulator) {
                    $this->errorMessage = 'All asset models must\'nt be regulator';
                    return false;
                }
            }
            return true;
        }
    }
}