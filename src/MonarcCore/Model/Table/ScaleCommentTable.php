<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Model\Table;

/**
 * Class ScaleCommentTable
 * @package MonarcCore\Model\Table
 */
class ScaleCommentTable extends AbstractEntityTable
{
    /**
     * Get By Scale
     *
     * @param $scaleId
     * @return mixed
     * @throws \Exception
     */
    public function getByScale($scaleId)
    {
        $comments = $this->getRepository()->createQueryBuilder('s')
            ->select(array('s.val', 'IDENTITY(s.scaleImpactType) as scaleImpactType', 's.comment1', 's.comment2', 's.comment3', 's.comment4'))
            ->where('s.scale = :scaleId')
            ->setParameter(':scaleId', $scaleId)
            ->getQuery()
            ->getResult();

        return $comments;
    }

    /**
     * Get By Scale And Out Of Range
     *
     * @param $scaleId
     * @param $min
     * @param $max
     * @return array
     */
    public function getByScaleAndOutOfRange($scaleId, $min, $max)
    {
        $comments = $this->getRepository()->createQueryBuilder('s')
            ->select(array('s.id', 's.val', 'IDENTITY(s.scaleImpactType) as scaleImpactType', 's.comment1', 's.comment2', 's.comment3', 's.comment4'))
            ->where('s.scale = :scaleId AND (s.val > :max OR s.val < :min)')
            ->setParameter(':scaleId', $scaleId)
            ->setParameter(':min', $min)
            ->setParameter(':max', $max)
            ->getQuery()
            ->getResult();

        return $comments;
    }
}