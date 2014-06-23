<?php
/* @var $this OperationController|TaskController|RoleController */
/* @var $model AuthItemForm */
/* @var $form TbActiveForm */

$this->breadcrumbs = array(
	$this->capitalize($this->getTypeText(true)) => array('index'),
	Yii::t('AuthModule.main', 'New {type}', array('{type}' => $this->getTypeText())),
);


?>

    <h1><?php echo Yii::t('AuthModule.main', 'New {type}', array('{type}' => $this->getTypeText())); ?></h1>


<section class="row">
    <?php $this->widget('bootstrap.widgets.NetAlert'); ?>
    <?php $form = $this->beginWidget('bootstrap.widgets.TbActiveForm',  array(
 	   'id' => $this->id . '-form',
 	   'enableAjaxValidation' => true,
)	); ?>

    <article class="span12">
        <?php echo $form->errorSummary($model); ?>
        <fieldset>
            <legend><?php echo Yii::t('app', 'Attributes');?></legend>
            <?php echo $form->hiddenField($model, 'type'); ?>
            <?php echo $form->textFieldRow($model, 'name'); ?>
            <?php echo $form->textFieldRow($model, 'description'); ?>
        </fieldset>

        <div class="form-actions" style="margin: 0 -10px -10px">
            <button type="submit" class="btn btn-primary"><i class="icon-ok"></i> <?php echo Yii::t('AuthModule.main', 'Create') ?></button>
            <button type="submit" class="btn btn-default"><i class="icon-remove"></i> <?php echo Yii::t('AuthModule.main', 'Cancel') ?></button>
        </div>
    </article>
    <?php $this->endWidget(); ?>
</section>


