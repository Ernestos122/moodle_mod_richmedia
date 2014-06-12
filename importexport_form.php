<?php

/**
 * Form to import/export the richmedia
 * Author:
 * 	Adrien Jamot  (adrien_jamot [at] symetrix [dt] fr)
 * 
 * @package   mod_richmedia
 * @copyright 2011 Symetrix
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir . '/formslib.php');

class mod_richmedia_import_form extends moodleform {

    function definition() {
        $mform = & $this->_form;
        $mform->addElement('header', 'import', get_string('import', 'richmedia'));
        $mform->addElement('filepicker', 'file', get_string('richmediaarchive', 'richmedia'));
        $mform->addRule('file', null, 'required', null, 'client');
        $submit_string = get_string('submit');
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $this->add_action_buttons(false, $submit_string);
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (empty($data['file'])) {
            $errors['file'] = get_string('required');
        }
        return $errors;
    }

}

class mod_richmedia_export_form extends moodleform {

    function definition() {
        $mform = & $this->_form;
        $mform->addElement('header', 'exporttitle', get_string('export', 'richmedia'));
        $mform->addElement('hidden', 'export', 1);
        $mform->setType('export', PARAM_INT);
        $mform->addElement('text', 'name', get_string('filename', 'richmedia'));
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);
        $mform->addElement('advcheckbox', 'exportscorm', get_string('scormformat', 'richmedia'), '');
        $submit_string = 'Exporter';
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $this->add_action_buttons(false, $submit_string);
    }

}

class mod_richmedia_error_form extends moodleform {

    function definition() {
        $mform = & $this->_form;
        $submit_string = 'Ok';
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $this->add_action_buttons(false, $submit_string);
    }

}
