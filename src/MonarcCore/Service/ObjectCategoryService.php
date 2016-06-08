<?php
namespace MonarcCore\Service;

/**
 * Object Category Service
 *
 * Class ObjectCategoryService
 * @package MonarcCore\Service
 */
class ObjectCategoryService extends AbstractService
{
    protected $filterColumns = ['label1', 'label2', 'label3', 'label4',];

    /**
     * Create
     *
     * @param $data
     * @throws \Exception
     */
    public function create($data) {

        $entity = $this->get('entity');

        $previous = (array_key_exists('previous', $data)) ? $data['previous'] : null;
        $parent = (array_key_exists('parent', $data)) ? $data['parent'] : null;

        $position = $this->managePositionCreation($parent, (int) $data['implicitPosition'], $previous);
        $data['position'] = $position;

        $entity->exchangeArray($data);

        //parent and root
        $parentValue = $entity->get('parent');
        if (!empty($parentValue)) {
            $parentEntity = $this->get('table')->getEntity($parentValue);
            $entity->setParent($parentEntity);

            $rootEntity = $this->getRoot($entity);
            $entity->setRoot($rootEntity);
        } else {
            $entity->setParent(null);
            $entity->setRoot(null);
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

        //parent and root
        $parentValue = $entity->get('parent');
        if (!empty($parentValue)) {
            $parentEntity = $this->get('table')->getEntity($parentValue);
            $entity->setParent($parentEntity);

            $rootEntity = $this->getRoot($entity);
            $entity->setRoot($rootEntity);
        } else {
            $entity->setParent(null);
            $entity->setRoot(null);
        }

        if ((! array_key_exists('parent', $data)) || (is_null($data['parent']))) {
            $entity->setParent(null);
            $entity->setRoot(null);
        }

        return $this->get('table')->save($entity);
    }

    /**
     * Delete
     *
     * @param $id
     */
    public function delete($id) {

        $this->get('table')->getRepository()->createQueryBuilder('t')
            ->update()
            ->set('t.parent', ':parentset')
            ->where('t.parent = :parentwhere')
            ->setParameter(':parentset', null)
            ->setParameter(':parentwhere', $id)
            ->getQuery()
            ->getResult();

        $this->get('table')->getRepository()->createQueryBuilder('t')
            ->update()
            ->set('t.root', ':rootset')
            ->where('t.root = :rootwhere')
            ->setParameter(':rootset', null)
            ->setParameter(':rootwhere', $id)
            ->getQuery()
            ->getResult();

        $this->get('table')->delete($id);
    }

    /**
     * Get root
     *
     * @param $entity
     * @return mixed
     */
    public function getRoot($entity) {
        if (!is_null($entity->getParent())) {
            return $this->getRoot($entity->getParent());
        } else {
            return $entity;
        }
    }


    /**
     * Manage position
     *
     * @param $parentId
     * @param $implicitPosition
     * @param null $previous
     * @return int
     */
    protected function managePositionCreation($parentId, $implicitPosition, $previous = null) {
        $position = 1;

        switch ($implicitPosition) {
            case 1:
                $this->get('table')->changePositionsByParent($parentId, 1, 'up', 'after');
                $position = 1;
                break;
            case 2:
                $maxPosition = $this->get('table')->maxPositionByCategory($parentId);
                $position = $maxPosition + 1;
                break;
            case 3:
                $previousObject = $this->get('table')->getEntity($previous);
                $this->get('table')->changePositionsByParent($parentId, $previousObject->position + 1, 'up', 'after');
                $position = $previousObject->position + 1;
                break;
        }

        return $position;
    }
}