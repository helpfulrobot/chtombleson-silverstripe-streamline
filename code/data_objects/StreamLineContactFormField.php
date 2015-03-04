<?php

class StreamLineContactFormField extends DataObject
{
    private static $defaults = array(
        'Type' => 'Text',
        'Required' => true,
    );

    private static $db = array(
        'Name'      => 'Varchar(50)',
        'Type'      => 'Varchar(30)',
        'Values'    => 'Text',
        'Required'  => 'Boolean',
        'SortOrder' => 'Int',
    );

    private static $default_sort = 'SortOrder';

    private static $has_one = array(
        'ContactPage' => 'StreamLineContactPage',
    );

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $name_field = new TextField('Name', 'Field Name');

        $type_field = new DropdownField(
            'Type',
            'Field Type',
            array(
                'TextField'     => 'Text',
                'EmailField'    => 'Email',
                'TextareaField' => 'Textarea',
                'DropdownField' => 'Dropdown',
            )
        );

        $required_field = new DropdownField(
            'Required',
            'Field Required',
            array(
                1 => 'Yes',
                0 => 'No'
            )
        );

        $values_field = new TextareaField('Values', 'Field Values');
        $values_field->setDescription('Values for dropdown type one per line.');

        $fields->addFieldToTab('Root.Main', $name_field);
        $fields->addFieldToTab('Root.Main', $type_field);
        $fields->addFieldToTab('Root.Main', $required_field);
        $fields->addFieldToTab('Root.Main', $values_field);
        $fields->removeFieldFromTab('Root.Main', 'ContactPage');
        $fields->removeFieldFromTab('Root.Main', 'SortOrder');

        return $fields;
    }

    public function requiredText()
    {
        return $this->Required ? 'Yes' : 'No';
    }
}