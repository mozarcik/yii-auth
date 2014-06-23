<?php
/* @var $this OperationController|TaskController|RoleController */
/* @var $model AuthItemForm */
/* @var $item CAuthItem */
/* @var $form TbActiveForm */

$this->breadcrumbs = array(
	$this->capitalize($this->getTypeText(true)) => array('index'),
	$item->description => array('view', 'name' => $item->name),
	Yii::t('AuthModule.main', 'Edit'),
);
$formDefaults = array(
    'id' => $this->id . '-form',
    'enableAjaxValidation' => true,
);
?>

<h1>
    <?php echo CHtml::encode($item->description); ?>
    <small><?php echo $this->getTypeText(); ?></small>
</h1>



<section class="row">
    <?php $this->widget('bootstrap.widgets.TbAlert'); ?>
    <?php $form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
	    'id' => $this->id . '-form',
	    'enableAjaxValidation' => true,
	)); ?>

    <article class="span12">
        <?php echo $form->errorSummary($model); ?>
        <fieldset>
            <legend><?php echo Yii::t('app', 'Attributes');?></legend>
            <?php echo $form->hiddenField($model, 'type'); ?>
            <?php echo $form->textFieldRow($model, 'name', array(
                'disabled'  => true,
                'title'     => Yii::t('AuthModule.main', 'System name cannot be changed after creation.'),
                'help'     => Yii::t('AuthModule.main', 'System name cannot be changed after creation.'),
            )); ?>
            <?php echo $form->textFieldRow($model, 'description'); ?>
        </fieldset>

        <div class="form-actions" style="margin: 0 -10px -10px">
            <button type="submit" class="btn btn-primary"><i class="icon-ok"></i> <?php echo Yii::t('AuthModule.main', 'Save') ?></button>
            <a href="<?php echo $this->createUrl('view', array('name' => $item->name));?>" class="btn btn-default"><i class="icon-remove"></i> <?php echo Yii::t('AuthModule.main', 'Cancel') ?></a>
        </div>
    </article>
    <?php $this->endWidget(); ?>
</section>

