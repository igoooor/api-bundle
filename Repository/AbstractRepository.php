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
    public function flush(): void
    {
        $this->_em->flush();
    }

    public function persist(...$entities): void
    {
        foreach ($entities as $entity) {
            $this->_em->persist($entity);
        }
    }

    public function remove(...$entities): void
    {
        foreach ($entities as $entity) {
            $this->_em->remove($entity);
        }
    }

    public function save(...$entities): void
    {
        $this->persist(...$entities);
        $this->flush();
    }

    public function delete(...$entities): void
    {
        $this->remove(...$entities);
        $this->flush();
    }
}
