<?php
/* @var $this OperationController|TaskController|RoleController */
/* @var $model AuthItemForm */
/* @var $form TbActiveForm */

$this->breadcrumbs = array(
    $this->capitalize($this->getTypeText(true)) => array('index'),
    Yii::t('AuthModule.main', 'New {type}', array('{type}' => $this->getTypeText())),
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
<h1><?php echo Yii::t('AuthModule.main', 'New {type}', array('{type}' => $this->getTypeText())); ?></h1>

<section class="row">
    <?php $form = $this->beginWidget('CActiveForm',  array(
       'id' => $this->id . '-form',
       'enableAjaxValidation' => true,
)    ); ?>

    <article class="col-sm-12">
        <?php echo $form->errorSummary($model); ?>
        <fieldset>
            <h4><?php echo Yii::t('app', 'Attributes');?></h4>
            <?php echo $form->hiddenField($model, 'type'); ?>
            <div class="form-group">
                <?php echo $form->labelEx($model,'name'); ?>
                <?php echo $form->textField($model, 'name', array('class' => 'form-control')); ?>
                <?php echo $form->error($model,'name'); ?>
            </div>
            <div class="form-group">
                <?php echo $form->labelEx($model,'description'); ?>
                <?php echo $form->textField($model, 'description', array('class' => 'form-control')); ?>
                <?php echo $form->error($model,'description'); ?>
            </div>
        </fieldset>

        <div class="form-actions" style="margin: 0 -10px -10px">
            <button type="submit" class="btn btn-primary"><i class="glyphicon glyphicon-floppy-disk"></i> <?php echo Yii::t('AuthModule.main', 'Create') ?></button>
            <button type="submit" class="btn btn-default"><i class="glyphicon glyphicon-remove"></i> <?php echo Yii::t('AuthModule.main', 'Cancel') ?></button>
        </div>
    </article>
    <?php $this->endWidget(); ?>
</section>
