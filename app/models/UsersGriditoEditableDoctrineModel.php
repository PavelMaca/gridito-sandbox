<?php

namespace Model;

use Doctrine\ORM\EntityManager;

/**
 * Users Gridito Doctrine model
 *
 * @author Jan Marek
 * @author Pavel Máca
 * @license MIT
 */
class UsersEditableGriditoDoctrineModel extends \Gridito\DoctrineEditableQueryBuilderModel
{
	public function __construct(EntityManager $em)
	{
		parent::__construct($em->getRepository("Model\User")->createQueryBuilder("u"));
	}

}