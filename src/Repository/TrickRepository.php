<?php

namespace App\Repository;

use App\Entity\Trick;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Trick|null find($id, $lockMode = null, $lockVersion = null)
 * @method Trick|null findOneBy(array $criteria, array $orderBy = null)
 * @method Trick[]    findAll()
 * @method Trick[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TrickRepository extends ServiceEntityRepository
{

    public int $NBRE_TRICKS_BY_PAGE=15;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Trick::class);
    }

    public function findOneByTrickId($value): ?Trick
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.id = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getResult();
    }

    public function getTricksFromPage($pageNumber = 2)
    {
        $tricksByPage=$this->NBRE_TRICKS_BY_PAGE;
        $offset = $pageNumber * $tricksByPage;
        $firstResult = $offset - $tricksByPage;
        $lastResult = $offset;
        return $this->createQueryBuilder('t')
            ->setFirstResult($firstResult)
            ->setMaxResults($lastResult)
            ->getQuery()
            ->getResult();
    }
}
