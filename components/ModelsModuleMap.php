<?php

class ModelsModuleMap extends CApplicationComponent
{
    private static $_map = null;

    public static function getMap($exclude = array())
    {
        if (self::$_map !== null)
            return self::$_map;

        $modules = array_merge(array('application' => array()), Yii::app()->getModules());
        $map = array();
        foreach ($modules as $module => $config) {
            if (isset($exclude[$module]) && $exclude[$module] == '*') {
                continue;
            }
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

                if (isset($exclude[$module]) && in_array($model, $exclude[$module])) {
                    continue;
                }
                
                $map[$model] = $module;
            }
        }

        self::$_map = $map;

        return self::$_map;
    }

    public static function getModule($model, $exclude = array())
    {
        $map = self::getMap($exclude);

        if (!isset($map[$model])) {
            Yii::log("Cannot find module for class $model!", CLogger::LEVEL_WARNING);
            return $model;
        }

        return $map[$model];
    }
}
