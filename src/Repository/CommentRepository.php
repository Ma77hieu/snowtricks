<?php

namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Comment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comment[]    findAll()
 * @method Comment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /**
     * @param int $value
     * @return int|mixed|string
     */

    public function findByTrickId(int $value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.trick = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            /*->setMaxResults(10)*/
            ->getQuery()
            ->getResult()
        ;
    }

    public function findOkComsTrickId(int $trickId)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.trick = :val')
            ->andWhere('c.isValidated=true')
            ->setParameter('val', $trickId)
            ->orderBy('c.id', 'ASC')
            /*->setMaxResults(10)*/
            ->getQuery()
            ->getResult()
            ;
    }


    /**
     * @return Comment[] Returns an array of Comment objects
     */
    public function findByValidationStatus($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.isValidated = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getResult()
        ;
    }
}
