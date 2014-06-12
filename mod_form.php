<?php

/**
 * Config all Rich Media instances in this course.
 * 
 * Author:
 * 	Adrien Jamot  (adrien_jamot [at] symetrix [dt] fr)
 * 
 * @package   mod_richmedia
 * @copyright 2011 Symetrix
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once ($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/richmedia/locallib.php');

class mod_richmedia_mod_form extends moodleform_mod {

    function definition() {
        global $CFG, $COURSE, $USER, $PAGE, $DB;
        $PAGE->requires->jquery();
        $PAGE->requires->jquery_plugin('ui');
        $PAGE->requires->jquery_plugin('ui-css');
        $PAGE->requires->js('/mod/richmedia/lib/iris.min.js');
        $PAGE->requires->string_for_js('required', 'moodle');
        $PAGE->requires->js_init_call('M.mod_richmedia.setModForm');
        $cfg_richmedia = get_config('richmedia');

        $mform = $this->_form;

        /* GENERAL */
        $mform->addElement('header', 'general', get_string('generalinformation', 'richmedia'));
        // Name
        $mform->addElement('text', 'name', get_string('title', 'richmedia'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');

        // Summary
        $this->add_intro_editor(true);

        // Presentor		
        $mform->addElement('text', 'presentor', get_string('presentername', 'richmedia'));
        $mform->setDefault('presentor', $USER->firstname . ' ' . $USER->lastname);
        $mform->setType('presentor', PARAM_RAW);

        //KEYWORDS			
        $mform->addElement('text', 'keywords', get_string('keywords', 'richmedia'));
        $mform->setType('keywords', PARAM_RAW);

        /* APPEARANCE */

        $mform->addElement('header', 'appearance', get_string('appearance', 'richmedia'));
        // Player		
        $mform->addElement('select', 'html5', get_string('playertype', 'richmedia'), array(0 => 'Flash', 1 => 'HTML5'));
        $mform->addHelpButton('html5', 'html5', 'richmedia');
        $mform->setDefault('html5', 1);

        // Width
        $mform->addElement('text', 'width', get_string('width', 'richmedia'), 'maxlength="4" size="4"');
        $mform->setDefault('width', $cfg_richmedia->width);
        $mform->setType('width', PARAM_INT);
        $mform->disabledIf('width', 'html5', 'eq', 1);
        // Height
        $mform->addElement('text', 'height', get_string('height', 'richmedia'), 'maxlength="4" size="4"');
        $mform->setDefault('height', $cfg_richmedia->height);
        $mform->setType('height', PARAM_INT);
        $mform->disabledIf('height', 'html5', 'eq', 1);

        // Theme
        //themes dispos dans le repertoire des themes
        $themes = array();
        $dossierthemes = '../mod/richmedia/themes';
        if ($dossier = @opendir($dossierthemes)) {
            while (false !== ($fichier = readdir($dossier))) {
                if (is_dir($dossierthemes . '/' . $fichier) && $fichier != '.' && $fichier != '..' && $fichier != '.svn') {
                    $themes[$fichier] = $fichier;
                }
            }
        }
        $mform->addElement('select', 'theme', get_string('theme', 'richmedia'), $themes);
        $mform->addElement('html', '<a href="' . $CFG->wwwroot . '/mod/richmedia/edit_theme.php?course=' . $COURSE->id . '&context=' . $this->context->id . '">' . get_string('managethemes', 'richmedia') . '</a>');
        //Font
        $mform->addElement('select', 'font', get_string('police', 'richmedia'), array("Arial" => "Arial", "Courier new" => "Courier new", "Georgia" => "Georgia", "Times New Roman" => "Times New Roman", "Verdana" => "Verdana"));
        $mform->setDefault('font', $cfg_richmedia->font);

        $mform->addElement('text', 'fontcolor', get_string('fontcolor', 'richmedia'));
        $mform->setDefault('fontcolor', $cfg_richmedia->fontcolor);
        $mform->setType('fontcolor', PARAM_RAW);

        //View
        $mform->addElement('select', 'defaultview', get_string('defaultdisplay', 'richmedia'), array(1 => get_string('tile', 'richmedia'), 2 => get_string('slide', 'richmedia'), 3 => get_string('video', 'richmedia')));
        $mform->setDefault('defaultview', $cfg_richmedia->defaultview);

        //Autoplay
        $mform->addElement('select', 'autoplay', get_string('autoplay', 'richmedia'), array(0 => get_string('no'), 1 => get_string('yes')));
        $mform->setDefault('autoplay', $cfg_richmedia->autoplay);

        /* MEDIA */
        $mform->addElement('header', 'media', get_string('media', 'richmedia'));
        // New slides upload
        $mform->addElement('filepicker', 'referenceslides', get_string('mediacontent', 'richmedia'));
        $mform->addHelpButton('referenceslides', 'archive', 'richmedia');
        // New video or audio upload
        $maxbytes = get_max_upload_file_size($CFG->maxbytes, $COURSE->maxbytes);
        $mform->addElement('hidden', 'MAX_FILE_SIZE', $maxbytes);
        $mform->setType('MAX_FILE_SIZE', PARAM_RAW);
        $mform->addElement('filepicker', 'referencesvideo', get_string('presentationmedium', 'richmedia'));
        $mform->addRule('referencesvideo', null, 'required', null, 'client'); // New video or audio upload
        // New picture upload
        if (!$DB->record_exists('block', array('name' => 'webtv'))) {
            $mform->addElement('filepicker', 'referencesfond');
            $mform->setType('referencesfond', PARAM_RAW);
        } else {
            $mform->addElement('filepicker', 'referencesfond', 'Aperçu');
        }
        if ($DB->record_exists('modules', array('name' => 'symquiz'))) {
            $quizs = $DB->get_records('symquiz');
            $quizSelect = array();
            $quizSelect[0] = 'Aucun';
            foreach ($quizs as $quiz) {
                $quizSelect[$quiz->id] = $quiz->name;
            }
            $mform->addElement('select', 'quizid', 'Quiz', $quizSelect);
        } else {
            $mform->addElement('hidden', 'quizid', 0);
            $mform->setType('quizid', PARAM_INT);
        }

        /* Synchronisation */
        $mform->addElement('header', 'synchronization', get_string('synchronization', 'richmedia'));
        // New xml upload
        $mform->addElement('filepicker', 'referencesxml', get_string('filexml', 'richmedia'));
        $mform->addHelpButton('referencesxml', 'filexml', 'richmedia');

        $mform->addElement('html', '<input type="button" value="' . get_string('createedit', 'richmedia') . '" style="margin : auto;display : block;margin-top : 5px;" id="editsync">');
        // Hidden Settings
        $mform->addElement('hidden', 'richmediatype', RICHMEDIA_TYPE_LOCAL);
        $mform->setType('richmediatype', PARAM_RAW);
        $mform->addElement('hidden', 'datadir', null);
        $mform->setType('datadir', PARAM_RAW);
        $mform->addElement('hidden', 'pkgtype', null);
        $mform->setType('pkgtype', PARAM_RAW);
        $mform->addElement('hidden', 'launch', null);
        $mform->setType('launch', PARAM_RAW);
        $mform->addElement('hidden', 'redirect', null);
        $mform->setType('redirect', PARAM_RAW);
        $mform->addElement('hidden', 'redirecturl', null);
        $mform->setType('redirecturl', PARAM_RAW);


//-------------------------------------------------------------------------------
        $this->standard_coursemodule_elements();
//-------------------------------------------------------------------------------
        // buttons
        $this->add_action_buttons();
    }

    /**
     * * Fill in the form
     * */
    function data_preprocessing(&$default_values) {
        $draftitemidvideo = file_get_submitted_draft_itemid('referencesvideo');
        file_prepare_draft_area($draftitemidvideo, $this->context->id, 'mod_richmedia', 'content', 0, array('subdirs' => 1));
        $default_values['referencesvideo'] = $draftitemidvideo;


        $draftitemidslides = file_get_submitted_draft_itemid('referenceslides');
        file_prepare_draft_area($draftitemidslides, $this->context->id, 'mod_richmedia', 'package', 0);
        $default_values['referenceslides'] = $draftitemidslides;


        $draftitemidxml = file_get_submitted_draft_itemid('referencesxml');
        file_prepare_draft_area($draftitemidxml, $this->context->id, 'mod_richmedia', 'content', 0);
        $default_values['referencesxml'] = $draftitemidxml;

        $draftitemidfond = file_get_submitted_draft_itemid('referencesfond');
        file_prepare_draft_area($draftitemidfond, $this->context->id, 'mod_richmedia', 'picture', 0);
        $default_values['referencesfond'] = $draftitemidfond;


        $default_values['redirect'] = 'yes';
        $default_values['redirecturl'] = '../course/view.php?id=' . $default_values['course'];
    }

    /**
     * * valid the form
     * */
    function validation($data, $files) {
        global $CFG;
        $errors = parent::validation($data, $files);

        $type = $data['richmediatype'];

        if ($type === RICHMEDIA_TYPE_LOCAL) {
            if (empty($data['referencesvideo'])) {
                $errors['referencesvideo'] = get_string('required');
            } else if (!empty($data['referenceslides'])) {
                /*                 * *slides (rep data)** */
                $files = $this->get_draft_files('referenceslides');
                if (count($files) > 1) {

                    $file = reset($files);
                    $filename = $CFG->dataroot . '/temp/richmediaimport/richmedia_' . time();
                    make_upload_directory('temp/richmediaimport');
                    $file->copy_content_to($filename);

                    $packer = get_file_packer('application/zip');

                    $filelist = $packer->list_files($filename);
                    if (!is_array($filelist)) {
                        $errors['referenceslides'] = get_string('errorarchive', 'richmedia');
                    }
                    foreach ($filelist as $file) {
                        $length = strlen('slides/');
                        if (substr($file->pathname, 0, $length) !== 'slides/') {
                            $errors['referenceslides'] = get_string('errorslides', 'richmedia');
                            return $errors;
                        }
                    }
                    unlink($filename);

                    /* video */
                    $filesvideo = $this->get_draft_files('referencesvideo');
                    if (count($filesvideo) < 1) {
                        $errors['referencesvideo'] = get_string('required');
                        return $errors;
                    }
                    $filesvideo = reset($filesvideo);
                    $filenamevideo = $CFG->dataroot . '/temp/richmediaimport/richmedia_' . time();
                    $filesvideo->copy_content_to($filenamevideo);
                    unlink($filenamevideo);

                    /* xml */
                    $filesxml = $this->get_draft_files('referencesxml');

                    if (count($filesxml) > 0) {
                        $filexml = reset($filesxml);
                        $xmlextension = explode('.', $filexml->get_filename());
                        if (end($xmlextension) != 'xml') {
                            $errors['referencesxml'] = get_string('errorxml', 'richmedia');
                            return $errors;
                        }
                        $filenamexml = $CFG->dataroot . '/temp/richmediaimport/richmedia_' . time();
                        $filexml->copy_content_to($filenamexml);
                        unlink($filenamexml);
                    }
                }
            }
        }
        return $errors;
    }

    //need to translate the "options" and "reference" field.
    function set_data($default_values) {
        $default_values = (array) $default_values;
        $this->data_preprocessing($default_values);
        parent::set_data($default_values);
    }

}
