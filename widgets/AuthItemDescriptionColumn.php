<?php

class AuthItemDescriptionColumn extends AuthItemColumn
{
    /**
     * Initializes the column.
     */
    public function init()
    {
        if (isset($this->htmlOptions['class']))
            $this->htmlOptions['class'] .= ' auth-item-description-column';
        else
            $this->htmlOptions['class'] = 'auth-item-description-column';
    }

    /**
     * Renders the data cell content.
     * @param integer $row the row number (zero-based)
     * @param mixed $data the data associated with the row
     */
    protected function renderDataCellContent($row, $data)
    {
        /* @var $am CAuthManager|AuthBehavior */
        $am = Yii::app()->getAuthManager();

        $linkCssClass = $this->active || $am->hasParent($this->itemName, $data['name']) || $am->hasChild($this->itemName, $data['name'])
            ? 'active'
            : 'disabled';

        echo CHtml::link($data['item']->getDescription(),
                array('/auth/authItem/view', 'name' => $data['name']), array( 'class' => $linkCssClass)
        );
    }
}