<?php
namespace MonarcCore\Service;

use MonarcCore\Model\Entity\User;
use MonarcCore\Model\Entity\UserRole;
use MonarcCore\Model\Table\UserTable;

/**
 * User Service
 *
 * Class UserService
 * @package MonarcCore\Service
 */
class UserService extends AbstractService
{
    protected $roleTable;
    protected $userEntity;
    protected $mailService;

    /**
     * Get Total Count
     *
     * @return bool|mixed
     */
    public function getTotalCount()
    {
        return $this->get('table')->count();
    }

    /**
     * Get Filtered Count
     *
     * @param int $page
     * @param int $limit
     * @param null $order
     * @param null $filter
     * @return bool|mixed
     */
    public function getFilteredCount($page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null) {

        return $this->get('table')->countFiltered($page, $limit, $this->parseFrontendOrder($order),
            $this->parseFrontendFilter($filter, array('firstname', 'lastname', 'email')));
    }



    /**
     * Create
     *
     * @param $data
     * @throws \Exception
     */
    public function create($data)
    {
        $userEntity = $this->get('entity');
        $userEntity->exchangeArray($data);

        $id = $this->get('table')->save($userEntity);

        //user role
        /** @var UserRoleTable $userRoleTable */
        $userRoleTable = $this->get('roleTable');
        if (array_key_exists('role', $data)) {
            foreach ($data['role'] as $role) {
                $roleData = [
                    'user' => $userEntity,
                    'role' => $role,
                ];

                $userRoleEntity = new UserRole();
                $userRoleEntity->exchangeArray($roleData);

                $userRoleTable->save($userRoleEntity);
            }
        }

        return $id;
    }


    /**
     * Get By Email
     *
     * @param $email
     * @return array
     */
    public function getByEmail($email)
    {
        return $this->get('table')->getByEmail($email);
    }

}
