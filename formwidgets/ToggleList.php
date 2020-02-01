<?php namespace Tlokuus\LoginWithSocial\FormWidgets;

use Backend\Classes\FormWidgetBase;

class ToggleList extends FormWidgetBase
{
    
    protected $defaultAlias = 'toggelist';

    public function prepareVars()
    {
        $this->vars['name'] = $this->getFieldName();
        $this->vars['selectedValues'] = $this->getLoadValue();
    }

    public function render() {
        $this->prepareVars();
        return $this->makePartial('field_togglelist');
    }

    public function toggles() {
        $toggles = [];
        foreach($this->options() as $option) {
            $field = clone $this->formField;
            $field->type = "switch";
            $field->label = $option[0];
            $field->value = in_array($field->label, $this->vars['selectedValues']);
            foreach($option[1] as $attr_name => $attr_val) {
                if(isset($field->$attr_name)) {
                    $field->$attr_name = $attr_val;
                } else {
                    $field->attributes[$attr_name] = $attr_val;
                }
            }
            $toggles[] = [
                'field' => $field
            ];
        }

        return $toggles;
    }

    public function options() {
        $method = 'get' . ucfirst($this->fieldName) . 'Options';
        return $this->model->$method();
    }

    public function getSaveValue($value)
    {
        return $value ? array_keys($value) : [];
    }

    public function getLoadValue()
    {
        $value = parent::getLoadValue();
        return is_array($value) ? $value : [];
    }
}