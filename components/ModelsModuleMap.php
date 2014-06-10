<?php

class ModelsModuleMap extends CApplicationComponent
{
    private static $_map = null;

    public static function getMap()
    {
        if (self::$_map !== null)
            return self::$_map;

        $modules = array_merge(array('application' => array()), Yii::app()->getModules());
        $map = array();
        foreach ($modules as $module => $config) {
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

                $map[$model] = $module;
            }
        }

        self::$_map = $map;

        return self::$_map;
    }

    public static function getModule($model)
    {
        $map = self::getMap();

        if (!isset($map[$model]))
            throw new CException("Model $model is unknown!");

        return $map[$model];
    }
}
