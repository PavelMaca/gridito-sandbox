<?php

namespace Gridito;

/**
 * Doctrine Editable QueryBuilder model
 *
 * @author Pavel Máca
 * @license MIT
 */
class DoctrineEditableQueryBuilderModel extends DoctrineQueryBuilderModel implements IEditableModel
{
	/** @var callback */
	private $entityFactory;
	
	/** @var callback */
	private $entityUpdateHandler;

	/**
	 * @param callback $entityFactory 
	 */
	public function setEntityFactory($entityFactory) {
		if (!is_callable($entityFactory)) {
			throw new \InvalidArgumentException("EntityFactory is not callable.");
		}
		$this->entityFactory = $entityFactory;
	}

	/**
	 * @return callback
	 * @throws \InvalidStateException when self::$entityFactory not set
	 */
	public function getEntityFactory() {
		if (!isset($this->entityFactory)) {
			throw new \InvalidStateException( __CLASS__ . "::\$entityFactory is not set.");
		}
		return $this->entityFactory;
	}

	/**
	 * @param callback $entityFactory 
	 */
	public function setEntityUpdateHandler($entityUpdateHandler) {
		if (!is_callable($entityUpdateHandler)) {
			throw new \InvalidArgumentException("EntityUpdateHandler is not callable.");
		}
		$this->entityUpdateHandler = $entityUpdateHandler;
	}

	/**
	 * @return callback
	 * @throws \InvalidStateException when self::$entityUpdateHandler not set
	 */
	public function getEntityUpdateHandler() {
		if (!isset($this->entityUpdateHandler)) {
			throw new \InvalidStateException(__CLASS__ . "::\$entityUpdateHandler is not set.");
		}
		return $this->entityUpdateHandler;
	}

	/**
	 * @param string|int $id
	 * @return mixed
	 * @throws \InvalidStateException when entity not found or duplicity exists
	 */
	private function entityFind($id) {
		try {
			return $this->qb->where($this->qb->getRootAlias() . "." . $this->getPrimaryKey() . " = :id")
				->setParameter("id", $id)
				->getQuery()
				->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			throw new \InvalidStateException("Entity with id: '$id' not found");
		} catch (\Doctrine\ORM\NonUniqueResultException $e) {
			throw new \InvalidStateException("Entity with id: '$id' isn't unique!");
		}
	}
	
	
	/** interface IEditableModel */
	
	/**
	 * @param string|int $id
	 * @return array 
	 * @todo do it better
	 */
	public function findRow($id) {
		$data = array();
		foreach ((array) $this->entityFind($id) as $key => $value) {
			if (preg_match("~\\x00(?P<key>[^\\x00]+)$~", $key, $found)) {
				$data[$found["key"]] = $value;
			}
			else
				throw new \UnexpectedValueException("Unexpected structure of \$key : '$key'");
		}
		return $data;
	}

	/**
	 * @param string|int $id 
	 */
	public function removeRow($id) {
		$entity = $this->entityFind($id);
		
		$this->qb->getEntityManager()->remove($entity);
		$this->qb->getEntityManager()->flush();
	}

	/**
	 * @param array $rawValues
	 */
	public function addRow($rawValues) {
		$factory = $this->getEntityFactory();
		$entity = $factory($rawValues);
		
		$this->qb->getEntityManager()->persist($entity);
		$this->qb->getEntityManager()->flush();
	}

	/**
	 * @param string|int $id
	 * @param array $rawValues 
	 */
	public function updateRow($id, $rawValues) {
		$entity = $this->entityFind($id);
		if(!$entity){
			throw new \InvalidStateException("Can not find row with id:'$id'");
		}
		
		$this->qb->getEntityManager()->persist($entity);

		$handler = $this->getEntityUpdateHandler();
		$handler($entity, $rawValues);

		$this->qb->getEntityManager()->flush();
	}
}