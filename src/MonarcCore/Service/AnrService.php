<?php
namespace MonarcCore\Service;

/**
 * Anr Service
 *
 * Class AnrService
 * @package MonarcCore\Service
 */
class AnrService extends AbstractService
{
    protected $scaleService;

    /**
     * Create
     *
     * @param $data
     * @throws \Exception
     */
    public function create($data)
    {
        //anr
        $entity = $this->get('entity');
        $entity->exchangeArray($data);
        $anrId = $this->get('table')->save($entity);

        //scales
        $scales = [
            ['anr' => $anrId, 'type' => 1, 'min' => 0, 'max' => 3],
            ['anr' => $anrId, 'type' => 2, 'min' => 0, 'max' => 4],
            ['anr' => $anrId, 'type' => 3, 'min' => 0, 'max' => 3],
        ];
        foreach ($scales as $scale) {
            $this->get('scaleService')->create($scale);
        }

        return $anrId;
    }
}