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
            throw new \Exception('Not Authorized');
        }

        return $this->get('table')->save($entity);
    }

    /**
     * Update
     *
     * @param $id
     * @param $data
     * @return mixed
     */
    public function update($id, $data){

        $entity = $this->get('table')->getEntity($id);
        $entity->exchangeArray($data);

        foreach($this->dependencies as $dependency) {
            $fieldValue = isset($data[$dependency]) ? $data[$dependency] : array();

            if (!empty($fieldValue)) {
                $tableName = preg_replace("/[0-9]/", "", $dependency)  . 'Table';
                $method = 'set' . ucfirst($dependency);
                $dependencyEntity = $this->get($tableName)->getEntity($fieldValue);
                $entity->$method($dependencyEntity);
            }
        }

        return $this->get('table')->save($entity);
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

        if ((!$assetMode) && (!$threatMode) && (!$vulnerabilityMode)) {
            return true;
        } else if (!$assetMode) {
            return false;
        } else  if ($assetMode && $threatMode && $vulnerabilityMode) {

            $threatModels = [];
            foreach ($amv->getThreat()->getModels() as $model) {
                $threatModels[] = $model->id;
            }

            $vulnerabilityModels = [];
            foreach ($amv->getVulnerability()->getModels() as $model) {
                $vulnerabilityModels[] = $model->id;
            }

            foreach ($amv->getAsset()->getModels() as $model) {
                if ((in_array($model->id, $threatModels)) && (in_array($model->id, $vulnerabilityModels))) {
                    return true;
                }
            }
            return false;
        } else {
            foreach ($amv->getAsset()->getModels() as $model) {
                if ($model->isRegulator) {
                    return false;
                }
            }
            return true;
        }
    }
}