<?php
/* @var $this AssignmentController */
/* @var $dataProvider CActiveDataProvider */
$controller = $this;
$this->breadcrumbs = array(
    Yii::t('AuthModule.main', 'Assignments'),
);
?>
<?php
$this->widget(
    'zii.widgets.CMenu',
    array(
        'htmlOptions' => array('class' => 'nav nav-tabs'),
        'items' => $this->menu,
    )
);?>
<h1><?php echo Yii::t('AuthModule.main', 'Assignments'); ?></h1>

<?php $this->widget(
    'zii.widgets.grid.CGridView',
    array(
        'itemsCssClass' => 'table table-striped table-hover',
        'dataProvider' => $dataProvider,
        'emptyText' => Yii::t('AuthModule.main', 'No assignments found.'),
        'template' => "{items}\n{pager}",
        'columns' => array(
            array(
                'header' => Yii::t('AuthModule.main', 'User'),
                'type' => 'raw',
                'value' => function ($data, $row) use ($controller) {
                    return CHtml::link(CHtml::value($data, $controller->module->userNameColumn), array('view', 'id' => $data->{$controller->module->userIdColumn}));
                },
            ),
            array(
                'header' => Yii::t('AuthModule.main', 'Assigned items'),
                'type' => 'raw',
                'value' => function ($data, $row) use ($controller) {
                    $content = '';
                    /* @var $am CAuthManager|AuthBehavior */
                    $am = Yii::app()->getAuthManager();

                    $assignments = $am->getAuthAssignments($data->{$controller->module->userIdColumn});
                    $permissions = $am->getItemsPermissions(array_keys($assignments));
                    foreach ($permissions as $itemPermission) {
                        $content .= $itemPermission['item']->description;
                        $content .= ' <small>' . $controller->getItemTypeText($itemPermission['item']->type, false) . '</small><br />';
                    }

                    return $content;
                }
            ),
            array(
                'type' => 'raw',
                'value' => function ($data, $row) use ($controller) {
                    return CHtml::link(
                        '<i class="glyphicon glyphicon-eye-open"></i>',
                        array('view', 'id' => $data->{$controller->module->userIdColumn}),
                        array(
                            'class' => 'btn btn-default btn-xs',
                            'rel' => 'tooltip',
                            'title' => Yii::t('AuthModule.main', 'View'),
                        )
                    );
                }
            ),
        ),
    )
);
