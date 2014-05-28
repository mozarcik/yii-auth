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

        $this->render(
            'index',
            array(
                'dataProvider' => $dataProvider,
            )
        );
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

        $this->render(
            'create',
            array(
                'model' => $model,
            )
        );
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

        $this->render(
            'update',
            array(
                'item' => $item,
                'model' => $model,
            )
        );
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
                foreach ($formModel->items as $citem) {
                    $childItem = $am->getAuthItem($citem);
                    if ($childItem === null) {
                        $op = $this->capitalize($this->getItemTypeText(CAuthItem::TYPE_OPERATION, true));
                        $desc = isset($childOptions[$op][$citem]) ? $childOptions[$op][$citem] : $citem;
                        $am->createAuthItem($citem, CAuthItem::TYPE_OPERATION, $desc);
                    }
                    if (!$am->hasItemChild($name, $citem)) {
                        $am->addItemChild($name, $citem);
                        if ($am instanceof CPhpAuthManager) {
                            $am->save();
                        }
                    }
                }
                $descendants = $am->getDescendants($name);
                //todo remove child items which are not POSTed
                $removeChildren = array_diff(array_keys($descendants), $formModel->items);
                foreach ($removeChildren as $childName) {
                    $am->removeItemChild($name, $childName);
                }
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

        $this->render(
            'view',
            array(
                'item' => $item,
                'ancestorDp' => $ancestorDp,
                'descendantDp' => $descendantDp,
                'formModel' => $formModel,
                'childOptions' => $childOptions,
                'tree'  => $this->getItemsTree($descendants),
            )
        );
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

    protected function getItemsTree($descendants)
    {
        $options = array();
        $modules = Yii::app()->getModules();

        foreach ($modules as $module => $config) {
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

                $rightControl = '<i class="fa fa-lg fa-check text-success toggle-auth"></i>';
                if (!isset($options[$module])) {
                    $options[$module] = array(
                        'label' => $module,
                        'htmlOptions' => array('id' => $module,  'style' => 'display:none;'),
                        'rightControl' => $rightControl,
                        'items' => array(),
                    );
                }

                $formModel = new AddAuthItemForm();
                $options[$module]['items'][] = array(
                    'label' => $model::label(2),
                    'htmlOptions' => array('id' => "$module-$model", 'style' => 'display:none;'),
                    'rightControl' => $rightControl,
                    'items' => array(
                        array(
                            'label'=> Yii::t('AuthModule.main', "read {model}", array('{model}' => $model::label(2))),
                            'rightControl' => $rightControl.CHtml::activeHiddenField($formModel, 'items[]', array('disabled' => !isset($descendants["$module.$model.read"]), 'value' => "$module.$model.read")),
                        ),
                        array(
                            'label'=> Yii::t('AuthModule.main', "create {model}", array('{model}' => $model::label(2))),
                            'rightControl' => $rightControl.CHtml::activeHiddenField($formModel, 'items[]', array('disabled' => !isset($descendants["$module.$model.create"]), 'value' => "$module.$model.create")),
                        ),
                        array(
                            'label'=> Yii::t('AuthModule.main', "update {model}", array('{model}' => $model::label(2))),
                            'rightControl' => $rightControl.CHtml::activeHiddenField($formModel, 'items[]', array('disabled' => !isset($descendants["$module.$model.update"]), 'value' => "$module.$model.update")),
                        ),
                        array(
                            'label'=> Yii::t('AuthModule.main', "delete {model}", array('{model}' => $model::label(2))),
                            'rightControl' => $rightControl.CHtml::activeHiddenField($formModel, 'items[]', array('disabled' => !isset($descendants["$module.$model.delete"]), 'value' => "$module.$model.delete")),
                        ),
                    ),
                );
            }
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

            $modules = Yii::app()->getModules();

            foreach ($modules as $module => $config) {
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
                        $obj = new $model;
                    } catch (Exception $e) {
                        continue;
                    }

                    if (!($obj instanceof CActiveRecord))
                        continue;

                    $op = $this->capitalize($this->getItemTypeText(CAuthItem::TYPE_OPERATION, true));
                    if (!isset($exclude["$module.$model.read"]))
                        $options[$op]["$module.$model.read"] = Yii::t('AuthModule.main', "read {model}", array('{model}' => $model::label(2)));

                    if (!isset($exclude["$module.$model.create"]))
                        $options[$op]["$module.$model.create"] = Yii::t('AuthModule.main', "create {model}", array('{model}' => $model::label(2)));

                    if (!isset($exclude["$module.$model.update"]))
                        $options[$op]["$module.$model.update"] = Yii::t('AuthModule.main', "update {model}", array('{model}' => $model::label(2)));

                    if (!isset($exclude["$module.$model.delete"]))
                        $options[$op]["$module.$model.delete"] = Yii::t('AuthModule.main', "delete {model}", array('{model}' => $model::label(2)));
                }
            }

            foreach ($authItems as $childName => $childItem) {
                if (in_array($childItem->type, $validChildTypes) && !isset($exclude[$childName])) {
                    $options[$this->capitalize(
                        $this->getItemTypeText($childItem->type, true)
                    )][$childName] = $childItem->description;
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