<?php
namespace MonarcCore\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use DoctrineModule\Persistence\ProvidesObjectManager;
use MonarcCore\Model\Entity\Threat;

/**
 * Threat Service
 *
 * Class ThreatService
 * @package MonarcCore\Service
 */
class ThreatService extends AbstractService implements ObjectManagerAwareInterface
{
    use ProvidesObjectManager;

    protected $modelService;

    protected $themeService;

    protected $filterColumns = [
        'label1', 'label2', 'label3', 'label4',
        'description1', 'description2', 'description3', 'description4',
        'code'
    ];

    /**
     * @return mixed
     */
    public function getModelService()
    {
        return $this->modelService;
    }

    /**
     * @param mixed $modelService
     * @return AssetService
     */
    public function setModelService($modelService)
    {
        $this->modelService = $modelService;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getThemeService()
    {
        return $this->themeService;
    }

    /**
     * @param mixed $themeService
     * @return ThreatService
     */
    public function setThemeService($themeService)
    {
        $this->themeService = $themeService;
        return $this;
    }

    /**
     * Create
     *
     * @param $data
     * @throws \Exception
     */
    public function create($data) {

        $assetEntity = new Asset();
        $assetEntity = $this->addModel($assetEntity, $data);
        $assetEntity->exchangeArray($data);

        return $this->save($assetEntity);
    }

    /**
     * Update Entity
     *
     * @param $id
     * @param $data
     */
    public function update($id, $data) {

        $assetEntity = $this->getEntity($id);
        $assetEntity->setModels(new ArrayCollection());
        $assetEntity = $this->addModel($assetEntity, $data);
        $assetEntity->exchangeArray($data);

        $connectedUser = trim($this->getConnectedUser()['firstname'] . " " . $this->getConnectedUser()['lastname']);

        $assetEntity->set('updater', $connectedUser);
        $assetEntity->set('updatedAt',new \DateTime());

        $this->objectManager->persist($assetEntity);
        $this->objectManager->flush();
    }
}