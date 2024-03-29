<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newEncodedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newEncodedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    public function findUsersByDepartmentOrBossQB($department, $boss, bool $activated = true) {
        $qb = $this->createQueryBuilder('u');
        if ($department !== null) {
            $condition = "
                u.department = :department OR ( u.department != :department2 AND ( u.boss = :boss ) )
                ";
            $qb->andWhere($condition)
                ->setParameter('department', $department)
                ->setParameter('department2', $department)
                ->setParameter('boss', $boss);
        }
        $qb->andWhere('u.activated = :activated')
            ->setParameter('activated', $activated)
            ->orderBy('u.username', 'ASC');
        return $qb;

    }

    public function findByActivedQB(bool $activated) {
        $qb = $this->createQueryBuilder('u');
        $qb->andWhere('u.activated = :activated')
            ->setParameter('activated', $activated)
            ->orderBy('u.username', 'ASC');
        return $qb;
    }

    public function findAllOrderedByUsernameQB(): QueryBuilder {
        return $this->findByActivedQB(true);
    }

    public function findAllOrderedByUsernameAndRoleBossQB(): QueryBuilder {
        return $this->findAllOrderedByUsernameQB()
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role','%ROLE_BOSS%')
            ->orderBy('u.username', 'ASC');    
    }

}
