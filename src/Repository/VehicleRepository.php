<?php

namespace App\Repository;

use App\Entity\Vehicle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Vehicle>
 */
class VehicleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vehicle::class);
    }

//    /**
//     * @return Vehicle[] Returns an array of Vehicle objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('v')
//            ->andWhere('v.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('v.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Vehicle
//    {
//        return $this->createQueryBuilder('v')
//            ->andWhere('v.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    public function findByFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('v');

        if (!empty($filters['search'])) {
            $qb->andWhere('v.brand LIKE :search OR v.model LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['type'])) {
            $qb->andWhere('v.type = :type')
               ->setParameter('type', $filters['type']);
        }

        if (!empty($filters['maxPrice'])) {
            $qb->andWhere('v.pricePerDay <= :maxPrice')
               ->setParameter('maxPrice', $filters['maxPrice']);
        }

        if (isset($filters['available']) && $filters['available'] !== null) {
            $qb->andWhere('v.isAvailable = :available')
               ->setParameter('available', $filters['available']);
        }

        return $qb->orderBy('v.brand', 'ASC')
                 ->addOrderBy('v.model', 'ASC')
                 ->getQuery()
                 ->getResult();
    }
}
