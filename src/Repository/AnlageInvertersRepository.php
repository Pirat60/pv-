<?php

namespace App\Repository;

use App\Entity\AnlageInverters;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AnlageInverters|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnlageInverters|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnlageInverters[]    findAll()
 * @method AnlageInverters[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnlageInvertersRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnlageInverters::class);
    }

    // /**
    //  * @return AnlageInverters[] Returns an array of AnlageInverters objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?AnlageInverters
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
