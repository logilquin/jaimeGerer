<?php

namespace AppBundle\Entity\Compta;

use Doctrine\ORM\EntityRepository;

/**
 * JournalAchatRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class JournalAchatRepository extends EntityRepository
{
	public function findJournalEntier($company, $year){

		$result = $this->createQueryBuilder('j')
			->leftJoin('AppBundle\Entity\Compta\Depense', 'd', 'WITH', 'j.depense = d.id')
			->leftJoin('AppBundle\Entity\CRM\Compte', 'c1', 'WITH', 'd.compte = c1.id')
			->leftJoin('AppBundle\Entity\Compta\Avoir', 'a', 'WITH', 'j.avoir = a.id')
			->leftJoin('AppBundle\Entity\Compta\Depense', 'd2', 'WITH', 'a.depense = d2.id')
			->leftJoin('AppBundle\Entity\NDF\NoteFrais', 'n', 'WITH', 'd.noteFrais = n.id')
			->leftJoin('AppBundle\Entity\Compta\CompteComptable', 'cc', 'WITH', 'cc.id = n.compteComptable')
			->leftJoin('AppBundle\Entity\CRM\Compte', 'c2', 'WITH', 'd2.compte = c2.id')
			->where('c1.company = :company or c2.company = :company or cc.company = :company')
			->andWhere('(d.dateCreation >= :startDate and d.dateCreation <= :endDate) or (a.dateCreation >= :startDate and a.dateCreation <= :endDate)')
			->setParameter('startDate', $year.'-01-01')
			->setParameter('endDate', $year.'-12-31')
			->setParameter('company', $company)
			->orderBy('d.date', 'DESC')
			->orderBy('a.dateCreation', 'DESC')
			->addOrderBy('j.depense', 'DESC')
			->addOrderBy('j.debit', 'ASC')
			->getQuery()
			->getResult();

		return $result;
	}

	public function findByCompteForCompany($compteComptable, $company, $startDate = null, $endDate = null){

		$qb = $this->createQueryBuilder('j')
			->leftJoin('AppBundle\Entity\Compta\Depense', 'd', 'WITH', 'j.depense = d.id')
			->leftJoin('AppBundle\Entity\CRM\Compte', 'c1', 'WITH', 'd.compte = c1.id')
			->leftJoin('AppBundle\Entity\Compta\Avoir', 'a', 'WITH', 'j.avoir = a.id')
			->leftJoin('AppBundle\Entity\Compta\Depense', 'd2', 'WITH', 'a.depense = d2.id')
			->leftJoin('AppBundle\Entity\CRM\Compte', 'c2', 'WITH', 'd2.compte = c2.id')
			->where('c1.company = :company or c2.company = :company')
			->andWhere('j.compteComptable = :compteComptable')
			->setParameter('company', $company)
			->setParameter('compteComptable', $compteComptable);

			if($startDate && $endDate){
				$qb->andWhere('(d.dateCreation >= :startDate and d.dateCreation <= :endDate) or (a.dateCreation >= :startDate and a.dateCreation <= :endDate)')
					->setParameter('startDate', $startDate)
					->setParameter('endDate', $endDate);
			}

			$qb->orderBy('d.dateCreation', 'ASC')
			->addOrderBy('j.debit', 'DESC');

			$result = $qb->getQuery()
			->getResult();

		return $result;
	}

	public function findCompteAttenteACorriger($compteComptable, $company){

		$queryBuilder  = $this->_em->createQueryBuilder();

		$subQueryBuilder = $this->_em->createQueryBuilder();
		$subQueryBuilder->select('od')
			->from('AppBundle\Entity\Compta\OperationDiverse', 'od')
			->where('j.depense = od.depense')
			->orWhere('j.avoir = od.avoir');

		$query = $this->createQueryBuilder('j')
			->leftJoin('AppBundle\Entity\Compta\Depense', 'd', 'WITH', 'j.depense = d.id')
			->leftJoin('AppBundle\Entity\CRM\Compte', 'c1', 'WITH', 'd.compte = c1.id')
			->leftJoin('AppBundle\Entity\Compta\Avoir', 'a', 'WITH', 'j.avoir = a.id')
			->leftJoin('AppBundle\Entity\Compta\Depense', 'd2', 'WITH', 'a.depense = d2.id')
			->leftJoin('AppBundle\Entity\CRM\Compte', 'c2', 'WITH', 'd2.compte = c2.id')
			->where('c1.company = :company or c2.company = :company')
			->andWhere('j.compteComptable = :compteComptable')
			->setParameter('company', $company)
			->setParameter('compteComptable', $compteComptable);

		$query->andWhere($queryBuilder->expr()->not($queryBuilder->expr()->exists($subQueryBuilder->getDQL())));

		$result = $query->orderBy('d.num', 'ASC')
			->addOrderBy('j.debit', 'DESC')
			->getQuery()
			->getResult();

		return $result;
	}

	public function findInverse($ligne){
		$result = $this->createQueryBuilder('j')
		->where('j.depense = :depense or j.avoir = :avoir')
		->andWhere('j.compteComptable != :compteComptable')
		->setParameter('depense', $ligne->getDepense())
		->setParameter('avoir', $ligne->getAvoir())
		->setParameter('compteComptable', $ligne->getCompteComptable())
		->getQuery()
		->getResult();

		return $result;
	}
}