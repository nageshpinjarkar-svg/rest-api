<?php

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;
use AppBundle\Entity\User;
use AppBundle\Entity\Programmer;

class ProgrammerRepository extends EntityRepository
{
    /**
     * @param User $user
     * @return Programmer[]
     */
    public function findAllForUser(User $user)
    {
        return $this->findBy(array('user' => $user));
    }

    /**
     * @param $nickname
     * @return Programmer
     */
    public function findOneByNickname($nickname)
    {
        return $this->findOneBy(array('nickname' => $nickname));
    }
    
    // just return $this->createQueryBuilder(); with an alias of programmer 
    // public function findAllQueryBuilder() {
    //     return $this->createQueryBuilder('programmer'); 
    // }

    // filter argument optional
    public function findAllQueryBuilder($filter = ''){
        // the query builder
        $qb = $this->createQueryBuilder('programmer');

        if ($filter) {
            // the query
            $qb->andWhere('programmer.nickname LIKE :filter OR programmer.tagLine LIKE :filter')
                ->setParameter('filter', '%'.$filter.'%'); 
        }
            return $qb;
        }
}
