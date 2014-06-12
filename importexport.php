<?php

/**
 * Import/export the richmedia
 * Author:
 * 	Adrien Jamot  (adrien_jamot [at] symetrix [dt] fr)
 * 
 * @package   mod_richmedia
 * @copyright 2011 Symetrix
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

require_once("../../config.php");
require_once("$CFG->dirroot/course/lib.php");
require_once('importexport_form.php');
require_once('locallib.php');

$id = required_param('id', PARAM_INT);    // Course Module ID

$url = new moodle_url('/mod/richmedia/importexport.php', array('id' => $id));

$PAGE->set_url($url);

if (!$cm = get_coursemodule_from_id('richmedia', $id)) {
    print_error('invalidcoursemodule');
}

if (!$course = $DB->get_record("course", array("id" => $cm->course))) {
    print_error('coursemisconf');
}

if (!$richmedia = $DB->get_record("richmedia", array("id" => $cm->instance))) {
    print_error('invalidid', 'richmedia');
}

require_login($course->id, false, $cm);

$context = context_module::instance($cm->id);

$formimport = new mod_richmedia_import_form();
$formexport = new mod_richmedia_export_form();
$formerror  = new mod_richmedia_error_form();

if ($data = $formexport->get_data()) {
    if ($data->name != '') {
        $scorm = $data->exportscorm ? true : false;
        richmedia_export_scorm($richmedia, $data->name, $context, $scorm);
    }
} else if ($data = $formimport->get_data()) {
    $fs = get_file_storage();
    if ($formimport) {
        $filename = $formimport->get_new_filename('file');
        if ($filename !== false) {
            $fs->delete_area_files($context->id, 'mod_richmedia', 'zip');
            $fs->delete_area_files($context->id, 'mod_richmedia', 'zipcontent');
            $zip = $formimport->save_stored_file('file', $context->id, 'mod_richmedia', 'zip', 0, '/', $filename);
        }

        $packer = get_file_packer('application/zip');

        //CHECK FILES
        $filelist = $zip->list_files($packer);
        $settingsfound = false;
        foreach ($filelist as $file) {
            if (preg_match("/contents\/content\/settings.xml/i", $file->pathname)) {
                $settingsfound = true;
                $zip->extract_to_storage($packer, $context->id, 'mod_richmedia', 'zipcontent', 0, '/');
                $fs->delete_area_files($context->id, 'mod_richmedia', 'content');
            }
        }
        if (!$settingsfound) {
            $PAGE->navbar->add($richmedia->name);
            $PAGE->set_title(format_string($richmedia->name));
            $PAGE->set_heading($course->fullname);

            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('importexport', 'richmedia'));
            echo $OUTPUT->box_start('richmediadisplay generalbox');
            echo 'Erreur : settings not found';
            $data = new stdClass();
            $data->id = $id;
            $formerror->set_data($data);
            $formerror->display();
            echo $OUTPUT->box_end();
            echo $OUTPUT->footer();
            exit;
        } else {
            $files = $fs->get_area_files($context->id, 'mod_richmedia', 'zipcontent', 0, null, $includedirs = true);
            foreach ($files as $storedfile) {
                if ($storedfile->get_filename() == 'settings.xml') {
                    $changes = array('filearea' => 'content', 'filepath' => '/');
                    $newxml = $fs->create_file_from_storedfile($changes, $storedfile);
                    $richmedia->referencesxml = $storedfile->get_filename();
                    $contenuxml = $newxml->get_content();
                    $contenuxml = str_replace('&', '&amp;', $contenuxml);

                    //PARSE LE XML
                    $xml = simplexml_load_string($contenuxml);
                    foreach ($xml->titles[0]->title[0]->attributes() as $attribute => $value) {
                        if ($attribute == 'label') {
                            $richmedia->name = (String) $value;
                        }
                    }
                    foreach ($xml->presenter[0]->attributes() as $attribute => $value) {
                        if ($attribute == 'name') {
                            $richmedia->presentor = (String) $value;
                        }
                        if ($attribute == 'biography') {
                            if ($value && $value != '') {
                                $richmedia->intro = (String) $value;
                            } else {
                                $richmedia->intro = $richmedia->name;
                            }
                        }
                    }
                    foreach ($xml->options[0]->attributes() as $attribute => $value) {
                        if ($attribute == 'defaultview') {
                            if (!$value || $value == 'false') {
                                $value = 0;
                            }
                            $richmedia->defaultview = (String) $value;
                        }
                        if ($attribute == 'autoplay') {
                            if (!$value || $value == 'false') {
                                $value = '0';
                            } else {
                                $value = '1';
                            }
                            $richmedia->autoplay = (String) $value;
                        }
                    }
                    foreach ($xml->design[0]->attributes() as $attribute => $value) {
                        if ($attribute == 'fontcolor') {
                            $fontcolor = $value;
                            if ($fontcolor[0] == '#') {
                                $fontcolor = substr($fontcolor, 1);
                            }
                        }
                        if ($attribute == 'font') {
                            $richmedia->font = (String) $value;
                        }
                    }
                } else if (preg_match("/content\/slides/i", $storedfile->get_filepath())) {
                    $changes = array('filearea' => 'content', 'filepath' => '/slides/');
                    $slide = $fs->create_file_from_storedfile($changes, $storedfile);
                    if (!$storedfile->is_directory()) {
                        $zipfiles['slides/' . $storedfile->get_filename()] = $slide;
                    }
                } else if (preg_match("/content\/video/i", $storedfile->get_filepath())) {
                    $changes = array('filearea' => 'content', 'filepath' => '/video/');
                    $fs->create_file_from_storedfile($changes, $storedfile);
                    $richmedia->referencesvideo = $storedfile->get_filename();
                } else if (preg_match("/theme/i", $storedfile->get_filepath())) {
                    $split = explode('/', $storedfile->get_filepath());
                    if ($split[3] && !is_dir('themes/' . $split[3])) {
                        mkdir('themes/' . $split[3], 0775);
                    }
                    $richmedia->theme = $split[3];
                    $storedfile->copy_content_to('themes/' . $split[3] . '/' . $storedfile->get_filename());
                }
            }
            $fs->delete_area_files($context->id, 'mod_richmedia', 'zipcontent');
            $DB->update_record('richmedia', $richmedia);

            $zipper = get_file_packer('application/zip');
            $zipper->archive_to_storage($zipfiles, $context->id, 'mod_richmedia', 'package', '0', '/', 'slides.zip');
        }
        redirect("$CFG->wwwroot/course/modedit.php?update=" . $id);
    }
} else {
    $PAGE->navbar->add($richmedia->name);
    $PAGE->set_title(format_string($richmedia->name));
    $PAGE->set_heading($course->fullname);

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('importexport', 'richmedia'));
    echo $OUTPUT->box_start('richmediadisplay generalbox');
    // display upload form
    $data = new stdClass();
    $data->id = $id;
    $formimport->set_data($data);
    $formexport->set_data($data);
    $formimport->display();
    $formexport->display();
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
}	
