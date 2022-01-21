<?php

namespace App\Repository;

use App\Entity\Media;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Media|null find($id, $lockMode = null, $lockVersion = null)
 * @method Media|null findOneBy(array $criteria, array $orderBy = null)
 * @method Media[]    findAll()
 * @method Media[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Media::class);
    }

    /**
     * @param int $value
     * @return array
     */

    public function findByTrickId(int $value): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.trick = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            /*->setMaxResults(10)*/
            ->getQuery()
            ->getResult()
        ;
    }

    public function findMainMediaWithTrickId(int $trickId): Media
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.trick = :val')
            ->andWhere('m.isMain=true')
            ->setParameter('val', $trickId)
            ->orderBy('m.id', 'ASC')
            /*->setMaxResults(10)*/
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }


    /*
    public function findOneBySomeField($value): ?Media
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
