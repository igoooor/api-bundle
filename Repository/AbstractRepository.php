<?php
/**
 * Created by PhpStorm.
 * User: igorweigel
 * Date: 18.06.2020
 * Time: 10:15
 */

namespace Igoooor\ApiBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * Class AbstractRepository
 */
abstract class AbstractRepository extends ServiceEntityRepository
{
    /**
     * @param mixed $object
     * @param bool  $andFlush
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function persist($object, bool $andFlush = true): void
    {
        $this->_em->persist($object);
        if (true === $andFlush) {
            $this->flush();
        }
    }

    /**
     * @param mixed $object
     * @param bool  $andFlush
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function remove($object, bool $andFlush = true): void
    {
        $this->_em->remove($object);
        if (true === $andFlush) {
            $this->flush();
        }
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function flush(): void
    {
        $this->_em->flush();
    }
}
