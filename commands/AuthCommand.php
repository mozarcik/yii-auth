<?php

/**
 * Description here...
 * 
 * @author Michał Motyczko <michal@motyczko.pl>
 */
class AuthCommand extends CConsoleCommand
{
    public function actionExportToFile($file)
    {
        /** @var IAuthManager $auth */
        $auth = Yii::app()->authManager;

        $authItems = [];

        /** @var CAuthItem[] $operations */
        $operations = $auth->getAuthItems(CAuthItem::TYPE_OPERATION);
        foreach ($operations as $item) {
            $authItems[$item->name] = [
                'type' => CAuthItem::TYPE_OPERATION,
                'description' => $item->description,
                'bizRule' => $item->bizRule,
                'data' => $item->data,
                'children' => array_values(array_map(function ($i) { return $i->name;}, $auth->getItemChildren($item->name))),
            ];
        }

        /** @var CAuthItem[] $tasks */
        $tasks = $auth->getAuthItems(CAuthItem::TYPE_TASK);
        foreach ($tasks as $item) {
            $authItems[$item->name] = [
                'type' => CAuthItem::TYPE_TASK,
                'description' => $item->description,
                'bizRule' => $item->bizRule,
                'data' => $item->data,
                'children' => array_values(array_map(function ($i) { return $i->name;}, $auth->getItemChildren($item->name))),
            ];
        }

        /** @var CAuthItem[] $roles */
        $roles = $auth->getAuthItems(CAuthItem::TYPE_ROLE);
        foreach ($roles as $item) {
            $authItems[$item->name] = [
                'type' => CAuthItem::TYPE_ROLE,
                'description' => $item->description,
                'bizRule' => $item->bizRule,
                'data' => $item->data,
                'children' => array_values(array_map(function ($i) { return $i->name;}, $auth->getItemChildren($item->name))),
            ];
        }

        file_put_contents($file, "<?php\nreturn " . var_export($authItems, true) . ";\n");
    }
}
