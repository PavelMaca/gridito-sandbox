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
	
	public function addAddButton($name, $label = null, array $options = array()){
		if(isset($options["handler"])){
			throw new \InvalidArgumentException(__CLASS__.":".__METHOD__." \$options['handler'] is reserved.");
		}
		
		$grid = $this;
		$options["handler"] = function () use ($grid) {
			$grid["addForm"]->render();
		};
		
		return $this->addToolbarWindowButton($name, $label, $options);	
	}

	public function addEditButton($name, $label = null, array $options = array()){
		if(isset($options["handler"])){
			throw new \InvalidArgumentException(__CLASS__.":".__METHOD__." \$options['handler'] is reserved.");
		}
		
		$grid = $this;
		$model = $this->editableModel;
		$filter = $this->defaultValueFilter;
		$options["handler"] = function ($id) use ($grid, $model, $filter) {
			$grid["editForm"]->setDefaults(call_user_func($filter, $model->findRow($id)));
			$grid["editForm"]->render();
		};
		
		return $this->addWindowButton($name, $label, $options);
	}
	
	public function addRemoveButton($name, $label = null, array $options = array()){
		if(isset($options["handler"])){
			throw new \InvalidArgumentException(__CLASS__.":".__METHOD__." \$options['handler'] is reserved.");
		}
		
		$grid = $this;
		$model = $this->editableModel;
		$removedMessage = $this->removedMessage;
		$options["handler"] = function ($id) use ($grid, $model, $removedMessage) {
			$model->removeRow($id);
			$grid->flashMessage($removedMessage);
		};
		
		return $this->addButton($name, $label, $options);
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
			
			$rawData = call_user_func($filter, $form->values, $form);
			if($insert === true ){
				$model->addRow($rawData);
			}else {
				$model->updateRow($vals[$grid->getPrimaryKey()], $rawData);
			}
			
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