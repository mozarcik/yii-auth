<?php
/* @var $this AssignmentController */
/* @var $model User */
/* @var $authItemDp AuthItemDataProvider */
/* @var $formModel AddAuthItemForm */
/* @var $form TbActiveForm */
/* @var $assignmentOptions array */

$this->breadcrumbs = array(
    Yii::t('AuthModule.main', 'Assignments') => array('index'),
    CHtml::value($model, $this->module->userNameColumn),
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
<h1><?php echo CHtml::encode(CHtml::value($model, $this->module->userNameColumn)); ?>
    <small><?php echo Yii::t('AuthModule.main', 'Assignments'); ?></small>
</h1>

<div class="row">

    <div class="col-sm-6">
        <fieldset>
            <h2>
                <?php echo Yii::t('AuthModule.main', 'Permissions'); ?>
                <small><?php echo Yii::t('AuthModule.main', 'Items assigned to this user'); ?></small>
            </h2>

            <?php if (empty($assignmentTree)) : ?>
                <?php echo Yii::t('AuthModule.main', 'This user does not have any assignments.');?>
            <?php else: ?>
                <?php ob_start();?>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary"><?php echo Yii::t('AuthModule.main', 'Save');?></button>
                        {collapse}
                        {expand}
                    </div>
                    <div class="input-group">
                        <span class="input-group-addon"><i class="glyphicon glyphicon-search"></i></span>
                        {searchInput}
                    </div>
                <?php $toolbarTemplate = ob_get_clean();?>
                <?php $widget = $this->widget('SimpleTreeView', array(
                    'id' => 'stv-assignments',
                    'items' => $assignmentTree,
                    'toolbarTemplate' => $toolbarTemplate,
                    'toolbarButtons' => array(
                        'collapse' => array('label' => Yii::t('AuthModule.main', 'Collapse all')),
                        'expand' => array(
                            'label' => Yii::t('AuthModule.main', 'Expand selected'),
                            'click' => "function(e){ authHelper.expandSelectedBranches('#stv-assignments'); return false;}"
                        ),
                    )
                ));?>
                <?php Yii::app()->getClientScript()->registerScript('initAuthHelper'.__FILE__, "authHelper.initToggleChildItems('#{$widget->id}');", CClientScript::POS_READY);?>
                <?php $this->endWidget(); ?>
            <?php endif; ?>
        </fieldset>
	</div>
</div>