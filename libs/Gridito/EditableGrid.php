<?php
namespace Gridito;

use \Nette\Application\AppForm;
//use static \Nette\Debug::dump;

/**
 * @author Vít Šesták
 * @copyright Vít Šesták
 * A Grid with simplified CUD (create, update, delete) actions.
 */
class EditableGrid extends Grid{
	
	private $okMsg;
	
	/** @var EditableModel */
	private $editableModel;
	
	private $formFactory;
	
	private $formFilter = array(__CLASS__, '_bracket');

	private $defaultValueFilter = array(__CLASS__, '_bracket');

	private $insertedMessage;
	
	private $updatedMessage;
	
	private $removedMessage;
	
	static function _bracket($v){
		return $v;
	}
	
	private function formFactory($f){
		//dump($this->formFactory);
		call_user_func($this->formFactory, $f);
	}
	
	public function setSavedMessage($msg){
		$this->insertedMessage = $msg;
		$this->updatedMessage = $msg;
		return $this;
	}
	
	public function setDefaultValueFilter($filter){
		$this->defaultValueFilter = $filter;
		return $this;
	}
	
	public function setRemovedMessage($msg){
		$this->removedMessage = $msg;
		return $this;
	}
	
	public function setFormFilter($filter){
		$this->formFilter = $filter;
		return $this;
	}
	
	public function setEditableModel(IEditableModel $model, $formFactory){ // TODO: add default Form factory
		$this->editableModel = $model;
		$this->formFactory = $formFactory;
		return parent::setModel($model);
	}
	
	public function setModel(IModel $model){
		$this->editableModel = null;
		return parent::setModel($model);
	}
	
	public function addAddButton($label, $jQueryClass){
		$grid = $this;
		return $this->addToolbarWindowButton($label, function () use ($grid) {
			$grid["addForm"]->render();
		}, $jQueryClass);
	}

	public function addEditButton($label, $jQueryClass){
		$grid = $this;
		$model = $this->editableModel;
		$filter = $this->defaultValueFilter;
		return $this->addWindowButton($label, function ($id) use ($grid, $model, $filter) {
			$grid["editForm"]->setDefaults(call_user_func($filter, $model[$id]));
			$grid["editForm"]->render();
		}, $jQueryClass);
	}
	
	public function addRemoveButton($label, $jQueryClass){
		$grid = $this;
		$model = $this->editableModel;
		$removedMessage = $this->removedMessage;
		return $this->addButton($label, function ($id) use ($grid, $model, $removedMessage) {
			unset($model[$id]);
			$grid->flashMessage($removedMessage);
		}, $jQueryClass);
	}

	private function createBaseForm($name){
		$f = new AppForm($this, $name);
		$f->addProtection();
		return $f;
	}
	
	private function createSubmitHandler($insert, $okMsg){
		$grid = $this;
		$model = $this->editableModel;
		$filter = $grid->formFilter;
		return function ($form) use ($grid, $model, $okMsg, $insert, $filter) {
			$vals = $form->values;
			$id = $insert ?null :$vals[$grid->getPrimaryKey()];
			$model[$id] = call_user_func($filter, $form->values, $form);
			$grid->flashMessage($okMsg);
			$grid->redirect("this");
		};
	}
	
	protected function createComponentAddForm($name){
		$f = $this->createBaseForm($name);
		$this->formFactory($f);
		$f->onSubmit[] = $this->createSubmitHandler(true, $this->insertedMessage);
	}

	protected function createComponentEditForm($name){
		$f = $this->createBaseForm($name);
		$f->addHidden($this->getPrimaryKey());
		$this->formFactory($f);
		$f->onSubmit[] = $this->createSubmitHandler(false, $this->updatedMessage);
	}
	
}