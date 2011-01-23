<?php

namespace Gridito;

use \Nette\Application\AppForm;

/**
 * @author Pavel Máca
 * @license MIT
 * Editable Grid with ADD, EDIT and REMOVE actions.
 */
class EditableGrid extends Grid {

	/** @var type IEditableModel */
	protected $model;
	/** @var string Class used as default when creting edit/add form */
	private $editControlClass = "\Nette\Forms\TextInput";
	private $formFilter = array(__CLASS__, '_bracket');
	private $defaultValueFilter = array(__CLASS__, '_bracket');
	/** @var string FlashMessage show after row created */
	private $insertedMessage = "Row was created successfully.";
	/** @var string FlashMessage show after row updated */
	private $editedMessage = "Row updated.";
	/** @var string FlashMessage show after row removed */
	private $removedMessage = "Row was removed successfully.";

	static function _bracket($v) {
		return $v;
	}

	/**
	 * @param \Nette\IComponentContainer $parent
	 * @param string $name 
	 */
	public function __construct(\Nette\IComponentContainer $parent = null, $name = null) {
		parent::__construct($parent, $name);
		Column::extensionMethod("setEditable", callback($this, "setEditable"));
		Column::extensionMethod("getEditable", callback($this, "getEditable"));
	}

	/*	 * *************** Messages ****** */

	/**
	 * @param string $message
	 * @return EditableGrid 
	 */
	public function setInsertedMessage($message) {
		$this->insertedMessage = (string) $message;
		return $this;
	}

	/**
	 * @param string $message
	 * @return EditableGrid 
	 */
	public function setEditedMessage($message) {
		$this->editedMessage = (string) $message;
		return $this;
	}

	/**
	 * @param string $message
	 * @return EditableGrid 
	 */
	public function setRemovedMessage($message) {
		$this->removedMessage = (string) $message;
		return $this;
	}

	/** @return string */
	public function getInsertedMessage() {
		return $this->insertedMessage;
	}

	/** @return string */
	public function getEditedMessage() {
		return $this->editedMessage;
	}

	/** @return string */
	public function getRemovedMessage() {
		return $this->removedMessage;
	}

	/** filtering */

	/**
	 * @param callback $filter
	 * @return EditableGrid 
	 */
	public function setDefaultValueFilter($filter) {
		$this->defaultValueFilter = $filter;
		return $this;
	}

	/**
	 * @param callback $filter
	 * @return EditableGrid 
	 */
	public function setFormFilter($filter) {
		$this->formFilter = $filter;
		return $this;
	}

	/** model */

	/**
	 * @param IModel $model
	 * @return EditableGrid
	 */
	public function setModel(IModel $model) {
		if (!$model instanceof IEditableModel) {
			throw new \InvalidArgumentException("Model must implements \Gridito\IEditableModel");
		}
		return parent::setModel($model);
	}

	/** buttons */

	/**
	 * @param string $name
	 * @param string $label
	 * @param array $options
	 * @return WindowButton
	 * @throws \InvalidArgumentException
	 */
	public function addAddButton($name, $label = null, array $options = array()) {
		if (isset($options["handler"])) {
			throw new \InvalidArgumentException(__CLASS__ . ":" . __METHOD__ . " \$options['handler'] is reserved.");
		}

		$grid = $this;
		$options["handler"] = function () use ($grid) {
			
				$grid["editForm"]->render();
			};

		return $this->addToolbarWindowButton($name, $label, $options);
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @param array $options
	 * @return WindowButton
	 * @throws \InvalidArgumentException
	 */
	public function addEditButton($name, $label = null, array $options = array()) {
		if (isset($options["handler"])) {
			throw new \InvalidArgumentException(__CLASS__ . ":" . __METHOD__ . " \$options['handler'] is reserved.");
		}

		$grid = $this;
		$model = $this->model;
		$filter = $this->defaultValueFilter;
		$options["handler"] = function ($id) use ($grid, $model, $filter) {
				$grid["editForm"]->setDefaults(call_user_func($filter, $model->findRow($id)));
				$grid["editForm"]->render();
			};

		$this->setEditedMessage($options["editedMessage"]);
		//remove editable-only options
		unset($options["editedMessage"]);

		return $this->addWindowButton($name, $label, $options);
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @param array $options
	 * @return Button
	 * @throws \InvalidArgumentException
	 */
	public function addRemoveButton($name, $label = null, array $options = array()) {
		if (isset($options["handler"])) {
			throw new \InvalidArgumentException(__CLASS__ . ":" . __METHOD__ . " \$options['handler'] is reserved.");
		}

		$grid = $this;
		$model = $this->model;
		$removedMessage = $this->removedMessage;
		$options["handler"] = function ($id) use ($grid, $model, $removedMessage) {
				$model->removeRow($id);
				$grid->flashMessage($removedMessage);
			};

		return $this->addButton($name, $label, $options);
	}

	/** handlers */
	private function createSubmitHandler($message = NULL) {
		$grid = $this;
		$model = $this->model;
		$filter = $grid->formFilter;
		return function ($form) use ($grid, $model, $message, $filter) {
			$vals = $form->values;

			$rawData = call_user_func($filter, $form->values, $form);
			if ($rawData[$grid->getModel()->getPrimaryKey()] === NULL ) {
				$model->addRow($rawData);
			} else {
				unset($rawData[$grid->getModel()->getPrimaryKey()]);
				$model->updateRow($vals[$grid->getModel()->getPrimaryKey()], $rawData);
			}

			if ($message !== NULL) {
				$grid->flashMessage($message);
			}
			$grid->redirect("this");
		};
	}

	/*
	  protected function createBaseForm($name){
	  $form = new AppForm($this, $name);
	  $form->addProtection();
	  $this->editableForm = $form;
	  } */
	/*
	  protected function createComponentAddForm($name){
	  $this->createBaseForm($name);

	  $this->editableForm->onSubmit[] = $this->createSubmitHandler(true, $this->insertedMessage);
	  } */

	protected function createComponentEditForm($name) {
		$form = new AppForm($this, $name);
		$form->addProtection();

		$form->addHidden($this->getModel()->getPrimaryKey());

		$form->onSubmit[] = $this->createSubmitHandler(($this->editedMessage ? : NULL));
	}

	/**
	 * @return AppForm 
	 */
	public function getEditableForm() {
		return $this["editForm"];
	}

	/** extending methods */

	/**
	 * @param Column $column
	 * @param bool|string $controlClass bool or class of component, default \Nette\Forms\TextInput
	 * @return \Nette\Forms\IFormControl 
	 */
	public function setEditable(Column $column, $controlClass = true) {
		if ($controlClass === true) {
			$controlClass = $this->editControlClass;
		}
		if (class_exists($controlClass) && is_subclass_of($controlClass, '\Nette\Forms\IFormControl')) {
			$control = new $controlClass($column->getName(), $column->getLabel());
			$this->getEditableForm()->addComponent($control, $column->getName());
			return $this->getEditableForm()->getComponent($column->getName());
		} elseif ($controlClass === false) {
			$this->getEditableForm()->removeComponent($column->getName());
		} else {
			throw new \InvalidArgumentException("No valid editable control");
		}
	}
	
	/**
	 * @param Column $column
	 * @return \Nette\Forms\IFormControl 
	 */
	public function getEditable(Column $column){
		return $this->getEditableForm()->getComponent($column->getName());
	}
	
}