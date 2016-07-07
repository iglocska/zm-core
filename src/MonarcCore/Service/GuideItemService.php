<?php
namespace MonarcCore\Service;

/**
 * Guide Item Service
 *
 * Class GuideItemService
 * @package MonarcCore\Service
 */
class GuideItemService extends AbstractService
{
    protected $guideTable;

    protected $dependencies = ['guide'];

    /**
     * Create
     *
     * @param $data
     * @throws \Exception
     */
    public function create($data) {

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];

        $entity = $this->get('entity');

        $previous = (array_key_exists('previous', $data)) ? $data['previous'] : null;
        $guide = (array_key_exists('guide', $data)) ? $data['guide'] : null;

        $position = $this->managePositionCreation('guide', $guide, (int) $data['implicitPosition'], $previous);
        $data['position'] = $position;

        $entity->exchangeArray($data);

        $this->setDependencies($entity, $dependencies);

        return $this->get('table')->save($entity);
    }

    /**
     * Update
     *
     * @param $id
     * @param $data
     * @return mixed
     */
    public function update($id,$data){

        $previous = (array_key_exists('previous', $data)) ? $data['previous'] : null;
        $guide = (array_key_exists('guide', $data)) ? $data['guide'] : null;

        $entity = $this->get('table')->getEntity($id);

        if (array_key_exists('implicitPosition', $data)) {
            $data['position'] = $this->managePositionUpdate('guide', $entity, $guide, $data['implicitPosition'], $previous);
        }

        $entity = $this->get('table')->getEntity($id);
        $entity->exchangeArray($data);

        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);


        return $this->get('table')->save($entity);
    }

    /**
     * Delete
     *
     * @param $id
     */
    public function delete($id) {

        $entity = $this->getEntity($id);

        $entityGuideId = $entity['guide']->id;
        $position = $entity['position'];

        $this->get('table')->changePositionsByParent('guide', $entityGuideId, $position, 'down', 'after');

        $this->get('table')->delete($id);
    }

}