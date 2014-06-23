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

<h1><?php echo CHtml::encode(CHtml::value($model, $this->module->userNameColumn)); ?>
    <small><?php echo Yii::t('AuthModule.main', 'Assignments'); ?></small>
</h1>

<div class="row">

    <div class="span6">
        <fieldset>
            <legend>
                <?php echo Yii::t('AuthModule.main', 'Permissions'); ?>
                <small><?php echo Yii::t('AuthModule.main', 'Items assigned to this user'); ?></small>
            </legend>

            <?php if (empty($assignmentTree)) : ?>
                <?php echo Yii::t('AuthModule.main', 'This user does not have any assignments.');?>
            <?php else: ?>
                <?php $form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(/*'type'=>'inline'*/)); ?>
                <button type="submit" class="btn btn-primary"><?php echo Yii::t('AuthModule.main', 'Save');?></button>
                <a href="#" id="collapse-all" class="btn btn-default"><?php echo Yii::t('AuthModule.main', 'Collapse all');?></a>
                <a href="#" id="expand-selected" class="btn btn-default"><?php echo Yii::t('AuthModule.main', 'Expand selected');?></a>
                <?php $widget = $this->widget('SimpleTreeView', array('items' => $assignmentTree));?>
                <?php Yii::app()->getClientScript()->registerScript('initAuthHelper'.__FILE__, "authHelper.initToggleChildItems('#{$widget->id}');", CClientScript::POS_READY);?>
                <?php Yii::app()->getClientScript()->registerScript('collapseAll'.__FILE__, "$('#collapse-all').click(function(e){ $('#{$widget->id}').simpleTreeView('collapseAll'); return false;});", CClientScript::POS_READY);?>
                <?php Yii::app()->getClientScript()->registerScript('expandSelected'.__FILE__, "$('#expand-selected').click(function(e){ authHelper.expandSelectedBranches('#{$widget->id}'); return false;});", CClientScript::POS_READY);?>
                <?php $this->endWidget(); ?>
            <?php endif; ?>
        </fieldset>
	</div>
</div>