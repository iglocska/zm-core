<?php
namespace MonarcCore\Model\Table;

abstract class AbstractEntityTable
{
    protected $db;
    protected $class;
    protected $language;
    protected $connectedUser;

    public function __construct(\MonarcCore\Model\Db $dbService, $class = null)
    {
        $this->db = $dbService;
        if ($class != null) {
            $this->class = $class;
        } else {
            $thisClassName = get_class($this);
            $classParts = array_filter(explode('\\', $thisClassName));
            $firstClassPart = reset($classParts);
            $lastClassPart = end($classParts);

            $this->class = '\\'.$firstClassPart.'\\Model\\Entity\\' . substr($lastClassPart, 0, -5);
        }
    }
    public function getDb()
    {
        if(!$this->db) {
            $this->db = $this->getServiceLocator()->get('MonarcCore\Model\Db');
        }
        return $this->db;
    }

    public function getRepository()
    {
        return $this->getDb()->getRepository($this->getClass());
    }

    public function getClassMetadata(){
        return $this->getDb()->getClassMetadata($this->getClass());
    }

    public function getClass(){
        return $this->class;
    }

    public function setConnectedUser($cu){
        $this->connectedUser = $cu;
        return $this;
    }
    public function getConnectedUser(){
        return $this->connectedUser;
    }

    public function fetchAll($fields = array())
    {
        $c = $this->getClass();
        if (class_exists($c)) {
            $all = $this->getDb()->fetchAll(new $c());
            $return = array();
            foreach ($all as $a) {
                $return[] = $a->getJsonArray($fields);
            }
            return $return;
        } else {
            return false;
        }
    }
    public function fetchAllObject(){
        $c = $this->getClass();
        if (class_exists($c)) {
            return $this->getDb()->fetchAll(new $c());
        }else{
            return false;
        }
    }

    public function fetchAllFiltered($fields = array(), $page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null, $filterJoin = null, $filterLeft = null)
    {
        $class = $this->getClass();

        if (class_exists($class)) {
            $all = $this->getDb()->fetchAllFiltered(new $class(), $page, $limit, $order, $filter, $filterAnd, $filterJoin, $filterLeft);
            $return = array();
            foreach ($all as $a) {
                $return[] = $a->getJsonArray($fields);
            }
            return $return;
        } else {
            return false;
        }
    }

    public function count()
    {
        $c = $this->getClass();
        if (class_exists($c)) {
            return $this->getDb()->count(new $c());
        } else {
            return false;
        }
    }

    public function countFiltered($page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null, $filterJoin = null, $filterLeft = null)
    {
        $c = $this->getClass();
        if (class_exists($c)) {
            return $this->getDb()->countFiltered(new $c(), $limit, $order, $filter, $filterAnd, $filterJoin, $filterLeft);
        } else {
            return false;
        }
    }

    public function get($id, $fields = array())
    {
        $ent = $this->getEntity($id);
        if ($ent !== false) {
            return $ent->getJsonArray($fields);
        } else {
            return false;
        }
    }

    public function getEntity($id)
    {
        $class = $this->getClass();
        if(class_exists($class)){
            $entity = new $class();
            $entity->setDbAdapter($this->getDb());
            $entity->set('id',$id);
            $entity = $this->getDb()->fetch($entity);
            if (!$entity) {
                throw new \Exception('Entity does not exist', 412);
            }
            $entity->initParametersChanges();
            return $entity;
        }else{
            return false;
        }
    }

    public function getEntityByFields($fields = array(), $orderBy = array()) {
        $class = $this->getClass();
        if (class_exists($class)) {
            $entity = new $class();
            $entity->setDbAdapter($this->getDb());

            return $this->getDb()->fetchByFields($entity, $fields, $orderBy);
        } else {
            return false;
        }
    }

    public function save(\MonarcCore\Model\Entity\AbstractEntity &$entity, $last = true)
    {
        if(!empty($this->connectedUser) && isset($this->connectedUser['firstname']) && isset($this->connectedUser['lastname'])){
            $id = $entity->get('id');
            if(empty($id)){
                if($entity->__isset('creator')){
                    $entity->set('creator',trim($this->connectedUser['firstname']." ".$this->connectedUser['lastname']));
                }
                if($entity->__isset('createdAt')){
                    $entity->set('createdAt',new \DateTime());
                }
            }else{
                if($entity->__isset('updater')){
                    $entity->set('updater',trim($this->connectedUser['firstname']." ".$this->connectedUser['lastname']));
                }
                if($entity->__isset('updatedAt')){
                    $entity->set('updatedAt',new \DateTime());
                }
            }
        }

        $params = $entity->get('parameters');
        $clean_params = false;
        if(isset($params['implicitPosition']['changes'])){
            if(isset($entity->parameters['implicitPosition']['root']) && ( ! $entity->id || $params['implicitPosition']['changes']['parent']['before'] != $params['implicitPosition']['changes']['parent']['after'])){
                $this->updateRootTree($entity, ! $entity->id, $params['implicitPosition']['changes']);
                $clean_params = true;
            }

            if(! $entity->id
                || (isset($params['implicitPosition']['changes']['parent']) && $params['implicitPosition']['changes']['parent']['before'] != $params['implicitPosition']['changes']['parent']['after'])
                || (isset($params['implicitPosition']['changes']['position']) && $params['implicitPosition']['changes']['position']['before'] != $params['implicitPosition']['changes']['position']['after'])
            ){
                $this->autopose($entity, ! $entity->id, $params['implicitPosition']['changes']);
                $clean_params = true;
            }
        }

        if($clean_params){
            unset($params['implicitPosition']['changes']);
        }

        $id = $this->getDb()->save($entity, $last);

        return $id;
    }

    protected function updateRootTree($entity, $was_new, $changes = []){
        $this->initTree($entity, 'position');//need to be called first to allow tree repositionning
        $rootField = $entity->parameters['implicitPosition']['root'];
        if( ! is_null($entity->get($entity->parameters['implicitPosition']['field']))) {
            $father = $this->getEntity($entity->get($entity->parameters['implicitPosition']['field'])->get('id'));
            $entity->set($rootField, ($father->get($rootField) === null) ? $father : $father->get($rootField));
        }
        else{
            $entity->set($rootField, null);
        }

        if( ! $was_new && $changes['parent']['before'] != $changes['parent']['after']){
            $temp = isset($entity->parameters['children']) ? $entity->parameters['children'] : [];
            while( ! empty($temp) ){
                $sub = array_shift($temp);
                $sub->set($rootField, ((is_null($entity->get($rootField))) ? $entity : $entity->get($rootField) ) );
                $this->save($sub, false);
                if(!empty($sub->parameters['children'])){
                    foreach($sub->parameters['children'] as $subsub){
                        array_unshift($temp, $subsub);
                    }
                }
            }
        }
    }

    protected function autopose($entity, $was_new, $changes = [], $force_new = false){
        //objects could be sorted even if they haven't a parent field
        $isParentable = ! isset($entity->parameters['isParentRelative']) || $entity->parameters['isParentRelative'];
        /*
        * MEMO :
        * Be sure that the corresponding service has its parent dependency declared
        * and the create or update method calls $this->setDependencies($entity, $this->dependencies).
        * This required the injection of the parentTable in the factory of your Service
        */
        if($was_new || $force_new){
            $parentfield = $isParentable ? $entity->parameters['implicitPosition']['field'] : null;

            $params = [
                ':position' => $entity->get('position'),
                ':id'       => $entity->get('id') === null ? '' : $entity->get('id') //specific to the TIPs below
                ];

            $parentWhere = "1 = 1";

            if( $isParentable ){
                $parentWhere = 'bro.'.$entity->parameters['implicitPosition']['field'] . ' = :parentid';
                if(is_null($entity->get($entity->parameters['implicitPosition']['field']))){
                    $parentWhere = 'bro.'.$entity->parameters['implicitPosition']['field'] . ' IS NULL';
                }
                else{
                    $params[':parentid'] = $entity->get($entity->parameters['implicitPosition']['field'])->get('id');
                }
            }
            $bros = $this->getRepository()->createQueryBuilder('bro')
                         ->select()
                         ->where( $parentWhere )
                         ->andWhere('bro.position >= :position')
                         ->andWhere('bro.id != :id')
                         ->setParameters( $params )
                         ->getQuery()->getResult();

            if(!empty($bros)){
                foreach($bros as $bro){
                    $bro->set('position', $bro->get('position') + 1);
                    $this->save($bro);
                }
            }
        }
        else if(!empty($changes['parent']) && $changes['parent']['before'] != $changes['parent']['after']){//this is somewhat like we was new but we need to redistribute brothers
            $params = [
                ':position' => ! empty($changes['position']['before']) ? $changes['position']['before'] : $entity->get('position'),
                ':id'       => $entity->get('id')
                ];

            $parentWhere = "1 = 1";

            if( $isParentable ){//by security - inverse never should happen in this case
                $parentWhere = 'bro.'.$entity->parameters['implicitPosition']['field'] . ' = :parentid';
                if(is_null($changes['parent']['before'])) {
                    $parentWhere = 'bro.'.$entity->parameters['implicitPosition']['field'] . ' IS NULL';
                }
                else{
                    $params[':parentid'] = $changes['parent']['before'];
                }
            }

            $bros = $this->getRepository()->createQueryBuilder('bro')
                         ->select()
                         ->where( $parentWhere )
                         ->andWhere('bro.position >= :position')
                         ->andWhere('bro.id != :id')
                         ->setParameters( $params )
                         ->getQuery()->getResult();

            if(!empty($bros)){
                foreach($bros as $bro){
                    $bro->set('position', $bro->get('position') - 1);//get down old pals
                    $this->save($bro);
                }
            }

            $this->autopose($entity, $was_new, $changes, true);//TIPS : we simulate the new option to move new brothers up
        }
        else{//we're not new, the parent is the same, so we "just" have to change internal positions
            $avant = $changes['position']['before'];
            $apres = $changes['position']['after'];// == $entity->get('position')

            $params = [
                ':apres'    => $apres,
                ':avant'    => $avant,
                ':id'       => $entity->get('id')
                ];

            $parentWhere = "1 = 1";

            if( $isParentable ){
                $parentWhere = 'bro.'.$entity->parameters['implicitPosition']['field'] . ' = :parentid';
                if(is_null($entity->get($entity->parameters['implicitPosition']['field']))){
                    $parentWhere = 'bro.'.$entity->parameters['implicitPosition']['field'] . ' IS NULL';
                }
                else{
                    $params[':parentid'] = $entity->get($entity->parameters['implicitPosition']['field'])->get('id');
                }
            }

            $bros = $this->getRepository()->createQueryBuilder('bro')
                         ->select()
                         ->where( $parentWhere )
                         ->andWhere('bro.position '.(($avant > $apres) ? '>=' : '<=').' :apres')
                         ->andWhere('bro.position '.(($avant > $apres) ? '<' : '>').' :avant')
                         ->andWhere('bro.id != :id')
                         ->setParameters( $params )
                         ->getQuery()->getResult();

            if(!empty($bros)){
                foreach($bros as $bro){
                    $bro->set('position', ($avant > $apres) ? $bro->get('position') + 1 : $bro->get('position') - 1 );
                    $this->save($bro);
                }
            }
        }
    }

    public function delete($id, $last = true)
    {
        $c = $this->getClass();
        if(class_exists($c)){
            $id  = (int) $id;

            $entity = new $c();
            $entity->set('id',$id);
            $entity = $this->getDb()->fetch($entity);

            $params = $entity->get('parameters');
            if(!empty($params['implicitPosition'])){
                $this->manageDeletePosition($entity,$params['implicitPosition']);
            }

            $this->getDb()->delete($entity, $last);
            return true;
        }else{
            return false;
        }
    }

    protected function manageDeletePosition(\MonarcCore\Model\Entity\AbstractEntity $entity,$params = array()){
        $return = $this->getRepository()->createQueryBuilder('t')
            ->update()
            ->set('t.position', 't.position - 1');
        $hasWhere = false;
        if(!empty($params['field'])){
            $hasWhere = true;
            if(is_null($entity->get($params['field']))){
                $return = $return->where('t.'.$params['field'].' IS NULL');
            }else{
                $return = $return->where('t.' . $params['field'] . ' = :'.$params['field'])
                    ->setParameter(':'.$params['field'], $entity->get($params['field']));
            }
        }
        if($hasWhere){
            $return = $return->andWhere('t.position >= :pos');
        }else{
            $return = $return->where('t.position >= :pos');
        }
        $return = $return->setParameter(':pos', $entity->get('position'));
        $return->getQuery()->getResult();
    }

    public function deleteList($data){
        $c = $this->getClass();
        if(class_exists($c) && is_array($data)){
            $entity = new $c();
            $entities = $this->getDb()->fetchByIds($entity,$data);
            if(!empty($entities)){
                $params = $entity->get('parameters');
                if(!empty($params['implicitPosition'])){
                    // C'est un peu bourrin
                    foreach($entities as $e){
                        $this->manageDeletePosition($e,$params['implicitPosition']);
                    }
                }
                $this->getDb()->deleteAll($entities);
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * Change positions by parent
     *
     * @param $field
     * @param $parentId
     * @param $position
     * @param string $direction
     * @param string $referential
     * @param bool $strict
     * @return array
     */
    public function changePositionsByParent($field = 'parent', $parentId, $position, $direction = 'up', $referential = 'after', $strict = false)
    {
        $positionDirection = ($direction == 'up') ? '+1' : '-1';
        $sign = ($referential == 'after') ? '>' : '<';
        if (!$strict) {
            $sign .= '=';
        }

        $return = $this->getRepository()->createQueryBuilder('t')
            ->update()
            ->set('t.position', 't.position' . $positionDirection);
        if(empty($parentId)){
            $return = $return->where('t.' . $field . ' IS NULL');
        }else{
            $return = $return->where('t.' . $field . ' = :parentid')
            ->setParameter(':parentid', $parentId);

        }

        $return = $return->andWhere('t.position ' . $sign . ' :position')
            ->setParameter(':position', $position)
            ->getQuery();

        $return->getResult();
        return $return;
    }

    /**
     * Change positions
     *
     * @param $position
     * @param string $direction
     * @param string $referential
     * @param bool $strict
     * @return \Doctrine\ORM\Query
     */
    public function changePositions($position, $direction = 'up', $referential = 'after', $strict = false)
    {
        $positionDirection = ($direction == 'up') ? '+1' : '-1';
        $sign = ($referential == 'after') ? '>' : '<';
        if (!$strict) {
            $sign .= '=';
        }

        $return = $this->getRepository()->createQueryBuilder('t')
            ->update()
            ->set('t.position', 't.position' . $positionDirection)
            ->andWhere('t.position ' . $sign . ' :position')
            ->setParameter(':position', $position)
            ->getQuery();

        $return->getResult();
        return $return;
    }

    /**
     * Max Position By Parent
     *
     * @param $field
     * @param $parentId
     * @return mixed
     */
    public function maxPositionByParent($field, $parentId)
    {
        $maxPosition = $this->getRepository()->createQueryBuilder('t')
            ->select(array('max(t.position)'));
        if(empty($parentId)){
            $maxPosition = $maxPosition->where('t.' . $field . ' IS NULL');
        }else{
            $maxPosition = $maxPosition->where('t.' . $field . ' = :parentid')
            ->setParameter(':parentid', $parentId);
        }
        $maxPosition = $maxPosition->getQuery()
            ->getResult();

        return $maxPosition[0][1];
    }

    public function getReference($id){
        return $this->getDb()->getReference($this->getClass(),$id);
    }



    /**
     * Get Descendants
     * @param $id
     * @return array
     */
    public function getDescendants($id) {

        $childList = [];

        $this->getRecursiveChild($childList, $id);

        return $childList;
    }

    /**
     * Get Recursive Child
     *
     * @param $childList
     * @param $id
     */
    protected function getRecursiveChild(&$childList, $id) {
        $children = $this->getRepository()->createQueryBuilder('t')
            ->select(array('t.id'))
            ->where('t.parent = :parent')
            ->setParameter(':parent', $id)
            ->getQuery()
            ->getResult();

        if (count($children)) {
            foreach ($children as $child) {
                $childList[] = $child['id'];
                $this->getRecursiveChild($childList, $child['id']);
            }
        }
    }


    public function getDescendantsObjects($id) {

        $childList = [];

        $this->getRecursiveChildObjects($childList, $id);

        return $childList;
    }

    protected function getRecursiveChildObjects(&$childList, $id) {

        $children = $this->getRepository()->createQueryBuilder('t')
            ->select()
            ->where('t.parent = :parent')
            ->setParameter(':parent', $id)
            ->getQuery()
            ->getResult();

        foreach ($children as $child) {
            $childList[] = $child;
            $this->getRecursiveChildObjects($childList, $child->id);
        }
    }

    //optimized method to avoid recursive call with multiple SQL queries
    public function initTree($entity, $order_by = null){
        $rootField = isset($entity->parameters['implicitPosition']['root']) ? $entity->parameters['implicitPosition']['root'] : 'root';
        $parentField = isset($entity->parameters['implicitPosition']['field']) ? $entity->parameters['implicitPosition']['field'] : 'parent';

        if(is_null($entity->get($rootField))) $ref = $entity->get('id');
        else $ref = $entity->get($rootField)->get('id');

        $qb = $this->getRepository()->createQueryBuilder('t');

        $qb->select()
           ->where('t.'.$rootField.' = :ref')
           ->setParameter(':ref', $ref);

        if(!is_null($order_by)){
            $qb->orderBy('t.'.$order_by, 'DESC');
        }

        $descendants = $qb->getQuery()->getResult();

        $family = array();
        foreach($descendants as $c){
            //root is null but [null] on an array is not pretty cool
            $family[is_null($c->get($parentField)) ? 0 : $c->get($parentField)->get('id')][] = $c;
        }

        if( ! empty($family)){
            $temp = array();
            $temp[] = $entity;
            while(!empty($temp)){
                $current = array_shift($temp);
                if(!empty($family[$current->get('id')])){
                    foreach($family[$current->get('id')] as $fam){
                        $params = [];
                        if( ! isset($current->parameters['children'])){
                            $current->setParameter('children', []);
                        }
                        else{
                            $params = $current->parameters['children'];
                        }
                        $params[$fam->get('id')] = $fam;
                        $current->setParameter('children', $params);
                        array_unshift($temp, $fam);
                    }
                }
            }
        }
    }
}
