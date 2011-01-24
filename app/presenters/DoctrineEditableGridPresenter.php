<?php

use Nette\Forms\Form;

/**
 * Doctine editable grid example presenter
 *
 * @author Pavel Máca
 * @license MIT
 */
class DoctrineEditableGridPresenter extends BasePresenter {

	protected function createComponentGrid($name) {
		$grid = new Gridito\EditableGrid($this, $name);

		// model
		$em = Nette\Environment::getService("Doctrine\ORM\EntityManager");
		$model = new Model\UsersEditableGriditoDoctrineModel($em);
		$grid->setModel($model);

		// columns
		$grid->addColumn("id", "ID")
			->setSortable(true);

		$grid->addColumn("username", "Username")
			->setSortable(true)
			->setEditableText();
		
		$grid->getColumn("username")
			->setAddable();
		

		$grid->addColumn("name", "Name")
			->setSortable(true)
			->setEditableText();
		
		$grid->getColumn("name")
			->setAddableText();

		
		$grid->addColumn("surname", "Surname")
			->setSortable(true)
			->setEditableText();
		
		$grid->getColumn("surname")
			->setAddable();
		
			
		$grid->addColumn("mail", "E-mail", array(
			"sortable" => true,
			"renderer" => function ($row) {
				echo Nette\Web\Html::el("a")->href("mailto:$row->mail")->setText($row->mail);
			},
			"editable" => true,
			"addable" => true,
		));

			
		$grid->getColumn("mail")
			->getEditable()
				->addRule(Form::EMAIL, "E-mail is not valid.");
		
		$grid->getColumn("mail")
			->getAddable()
				->addRule(Form::EMAIL, "E-mail is not valid.");

		
		$grid->addColumn("active", "Active")
			->setSortable(true)
			->setEditableCheckbox();
			
		$grid->getColumn("active")
			->setAddableCheckbox();
		
		
		
		// toolbar buttons
		
		$grid->addAddButton("create", "Create new user", array(
			"icon" => "ui-icon-plusthick",
		));

	
		// action buttons
		$grid->addRemoveButton("delete", "Delete", array(
			"icon" => "ui-icon-closethick",
			"confirmationQuestion" => function ($user) {
				if ($user->active) {
					return "Really delete use $user->name $user->surname?";
				} else {
					return null;
				}
			},
			"visible" => function ($user) {
				return !$user->isActive();
			},
		));

		$grid->addEditButton("edit", "Edit", array(
			"icon" => "ui-icon-pencil",
			"editedMessage" => "Uloženo.",
		));
		
		
		//buttons for editabel forms
		
		$grid->getEditableForm()
			->addSubmit("save", "Uložit");
		
		
		$grid->getAddableForm()
			->addPassword("password", "Heslo")
				->getParent()
			->addSubmit("add", "Přidat");
			;
		
			
		// messages
		$grid->setInsertedMessage("Succes.");
		$grid->setRemovedMessage("Row was removed.");


		// handlers
		
		$grid->getModel()->setEntityUpdateHandler(function($entity, $values) use ($grid) {
			$class = $grid->getModel()->getEntityManager()->getClassMetadata(get_class($entity));
				foreach ($values as $property_name => $val) {
					//TODO: test for oneToMany
					$prop = $class->reflFields[$property_name];
					$prop->setValue($entity, $val);
				}
			});
			
		$grid->getModel()->setEntityInsertHandler(function($values) use ($grid) {
			$entity = new Model\User;
			$class = $grid->getModel()->getEntityManager()->getClassMetadata(get_class($entity));
				foreach ($values as $property_name => $val) {
					//TODO: test for oneToMany
					$prop = $class->reflFields[$property_name];
					$prop->setValue($entity, $val);
				}
			return $entity;
		});
	}

}
