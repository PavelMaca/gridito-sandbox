<?php

namespace Model;

use Doctrine\ORM\EntityManager;

/**
 * Users Gridito Doctrine model
 *
 * @author Jan Marek
 * @license MIT
 */
class UsersGriditoDoctrineModel extends \Gridito\DoctrineEditableQueryBuilderModel
{
	public function __construct(EntityManager $em)
	{
		parent::__construct($em->getRepository("Model\User")->createQueryBuilder("u"));
	}

}