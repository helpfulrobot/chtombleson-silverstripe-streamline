# Streamline Contact Form

This is an easy to configure contact form. The main features of the form is
the customisation, adding new fields to the form and sending the submissions
to different email addresses based on the value of one of the form fields.

## Adding fields

Currently the following field types are available:

  * TextField
  * EmailField
  * TextareaField
  * DropdownField

Fields can also be marked as required.

## Recipient Mapping

This allows you to map recipients for the submission based on a value from one
of the form fields.

For example if you had a region dropdown field in the form you can send the email
to the appropriate email address for that region. Mapping are defined as followed:

    [value]:[email address]
    Auckland:auckland@example.com
    Wellington:wellington@example.com
    Christchurch:christchurch@example.com
