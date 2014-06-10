<?php
/**
 * AssignmentController class file.
 * @author Christoffer Niska <ChristofferNiska@gmail.com>
 * @copyright Copyright &copy; Christoffer Niska 2012-
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @package auth.controllers
 */

/**
 * Controller for assignment related actions.
 */
class AssignmentController extends AuthController
{
    /**
     * Displays the a list of all the assignments.
     */
    public function actionIndex()
    {
        $dataProvider = new CActiveDataProvider($this->module->userClass);

        $this->render(
            'index',
            array(
                'dataProvider' => $dataProvider
            )
        );
    }

    /**
     * Displays the assignments for the user with the given id.
     * @param string $id the user id.
     */
    public function actionView($id)
    {
        $formModel = new AddAuthItemForm();

        /* @var $am CAuthManager|AuthBehavior */
        $am = Yii::app()->getAuthManager();

        if (isset($_POST['AddAuthItemForm'])) {
            $formModel->attributes = $_POST['AddAuthItemForm'];
            if ($formModel->validate()) {
                foreach ($formModel->items as $citem => $label) {
                    $childItem = $am->getAuthItem($citem);
                    if ($childItem === null) {
                        $am->createAuthItem($citem, CAuthItem::TYPE_OPERATION, $label);
                    }
                   
                    if (!$am->isAssigned($citem, $id)) {
                        $am->assign($citem, $id);
                        if ($am instanceof CPhpAuthManager) {
                            $am->save();
                        }

                        if ($am instanceof ICachedAuthManager) {
                            $am->flushAccess($citem, $id);
                        }
                    }
                }
                $assignments = $am->getAuthAssignments($id);
                //todo remove child items which are not POSTed
                $removeChildren = array_diff(array_keys($assignments), array_keys($formModel->items));
                foreach ($removeChildren as $childName) {
                    $am->revoke($childName, $id);
                    if ($am instanceof CPhpAuthManager) {
                        $am->save();
                    }

                    if ($am instanceof ICachedAuthManager) {
                        $am->flushAccess($childName, $id);
                    }
                }
                $this->redirect(array('view', 'id' => $id));
            }
        }

        $model = CActiveRecord::model($this->module->userClass)->findByPk($id);

        $assignments = $am->getAuthAssignments($id);
        $authItems = $am->getItemsPermissions(array_keys($assignments));
        $authItemDp = new AuthItemDataProvider();
        $authItemDp->setAuthItems($authItems);

        $assignmentOptions = $this->getAssignmentOptions($id);
        if (!empty($assignmentOptions)) {
            $assignmentOptions = array_merge(
                array('' => Yii::t('AuthModule.main', 'Select item') . ' ...'),
                $assignmentOptions
            );
        }

        $this->render(
            'view',
            array(
                'model' => $model,
                'authItemDp' => $authItemDp,
                'formModel' => $formModel,
                'assignmentOptions' => $assignmentOptions,
                'assignmentTree' => $this->getAssignmentTree($id),
            )
        );
    }

    /**
     * Revokes an assignment from the given user.
     * @throws CHttpException if the request is invalid.
     */
    public function actionRevoke()
    {
        if (isset($_GET['itemName'], $_GET['userId'])) {
            $itemName = $_GET['itemName'];
            $userId = $_GET['userId'];

            /* @var $am CAuthManager|AuthBehavior */
            $am = Yii::app()->getAuthManager();

            if ($am->isAssigned($itemName, $userId)) {
                $am->revoke($itemName, $userId);
                if ($am instanceof CPhpAuthManager) {
                    $am->save();
                }

                if ($am instanceof ICachedAuthManager) {
                    $am->flushAccess($itemName, $userId);
                }
            }

            if (!isset($_POST['ajax'])) {
                $this->redirect(array('view', 'id' => $userId));
            }
        } else {
            throw new CHttpException(400, Yii::t('AuthModule.main', 'Invalid request.'));
        }
    }

    /**
     * Returns a list of possible assignments for the user with the given id.
     * @param string $userId the user id.
     * @return array the assignment options.
     */
    protected function getAssignmentOptions($userId)
    {
        $options = array();

        /* @var $am CAuthManager|AuthBehavior */
        $am = Yii::app()->authManager;

        $assignments = $am->getAuthAssignments($userId);
        $assignedItems = array_keys($assignments);

        /* @var $authItems CAuthItem[] */
        $authItems = $am->getAuthItems();
        foreach ($authItems as $itemName => $item) {
            if (!in_array($itemName, $assignedItems)) {
                $options[$this->capitalize($this->getItemTypeText($item->type, true))][$itemName] = trim($item->description) !== '' ? $item->description : $itemName;
            }
        }

        return $options;
    }

    protected function getAssignmentTree($userId)
    {
        $options = array();
        /* @var $am CAuthManager|AuthBehavior */
        $am = Yii::app()->getAuthManager();

        $assignments = $am->getAuthAssignments($userId);
        
        $authItems = $am->getAuthItems();
        $rightControl = '<i class="fa fa-lg fa-check text-success toggle-auth"></i>';
        $formModel = new AddAuthItemForm();
        $excludeItems = $this->module->excludedFromAutogenerate;

        $modules = array_merge(array('application' => array()), Yii::app()->getModules());
        $operationsOptions = array();
        foreach ($modules as $module => $config) {
            if (isset($excludeItems[$module]) && $excludeItems[$module] == '*') {
                continue;
            }
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

                if (isset($excludeItems[$module]) && in_array($model, $excludeItems[$module])) {
                    continue;
                }

                $operations = array(
                    'read' => 'read {model}',
                    'create' => 'create {model}',
                    'update' => 'update {model}',
                    'delete' => 'delete {model}',
                );
                $modelOperations = array();
                foreach ($operations as $operationName => $operationLabel) {
                    $authName = strtr($this->module->authItemNameTemplate, array(
                        '{module}' => $module,
                        '{model}' => $model,
                        '{operationName}' => $operationName,
                    ));
                    
                    $label = Yii::t('AuthModule.main', $operationLabel, array('{model}' => $class->hasMethod('label') ? $model::label(2) : $model));

                    if (isset($authItems[$authName])) {
                        $label = CHtml::link($label, array('/auth/' . $this->getItemControllerId($authItems[$authName]->type) . '/view', 'name' => $authName));
                        unset($authItems[$authName]);
                    }

                    $hiddenField = CHtml::activeHiddenField($formModel, "items[$authName]", array('disabled' => !isset($assignments[$authName]), 'value' => $label));
                    $modelOperations[] = array(
                        'label'=> $label,
                        'rightControl' => $rightControl.$hiddenField,
                    );
                }

                if (empty($modelOperations))
                    continue;

                if (!isset($operationsOptions[$module])) {
                    $operationsOptions[$module] = array(
                        'label' => $module,
                        'htmlOptions' => array('id' => $module,  'style' => 'display:none;'),
                        'rightControl' => $rightControl,
                        'items' => array(),
                    );
                }

                $operationsOptions[$module]['items'][] = array(
                    'label' => $model::label(2),
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
			$rc = '<i class="fa fa-lg toggle-auth ' . (!isset($assignments[$childName]) ? 'fa-times text-danger' : 'fa-check text-success') . '"></i>';

            $options[$typeText]['items'][] = array(
                'label' => $label,
                'htmlOptions' => array('id' => "$childName", 'style' => 'display:none;'),
                'rightControl' => $rc.CHtml::activeHiddenField($formModel, "items[$childName]", array('disabled' => !isset($assignments[$childName]), 'value' => $opLabel)),
            );
        }

        return $options;
    }
}