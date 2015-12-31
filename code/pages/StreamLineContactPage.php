<?php

class StreamLineContactPage extends Page
{
    private static $db = array(
        'DefaultRecipient' => 'Varchar(150)',
        'Subject' => 'Varchar(150)',
        'DefaultFrom' => 'Varchar(150)',
        'RecipientMap' => 'Text',
        'RecipientMapField' => 'Varchar(50)',
    );

    private static $has_many = array(
        'ContactFields' => 'StreamLineContactFormField',
    );

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        // Contact Form settings
        $recipient_field = new EmailField('DefaultRecipient', 'Email recipient');
        $recipient_field->setDescription('Default email address to send submissions to.');

        $subject_field = new TextField('Subject', 'Email subject');
        $subject_field->setDescription('Subject for the email.');

        $default_from_field = new EmailField('DefaultFrom', 'Email from');
        $default_from_field->setDescription('Default from email address.');

        $fields->addFieldToTab('Root.ContactForm.Settings', $recipient_field);
        $fields->addFieldToTab('Root.ContactForm.Settings', $subject_field);
        $fields->addFieldToTab('Root.ContactForm.Settings', $default_from_field);

        // Contact Form fields
        $conf = GridFieldConfig_RelationEditor::create(10);
        $conf->addComponent(new GridFieldSortableRows('SortOrder'));

        $data_columns = $conf->getComponentByType('GridFieldDataColumns');

        $data_columns->setDisplayFields(array(
            'Name' => 'Name',
            'Type'=> 'Type',
            'requiredText' => 'Required',
        ));

        $contact_form_fields = new GridField(
            'Fields',
            'Field',
            $this->ContactFields(),
            $conf
        );

        $fields->addFieldToTab('Root.ContactForm.Fields', $contact_form_fields);

        // Recipient map
        $contact_fields = array();

        foreach ($this->ContactFields() as $contact_field) {
            $contact_fields[$contact_field->Name] = $contact_field->Name;
        }

        $recipient_map_field_field = new DropdownField('RecipientMapField', 'Recipient Map Field', $contact_fields);
        $recipient_map_field_field->setDescription('Field used to map recipients.');

        $recipient_map_field = new TextareaField('RecipientMap', 'Recipient Map');
        $recipient_map_field->setDescription('Map field values to an email address (format: value:email address) one per line.');

        $fields->addFieldToTab('Root.ContactForm.RecipientMap', $recipient_map_field_field);
        $fields->addFieldToTab('Root.ContactForm.RecipientMap', $recipient_map_field);

        return $fields;
    }
}

class StreamLineContactPage_Controller extends Page_Controller
{
    private static $allowed_actions = array('ContactForm');

    public function ContactForm()
    {
        return $this->buildForm();
    }

    public function doSubmitContactForm($data, Form $form)
    {
        $subject = !empty($this->Subject) ? $this->Subject : 'Contact form submission';

        $email = new Email($this->getEmailFrom($data), $this->getEmailTo($data), $subject);
        $email->setTemplate('ContactForm');
        $email->populateTemplate(array('Body' => $this->buildEmailBody($data)));
        $email->send();

        Controller::redirect($this->Link("?success=1"));
    }

    private function buildForm()
    {
        $contact_fields = $this->ContactFields();

        $fields = new FieldList();
        $required_fields = array();

        foreach ($contact_fields as $contact_field) {
            switch ($contact_field->Type) {
                case 'TextField':
                    $fields->add(new TextField($contact_field->Name, $contact_field->Name));
                    break;

                case 'EmailField':
                    $fields->add(new EmailField($contact_field->Name, $contact_field->Name));
                    break;

                case 'TextareaField':
                    $fields->add(new TextareaField($contact_field->Name, $contact_field->Name));
                    break;

                case 'DropdownField':
                    $values = explode("\n", $contact_field->Values);
                    $dropdown_values = array();

                    foreach ($values as $value) {
                        $dropdown_values[trim($value)] = trim($value);
                    }

                    $fields->add(new DropdownField($contact_field->Name, $contact_field->Name, $dropdown_values));
                    break;
            }

            if ($contact_field->Required) {
                $required_fields[] = $contact_field->Name;
            }
        }

        $actions = new FieldList(
            FormAction::create("doSubmitContactForm")->setTitle("Contact")
        );

        $required = new RequiredFields($required_fields);
        $form = new Form($this, 'ContactForm', $fields, $actions, $required);

        return $form;
    }

    private function buildEmailBody($data)
    {
        $contact_fields = $this->ContactFields();

        $body = '<ul>';

        foreach ($contact_fields as $contact_field) {
            if (isset($data[$contact_field->Name])) {
                $body .= '<li>' . $contact_field->Name . ': ' . nl2br($data[$contact_field->Name]) . '</li>' . "\r\n";
            }
        }

        $body .= '</ul>';

        return $body;
    }

    private function getEmailFrom($data)
    {
        $email_field = null;
        $contact_fields = $this->ContactFields();

        foreach ($contact_fields as $contact_field) {
            if ($contact_field->Type == 'EmailField') {
                $email_field = $contact_field;
            }
        }

        if ((!empty($email_field) && !empty($data[$email_field->Name])) &&
            Email::validEmailAddress($data[$email_field->Name])) {
            return $data[$email_field->Name];
        }

        return $this->DefaultFrom;
    }

    private function getEmailTo($data)
    {
        $recipient_field = $this->RecipientMapField;
        $recipient_map = $this->RecipientMap;

        if (isset($data[$recipient_field])) {
            $lines = explode("\n", $recipient_map);

            foreach ($lines as $line) {
                $value = explode(':', $line);
                var_dump($value);

                if ($value[0] == $data[$recipient_field]) {
                    if (Email::validEmailAddress(trim($value[1]))) {
                        return trim($value[1]);
                    }
                }
            }
        }

        return $this->DefaultRecipient;
    }
}
