<?php
/**
 * AuthItemController class file.
 * @author Christoffer Niska <ChristofferNiska@gmail.com>
 * @copyright Copyright &copy; Christoffer Niska 2012-
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @package auth.controllers
 */

/**
 * Base controller for authorization item related actions.
 */
abstract class AuthItemController extends AuthController
{
    /**
     * @var integer the item type (0=operation, 1=task, 2=role).
     */
    public $type;

    /**
     * Displays a list of items of the given type.
     */
    public function actionIndex()
    {
        $dataProvider = new AuthItemDataProvider();
        $dataProvider->type = $this->type;

        $this->render('index', array('dataProvider' => $dataProvider));
    }

    /**
     * Displays a form for creating a new item of the given type.
     */
    public function actionCreate()
    {
        $model = new AuthItemForm('create');

        if (isset($_POST['AuthItemForm'])) {
            $model->attributes = $_POST['AuthItemForm'];
            if ($model->validate()) {
                /* @var $am CAuthManager|AuthBehavior */
                $am = Yii::app()->getAuthManager();

                if (($item = $am->getAuthItem($model->name)) === null) {
                    $item = $am->createAuthItem($model->name, $model->type, $model->description);
                    if ($am instanceof CPhpAuthManager) {
                        $am->save();
                    }
                }

                $this->redirect(array('view', 'name' => $item->name));
            }
        }

        $model->type = $this->type;

        $this->render('create', array('model' => $model));
    }

    /**
     * Displays a form for updating the item with the given name.
     * @param string $name name of the item.
     * @throws CHttpException if the authorization item is not found.
     */
    public function actionUpdate($name)
    {
        /* @var $am CAuthManager|AuthBehavior */
        $am = Yii::app()->getAuthManager();

        $item = $am->getAuthItem($name);

        if ($item === null) {
            throw new CHttpException(404, Yii::t('AuthModule.main', 'Page not found.'));
        }

        $model = new AuthItemForm('update');

        if (isset($_POST['AuthItemForm'])) {
            $model->attributes = $_POST['AuthItemForm'];
            if ($model->validate()) {
                $item->description = $model->description;

                $am->saveAuthItem($item);
                if ($am instanceof CPhpAuthManager) {
                    $am->save();
                }

                $this->redirect(array('index'));
            }
        }

        $model->name = $name;
        $model->description = $item->description;
        $model->type = $item->type;

        $this->render('update', array('item' => $item, 'model' => $model));
    }

    private function addItemChild($name, $items, $child = null)
    {
        /* @var $am CAuthManager|AuthBehavior */
        $am = Yii::app()->getAuthManager();
        $autogenItems = $this->getAutogenItems();

        foreach ($items as $citem => $parents) {
            $authName = ($child !== null ? $child.'.' : '') . $citem;
            $authItem = $am->getAuthItem($authName);
            
            $authLabel =  is_string($parents) ? $parents : $authName;
            $authRule = null;
            $authData = null;
            if (isset($autogenItems[$authName])) {
                $authLabel = $autogenItems[$authName]['label'];
                $authRule = $autogenItems[$authName]['bizRule'];
                $authData = $autogenItems[$authName]['data'];
            }
            
            if ($authItem === null) {
                $am->createAuthItem($authName, CAuthItem::TYPE_OPERATION, $authLabel, $authRule, $authData);
                if ($child !== null) {
                    $am->addItemChild($authName, $child);
                }
            }

            if (!is_array($parents) && !$am->hasItemChild($name, $authName)) {
                $am->addItemChild($name, $authName);
                if ($am instanceof CPhpAuthManager) {
                    $am->save();
                }
            }

            if (is_array($parents)) {
                $this->addItemChild($name, $parents, $authName);
            }
        }
    }

    /**
     * Displays the item with the given name.
     * @param string $name name of the item.
     */
    public function actionView($name)
    {
        $formModel = new AddAuthItemForm();

        /* @var $am CAuthManager|AuthBehavior */
        $am = Yii::app()->getAuthManager();

        $item = $am->getAuthItem($name);
        $childOptions = $this->getItemChildOptions($item->name);
        
        if (isset($_POST['AddAuthItemForm'])) {
            $formModel->attributes = $_POST['AddAuthItemForm'];
            if ($formModel->validate()) {
                $descendants = $am->getDescendants($name);
                foreach ($descendants as $childName => $_) {
                    $am->removeItemChild($name, $childName);
                }
                $this->addItemChild($name, $formModel->items);
                $this->redirect(array('view', 'name' => $name));
            }
        }

        $dpConfig = array(
            'pagination' => false,
            'sort' => array('defaultOrder' => 'depth asc'),
        );

        $ancestors = $am->getAncestors($name);
        $ancestorDp = new PermissionDataProvider(array_values($ancestors), $dpConfig);

        $descendants = $am->getDescendants($name);
        $descendantDp = new PermissionDataProvider(array_values($descendants), $dpConfig);

        if (!empty($childOptions)) {
            $childOptions = array_merge(array('' => Yii::t('AuthModule.main', 'Select item') . ' ...'), $childOptions);
        }

        $this->render('view', array(
            'item' => $item,
            'ancestorDp' => $ancestorDp,
            'descendantDp' => $descendantDp,
            'formModel' => $formModel,
            'childOptions' => $childOptions,
            'descendantsTree'  => $this->getDescendantsTree($descendants),
            'ancestorsTree'  => $this->getAncestorsTree($ancestors),
        ));
    }

    /**
     * Deletes the item with the given name.
     * @throws CHttpException if the item does not exist or if the request is invalid.
     */
    public function actionDelete()
    {
        if (isset($_GET['name'])) {
            $name = $_GET['name'];

            /* @var $am CAuthManager|AuthBehavior */
            $am = Yii::app()->getAuthManager();

            $item = $am->getAuthItem($name);
            if ($item instanceof CAuthItem) {
                $am->removeAuthItem($name);
                if ($am instanceof CPhpAuthManager) {
                    $am->save();
                }

                if (!isset($_POST['ajax'])) {
                    $this->redirect(array('index'));
                }
            } else {
                throw new CHttpException(404, Yii::t('AuthModule.main', 'Item does not exist.'));
            }
        } else {
            throw new CHttpException(400, Yii::t('AuthModule.main', 'Invalid request.'));
        }
    }

    /**
     * Removes the parent from the item with the given name.
     * @param string $itemName name of the item.
     * @param string $parentName name of the parent.
     */
    public function actionRemoveParent($itemName, $parentName)
    {
        /* @var $am CAuthManager|AuthBehavior */
        $am = Yii::app()->getAuthManager();

        if ($am->hasItemChild($parentName, $itemName)) {
            $am->removeItemChild($parentName, $itemName);
            if ($am instanceof CPhpAuthManager) {
                $am->save();
            }
        }

        $this->redirect(array('view', 'name' => $itemName));
    }

    /**
     * Removes the child from the item with the given name.
     * @param string $itemName name of the item.
     * @param string $childName name of the child.
     */
    public function actionRemoveChild($itemName, $childName)
    {
        /* @var $am CAuthManager|AuthBehavior */
        $am = Yii::app()->getAuthManager();

        if ($am->hasItemChild($itemName, $childName)) {
            $am->removeItemChild($itemName, $childName);
            if ($am instanceof CPhpAuthManager) {
                $am->save();
            }
        }

        $this->redirect(array('view', 'name' => $itemName));
    }

    protected function getAncestorsTree($ancestors)
    {
        $options = array();
        /* @var $am CAuthManager|AuthBehavior */
        $am = Yii::app()->getAuthManager();
        
        foreach ($ancestors as $authItem) {
            $authItem = $authItem['item'];
            
            $typeText = $this->getItemTypeText($authItem->type, true);
            if (!isset($options[$typeText])) {
                $options[$typeText] = array(
                    'label' => $this->capitalize($typeText),
                    'htmlOptions' => array('id' => $typeText,  'style' => 'display:none;'),
                    'rightControl' => '',
                    'items' => array(),
                );
            }

            $label = trim($authItem->description) !== '' ? trim($authItem->description) : $authItem->name;
            $label = CHtml::link($label, array('/auth/' . $this->getItemControllerId($authItem->type) . '/view', 'name' => $authItem->name));

            $options[$typeText]['items'][] = array(
                'label' => $label,
                'htmlOptions' => array('id' => $authItem->name, 'style' => 'display:none;'),
            );
        }

        return $options;
    }

   

    protected function getDescendantsTree($descendants)
    {
        $options = array();
        /* @var $am CAuthManager|AuthBehavior */
        $am = Yii::app()->getAuthManager();
        $authItems = $am->getAuthItems();
        $validChildTypes = $this->getValidChildTypes();
        $rightControl = '<i class="fa fa-lg fa-times text-danger toggle-auth"></i>';
        $formModel = new AddAuthItemForm();
        
        $operationsOptions = array();
        foreach ($this->module->modules as $module => $config) {
            $moduleInstance = Yii::app()->getModule($module);
            Yii::import("$module.models.*");
            $filenames = CFileHelper::findFiles(Yii::getPathOfAlias("$module.models"), array (
                'fileTypes'=> array('php'),
                'level' => 0,
            ));
            foreach ($filenames as $filename) {
                //remove off the path
                $file = substr( $filename, strrpos($filename, '/') + 1 );
                // remove the extension, strlen('.php') = 4
                $model = substr( $file, 0, strlen($file) - 4);

                $class = new ReflectionClass($model);
                if ($class->isAbstract())
                    continue;

                try {
                    $obj = CActiveRecord::model($model);
                } catch (Exception $e) {
                    continue;
                }

                if (!($obj instanceof NetActiveRecord))
                    continue;

                if (in_array($model, $config['exclude'])) {
                    continue;
                }

                $operations = array('create', 'read', 'update', 'delete');
                $modelOperations = array();
                foreach ($operations as $operationName) {
                    $authName = "$model.$operationName";
                    if (!in_array(CAuthItem::TYPE_OPERATION, $validChildTypes) && !isset($descendants[$authName]))
                        continue;

                    $modelLabel = $class->hasMethod('label') ? $model::label(2) : $model;
                    $authLabels = $this->getAuthLabels($operationName, $modelLabel);
                    $label = $authLabels['main'];

                    if (isset($authItems[$authName])) {
                        $label = CHtml::link($authLabels['main'], array('/auth/' . $this->getItemControllerId($authItems[$authName]->type) . '/view', 'name' => $authName));
                        unset($authItems[$authName]);
                    }

                    $item = null;
                    if ($operationName !== 'create') {
                        $relLabel = $authLabels['related'];
                        if (isset($authItems["$authName.related"])) {
                            $relLabel = CHtml::link($relLabel, array('/auth/' . $this->getItemControllerId($authItems["$authName.related"]->type) . '/view', 'name' => "$authName.related"));
                            unset($authItems["$authName.related"]);
                        }

                        $ownLabel = $authLabels['own'];
                        if (isset($authItems["$authName.related.own"])) {
                            $ownLabel = CHtml::link($ownLabel, array('/auth/' . $this->getItemControllerId($authItems["$authName.related.own"]->type) . '/view', 'name' => "$authName.related.own"));
                            unset($authItems["$authName.related.own"]);
                        }
                        $item = array(
                            'label' => $relLabel,
                            'rightControl' => $rightControl . CHtml::activeHiddenField($formModel, "items[$model.$operationName][related]", array(
                                'disabled' => !isset($descendants["$authName.related"]),
                                'value' => $authLabels['related'],
                            )),
                            'htmlOptions' => array('id' => "$module-$model-$operationName-related", 'style' => 'display:none;'),
                            'items' => array(
                                array(
                                    'label' => $ownLabel,
                                    'rightControl' => $rightControl . CHtml::activeHiddenField($formModel, "items[$model.$operationName][related][own]", array(
                                        'disabled' => !isset($descendants["$authName.related.own"]),
                                        'value' => $authLabels['own'],
                                    )),
                                    'htmlOptions' => array('id' => "$module-$model-$operationName-related-own", 'style' => 'display:none;'),
                                    'items' => array(),
                                ),
                            ),
                        );
                    }

                    $hiddenField = CHtml::activeHiddenField($formModel, "items[$model.$operationName]", array('disabled' => !isset($descendants[$authName]), 'value' => $authLabels['main']));
                    $modelOperations[] = array(
                        'label'=> $label,
                        'rightControl' => $rightControl.$hiddenField,
                        'htmlOptions' => array('id' => "$module-$model-$operationName", 'style' => 'display:none;'),
                        'items' => $item === null ? array() : array($item),
                    );
                }

                if (empty($modelOperations))
                    continue;

                if (!isset($operationsOptions[$module])) {
                    $operationsOptions[$module] = array(
                        'label' => $config['label'],
                        'htmlOptions' => array('id' => $module,  'style' => 'display:none;'),
                        'rightControl' => $rightControl,
                        'items' => array(),
                    );
                }


                $label = $model::label(2);

                if (isset($authItems[$model])) {
                    $label = CHtml::link($label, array('/auth/' . $this->getItemControllerId($authItems[$model]->type) . '/view', 'name' => $model));
                    unset($authItems[$model]);
                }
                $operationsOptions[$module]['items'][] = array(
                    'label' => $label,
                    'htmlOptions' => array('id' => "$module-$model", 'style' => 'display:none;'),
                    'rightControl' => $rightControl,
                    'items' => $modelOperations,
                );
            }
        }

        if (!empty($operationsOptions)) {
            $typeText = $this->getItemTypeText(CAuthItem::TYPE_OPERATION, true);

            if (!isset($options[$typeText])) {
                $options[$typeText] = array(
                    'label' => $this->capitalize($typeText),
                    'htmlOptions' => array('id' => $typeText,  'style' => 'display:none;'),
                    'rightControl' => '',
                    'items' => array(),
                );
            }

            $options[$typeText]['items'] = array_merge($options[$typeText]['items'], $operationsOptions);
        }

        foreach ($authItems as $childName => $childItem) {
            if (!isset($descendants[$childName]) && !in_array($childItem->type, $validChildTypes))
                continue;

            $typeText = $this->getItemTypeText($childItem->type, true);
            if (!isset($options[$typeText])) {
                $options[$typeText] = array(
                    'label' => $this->capitalize($typeText),
                    'htmlOptions' => array('id' => $typeText,  'style' => 'display:none;'),
                    'rightControl' => '',
                    'items' => array(),
                );
            }

            $opLabel = trim($childItem->description) !== '' ? trim($childItem->description) : $childName;
            $label = CHtml::link($opLabel, array('/auth/' . $this->getItemControllerId($childItem->type) . '/view', 'name' => $childName));
			$rc = '<i class="fa fa-lg toggle-auth fa-times text-danger"></i>';

            $options[$typeText]['items'][] = array(
                'label' => $label,
                'htmlOptions' => array('id' => "$childName", 'style' => 'display:none;'),
                'rightControl' => $rc.CHtml::activeHiddenField($formModel, "items[$childName]", array('disabled' => !isset($descendants[$childName]), 'value' => $opLabel,)),
            );
        }
        
        return $options;
    }

    /**
     * Returns a list of possible children for the item with the given name.
     * @param string $itemName name of the item.
     * @return array the child options.
     */
    protected function getItemChildOptions($itemName)
    {
        $options = array();

        /* @var $am CAuthManager|AuthBehavior */
        $am = Yii::app()->getAuthManager();

        $item = $am->getAuthItem($itemName);
        if ($item instanceof CAuthItem) {
            $exclude = $am->getAncestors($itemName);
            $exclude[$itemName] = $item;
            $exclude = array_merge($exclude, $item->getChildren());
            $authItems = $am->getAuthItems();
            $validChildTypes = $this->getValidChildTypes();

            foreach ($authItems as $childName => $childItem) {
                if (in_array($childItem->type, $validChildTypes) && !isset($exclude[$childName])) {
                    $options[$this->capitalize(
                        $this->getItemTypeText($childItem->type, true)
                    )][$childName] = trim($childItem->description) !== '' ? $childItem->description : $childName;
                }
            }
        }

        return $options;
    }

    /**
     * Returns a list of the valid child types for the given type.
     * @return array the valid types.
     */
    protected function getValidChildTypes()
    {
        $validTypes = array();

        switch ($this->type) {
            case CAuthItem::TYPE_OPERATION:
                break;

            case CAuthItem::TYPE_TASK:
                $validTypes[] = CAuthItem::TYPE_OPERATION;
                break;

            case CAuthItem::TYPE_ROLE:
                $validTypes[] = CAuthItem::TYPE_OPERATION;
                $validTypes[] = CAuthItem::TYPE_TASK;
                break;
        }

        if (!$this->module->strictMode) {
            $validTypes[] = $this->type;
        }

        return $validTypes;
    }

    /**
     * Returns the authorization item type as a string.
     * @param boolean $plural whether to return the name in plural.
     * @return string the text.
     */
    public function getTypeText($plural = false)
    {
        return parent::getItemTypeText($this->type, $plural);
    }

    /**
     * Returns the directory containing view files for this controller.
     * @return string the directory containing the view files for this controller.
     */
    public function getViewPath()
    {
        return $this->module->getViewPath() . DIRECTORY_SEPARATOR . 'authItem';
    }
}
