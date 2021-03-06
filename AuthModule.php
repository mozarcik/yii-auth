<?php
/**
 * AuthModule class file.
 * @author Christoffer Niska <ChristofferNiska@gmail.com>
 * @copyright Copyright &copy; Christoffer Niska 2012-
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @package auth
 * @version 1.7.0
 */

/**
 * Web module for managing Yii's built-in authorization manager (CAuthManager).
 */
class AuthModule extends CWebModule
{
    /**
     * @var boolean whether to enable the RBAC strict mode.
     * When enabled items cannot be assigned children of the same type.
     */
    public $strictMode = true;
    /**
     * @var string name of the user model class.
     * Change this if your user model name is different than the default value.
     */
    public $userClass = 'User';
    /**
     * @var string name of the user id column.
     * Change this if the id column in your user table is different than the default value.
     */
    public $userIdColumn = 'id';
    /**
     * @var string name of the user name column.
     * Change this if the name column in your user table is different than the default value.
     */
    public $userNameColumn = 'name';
    /**
     * @var string the application layout.
     * Change this if you wish to use a different layout with the module.
     */
    public $defaultLayout = '/layouts/main';
    /**
     * @var array map of flash message keys to use for the module.
     */
    public $flashKeys = array();
    /**
     * @var string string the id of the default controller for this module.
     */
    public $defaultController = 'assignment';
    /**
     * @var boolean whether to force copying of assets.
     * Useful during development and when upgrading the module.
     */
    public $forceCopyAssets = true;
    /**
     * @var string path to view files for this module.
     * Specify this to use your own views instead of those shipped with the module.
     */
    public $viewDir;

    /**
     * List of modules used for autogenerating auth items. 
     * Format:
     *      array(
     *          'module' => 'Module Label',
     *          'module2' => array('exclude' => 'model1, model2'),
     *          'module3' => array('exclude' => array('model1', 'model2')),
     *          'module4' => array('label' => 'Module 4 Label', 'exclude' => 'model2'),
     *      )
     *
     * @var array
     */
    public $modules = array();

    private $_assetsUrl;

    /**
     * Initializes the module.
     */
    public function init()
    {
        $this->setImport(
            array(
                'auth.components.*',
                'auth.controllers.*',
                'auth.models.*',
                'auth.widgets.*',
            )
        );

        $this->registerCss();
        $this->registerScript();

        $this->flashKeys = array_merge(
            $this->flashKeys,
            array(
                'error' => 'error',
                'info' => 'info',
                'success' => 'success',
                'warning' => 'warning',
            )
        );

        if (isset($this->viewDir)) {
            if (strpos($this->viewDir, '.')) {
                $this->viewDir = Yii::getPathOfAlias($this->viewDir);
            }

            $this->setLayoutPath($this->viewDir . DIRECTORY_SEPARATOR . 'layouts');
            $this->setViewPath($this->viewDir);
        }

        foreach ($this->modules as $module => &$config) {
            if (is_string($config)) {
                $this->modules[$module] = array('label' => $config, 'exclude' => array());
                continue;
            }

            if (!isset($config['label'])) {
                $config['label'] = $module;
            }

            if (!isset($config['exclude'])) {
                $config['exclude'] = array();
            }

            if (!is_array($config['exclude'])) {
                $config['exclude'] = array_map('trim', explode(',', $config['exclude']));
            }
        }
    }

    public function getAuthItemName($model, $operation)
    {
        return '';
    }

    /**
     * Registers the module CSS.
     */
    public function registerCss()
    {
        Yii::app()->clientScript->registerCssFile($this->getAssetsUrl() . '/css/auth.css');
    }

    /**
     * Registers the module JavaScript.
     */
    public function registerScript()
    {
        Yii::app()->clientScript->registerScriptFile($this->getAssetsUrl() . '/js/auth.js');
    }

    /**
     * The pre-filter for controller actions.
     * @param CController $controller the controller.
     * @param CAction $action the action.
     * @return boolean whether the action should be executed.
     * @throws CException|CHttpException if user is denied access.
     */
    public function beforeControllerAction($controller, $action)
    {
        if (parent::beforeControllerAction($controller, $action)) {
            $user = Yii::app()->getUser();

            if ($user instanceof AdminWebUserInterface) {
                if ($user->isAdmin) {
                    return true;
                } elseif ($user->isGuest) {
                    $user->loginRequired();
                }
            } else {
                throw new CException('WebUser component is not an instance of AuthWebUser.');
            }
        }
        throw new CHttpException(401, Yii::t('AuthModule.main', 'Access denied.'));
    }

    /**
     * Returns the URL to the published assets folder.
     * @return string the URL.
     */
    protected function getAssetsUrl()
    {
        if (isset($this->_assetsUrl)) {
            return $this->_assetsUrl;
        } else {
            $assetsPath = Yii::getPathOfAlias('auth.assets');
            $assetsUrl = Yii::app()->assetManager->publish($assetsPath, false, -1, $this->forceCopyAssets);

            return $this->_assetsUrl = $assetsUrl;
        }
    }

    /**
     * Returns the module version number.
     * @return string the version.
     */
    public function getVersion()
    {
        return '1.7.0';
    }
}
