<?php
/* @var $this OperationController|TaskController|RoleController */
/* @var $item CAuthItem */
/* @var $ancestorDp AuthItemDataProvider */
/* @var $descendantDp AuthItemDataProvider */
/* @var $formModel AddAuthItemForm */
/* @var $form TbActiveForm */
/* @var $childOptions array */

$this->breadcrumbs = array(
    $this->capitalize($this->getTypeText(true)) => array('index'),
    $item->description,
);

Yii::app()->getClientScript()->registerCss('toggle-css', '.toggle-auth {min-width: 1em;text-align: center;}');
?>
    <?php
$this->widget(
    'zii.widgets.CMenu',
    array(
        'htmlOptions' => array('class' => 'nav nav-tabs'),
        'items' => $this->menu,
    )
);?>
<fieldset>

    <h3>
        <?php echo CHtml::encode($item->description); ?> <small><?php echo $this->getTypeText(); ?></small>
        <div class="pull-right">
            <?php echo CHtml::link(Yii::t('AuthModule.main', 'Edit'), array('update', 'name'=>$item->name), array('class' => 'btn btn-default'));?>
            <?php echo CHtml::link('<i class="fa fa-trash-o"></i>', array('delete', 'name'=>$item->name), array('class' => 'btn btn-default',
                        'confirm'=>Yii::t('AuthModule.main', 'Are you sure you want to delete this item?'),
                ));?>
        </div>
    </h3>
    <div class="row">
        <div class="col-sm-12 col-md-10 col-lg-8">
        <?php $this->widget('zii.widgets.CDetailView', array(
            'data' => $item,
            'htmlOptions' => array('class' => 'table table-striped'),
            'attributes' => array(
                array(
                    'name' => 'name',
                    'label' => Yii::t('AuthModule.main', 'System name'),
                ),
                array(
                    'name' => 'description',
                    'label' => Yii::t('AuthModule.main', 'Description'),
                ),
            ),
        )); ?>
        </div>
    </div>
</fieldset>

<hr />

<div class="row">

	<div class="col-sm-6">
        <fieldset>
            <h3>
                <?php echo Yii::t('AuthModule.main', 'Descendants'); ?>
                <small><?php echo Yii::t('AuthModule.main', 'Permissions granted by this item'); ?></small>
            </h3>

            <?php if (empty($descendantsTree)) : ?>
                <?php echo Yii::t('AuthModule.main', 'This item does not have any descendants.');?>
            <?php else: ?>
                <?php $form = $this->beginWidget('CActiveForm', array('htmlOptions' => array('autocomplete' => 'off'))); ?>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary"><?php echo Yii::t('AuthModule.main', 'Save');?></button>
                    <a href="#" id="collapse-all" class="btn btn-default"><?php echo Yii::t('AuthModule.main', 'Collapse all');?></a>
                    <a href="#" id="expand-selected" class="btn btn-default"><?php echo Yii::t('AuthModule.main', 'Expand selected');?></a>
                </div>
                <div class="input-group">
                    <span class="input-group-addon"><i class="glyphicon glyphicon-search"></i></span>
                    <input class="form-control" type="text" placeholder="<?php echo Yii::t('app', 'Search'); ?>" name="search" id="search-tree">
                </div>
                <?php $widget = $this->widget('SimpleTreeView', array(
                    'items' => $descendantsTree,
                    'itemTemplate' => '{icon}<span class="title text-muted">{label}</span><span class="right-control">{rightControl}</span>',
                ));?>
                <?php Yii::app()->getClientScript()->registerScript('initAuthHelper'.__FILE__, "authHelper.initToggleChildItems('#{$widget->id}');", CClientScript::POS_READY);?>
                <?php Yii::app()->getClientScript()->registerScript('collapseAll'.__FILE__, "$('#collapse-all').click(function (e) { $('#{$widget->id}').simpleTreeView('collapseAll'); return false;});", CClientScript::POS_READY);?>
                <?php Yii::app()->getClientScript()->registerScript('expandSelected'.__FILE__, "$('#expand-selected').click(function (e) { authHelper.expandSelectedBranches('#{$widget->id}'); return false;});", CClientScript::POS_READY);?>
                <?php $this->endWidget(); ?>
            <?php endif; ?>
        </fieldset>
	</div>

    <div class="col-sm-6">
        <fieldset>
            <h3>
                <?php echo Yii::t('AuthModule.main', 'Ancestors'); ?>
                <small><?php echo Yii::t('AuthModule.main', 'Permissions that inherit this item'); ?></small>
            </h3>

            <?php if (empty($ancestorsTree)) : ?>
                <?php echo Yii::t('AuthModule.main', 'This item does not have any ancestors.');?>
            <?php else: ?>
                <a href="#" id="collapse-all-ancestors" class="btn btn-default"><?php echo Yii::t('AuthModule.main', 'Collapse all');?></a>
                <a href="#" id="expand-all-ancestors" class="btn btn-default"><?php echo Yii::t('AuthModule.main', 'Expand selected');?></a>
                <?php $widget = $this->widget('SimpleTreeView', array('items' => $ancestorsTree));?>
                <?php Yii::app()->getClientScript()->registerScript('initAuthHelper'.__FILE__, "authHelper.initToggleChildItems('#{$widget->id}');", CClientScript::POS_READY);?>
                <?php Yii::app()->getClientScript()->registerScript('collapseAllAncestors'.__FILE__, "$('#collapse-all-ancestors').click(function (e) { $('#{$widget->id}').simpleTreeView('collapseAll'); return false;});", CClientScript::POS_READY);?>
                <?php Yii::app()->getClientScript()->registerScript('expandAllAncestors'.__FILE__, "$('#expand-all-ancestors').click(function (e) { $('#{$widget->id}').simpleTreeView('expandAll'); return false;});", CClientScript::POS_READY);?>
            <?php endif; ?>
        </fieldset>
	</div>
</div>
