<?php

/**
 * All moodle functions
 * Author:
 * 	Adrien Jamot  (adrien_jamot [at] symetrix [dt] fr)
 * 
 * @package   mod_richmedia
 * @copyright 2011 Symetrix
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/filestorage/zip_archive.php");
require_once($CFG->dirroot . '/mod/richmedia/locallib.php');

define('RICHMEDIA_TYPE_LOCAL', 'local');

/**
 * * Ajoute une instance de RICH MEDIA
 * */
function richmedia_add_instance($richmedia, $mform = null) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/richmedia/locallib.php');
    $cmid = $richmedia->coursemodule;
    $cmidnumber = $richmedia->cmidnumber;
    $courseid = $richmedia->course;

    if (is_int($richmedia->referenceslides)) {
        $richmedia->referenceslides = '';
    }
    if (is_int($richmedia->referencesxml)) {
        $richmedia->referencesxml = '';
    }
    if (is_int($richmedia->referencesvideo)) {
        $richmedia->referencesvideo = '';
    }
    if (is_int($richmedia->referencesfond)) {
        $richmedia->referencesfond = '';
    }

    $context = context_module::instance($cmid);
    $id = $DB->insert_record('richmedia', $richmedia);

    $DB->set_field('course_modules', 'instance', $id, array('id' => $cmid));
    $record = $DB->get_record('richmedia', array('id' => $id));

    $fs = get_file_storage();
    if ($mform) {
        $filenameslides = $mform->get_new_filename('referenceslides');
        if ($filenameslides !== false) {
            $fs->delete_area_files($context->id, 'mod_richmedia', 'package');
            $mform->save_stored_file('referenceslides', $context->id, 'mod_richmedia', 'package', 0, '/', $filenameslides);
            $record->referenceslides = $filenameslides;
        }

        $filenamexml = $mform->get_new_filename('referencesxml');
        if ($filenamexml !== false) {
            $mform->save_stored_file('referencesxml', $context->id, 'mod_richmedia', 'content', 0, '/', $filenamexml);
            $record->referencesxml = $filenamexml;
        } else {
            $record->referencesxml = "settings.xml";
        }

        $filenamevideo = $mform->get_new_filename('referencesvideo');
        if ($filenamevideo !== false) {
            $mform->save_stored_file('referencesvideo', $context->id, 'mod_richmedia', 'content', 0, '/video/', $filenamevideo);
            $record->referencesvideo = $filenamevideo;
        }

        $filenamefond = $mform->get_new_filename('referencesfond');
        if ($filenamefond !== false) {
            $mform->save_stored_file('referencesfond', $context->id, 'mod_richmedia', 'picture', 0, '/', $filenamefond);
            $record->referencesfond = $filenamefond;
        }
    }

    // Enregistrement
    $DB->update_record('richmedia', $record);

    $record->course = $courseid;
    $record->cmidnumber = $cmidnumber;
    $record->cmid = $cmid;

    // Traitement du zip
    richmedia_parse($record);

    richmedia_generate_xml($record);

    $fs->delete_area_files(13, 'user', 'draft');
    return $record->id;
}

/**
 * *Mise a jour d'une instance
 * */
function richmedia_update_instance($richmedia, $mform = null) {
    global $CFG, $DB;

    require_once($CFG->dirroot . '/mod/richmedia/locallib.php');
    $cmid = $richmedia->coursemodule;
    $cmidnumber = $richmedia->cmidnumber;
    $courseid = $richmedia->course;

    if (is_int($richmedia->referenceslides)) {
        $richmedia->referenceslides = '';
    }
    if (is_int($richmedia->referencesxml)) {
        $richmedia->referencesxml = '';
    }
    if (is_int($richmedia->referencesvideo)) {
        $richmedia->referencesvideo = '';
    }
    if (is_int($richmedia->referencesfond)) {
        $richmedia->referencesfond = '';
    }

    $richmedia->id = $richmedia->instance;

    $context = context_module::instance($cmid);
    $fs = get_file_storage();
    if ($mform) {
        $filenameslides = $mform->get_new_filename('referenceslides');

        if ($filenameslides !== false) {
            $richmedia->referenceslides = $filenameslides;
            $fs->delete_area_files($context->id, 'mod_richmedia', 'package');
            $mform->save_stored_file('referenceslides', $context->id, 'mod_richmedia', 'package', 0, '/', $filenameslides);
        }

        $filenamexml = $mform->get_new_filename('referencesxml');
        if ($filenamexml !== false) {
            $richmedia->referencesxml = $filenamexml;
            $fs->delete_area_files($context->id, 'mod_richmedia', 'content');
            $mform->save_stored_file('referencesxml', $context->id, 'mod_richmedia', 'content', 0, '/', $filenamexml);
        }

        $filenamevideo = $mform->get_new_filename('referencesvideo');
        if ($filenamevideo !== false) {
            $richmedia->referencesvideo = $filenamevideo;
            $fs->delete_area_files($context->id, 'mod_richmedia', 'video');
            $mform->save_stored_file('referencesvideo', $context->id, 'mod_richmedia', 'content', 0, '/video/', $filenamevideo);
        }

        $filenamepicture = $mform->get_new_filename('referencesfond');
        if ($filenamepicture !== false) {
            $richmedia->referencesfond = $filenamepicture;
            $fs->delete_area_files($context->id, 'mod_richmedia', 'picture');
            $mform->save_stored_file('referencesfond', $context->id, 'mod_richmedia', 'picture', 0, '/', $filenamepicture);
        }
    }

    $DB->update_record('richmedia', $richmedia);

    $richmedia = $DB->get_record('richmedia', array('id' => $richmedia->id));

    /// extra fields required in grade related functions
    $richmedia->course = $courseid;
    $richmedia->idnumber = $cmidnumber;
    $richmedia->cmid = $cmid;

    richmedia_parse($richmedia);

    richmedia_generate_xml($richmedia);

    return true;
}

/**
 * *Supprime une instance de RICH MEDIA
 * */
function richmedia_delete_instance($id) {
    global $DB;

    if (!$richmedia = $DB->get_record('richmedia', array('id' => $id))) {
        return false;
    }

    $result = true;
    if (!$DB->delete_records('richmedia', array('id' => $richmedia->id))) {
        $result = false;
    }
    return $result;
}

/**
 * get the infos of a file
 * */
function richmedia_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    global $CFG;

    if (!has_capability('moodle/course:managefiles', $context)) {
        return null;
    }

    $fs = get_file_storage();

    if ($filearea === 'video') {

        $filepath = is_null($filepath) ? '/' : $filepath;
        $filename = is_null($filename) ? '.' : $filename;

        $urlbase = $CFG->wwwroot . '/pluginfile.php';
        if (!$storedfile = $fs->get_file($context->id, 'mod_richmedia', $filearea, 0, $filepath, $filename)) {
            if ($filepath === '/' and $filename === '.') {
                $storedfile = new virtual_root_file($context->id, 'mod_richmedia', $filearea, 0);
            } else {
                // not found
                return null;
            }
        }
        require_once("$CFG->dirroot/mod/richmedia/locallib.php");
        return new richmedia_package_file_info($browser, $context, $storedfile, $urlbase, $areas[$filearea], true, true, false, false);
    }
    /* else if ($filearea === 'package') {
      $filepath = is_null($filepath) ? '/' : $filepath;
      $filename = is_null($filename) ? '.' : $filename;

      $urlbase = $CFG->wwwroot . '/pluginfile.php';
      if (!$storedfile = $fs->get_file($context->id, 'mod_richmedia', 'package', 0, $filepath, $filename)) {
      if ($filepath === '/' and $filename === '.') {
      $storedfile = new virtual_root_file($context->id, 'mod_richmedia', 'package', 0);
      } else {
      // not found
      return null;
      }
      }
      return new file_info_stored($browser, $context, $storedfile, $urlbase, $areas[$filearea], false, true, false, false);
      } */
    return false;
}

function richmedia_user_outline() {
    //not implemented yet
}

function richmedia_get_view_actions() {
    //not implemented yet
}

function richmedia_get_post_actions() {
    //not implemented yet
}

/**
 * *Renvoi un fichier
 * */
function richmedia_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    global $CFG;
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }
    require_login($course, true, $cm);
    $lifetime = isset($CFG->filelifetime) ? $CFG->filelifetime : 86400;
    if ($filearea === 'content') {
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_richmedia/content/0/$relativepath";
    } else if ($filearea === 'video') {
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_richmedia/content/video/0/$relativepath";
    } else if ($filearea === 'picture') {
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_richmedia/picture/0/$relativepath";
    } else if ($filearea === 'package') {
        if (!has_capability('moodle/course:manageactivities', $context)) {
            return false;
        }
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_richmedia/package/0/$relativepath";
        $lifetime = 0;
    } else if ($filearea === 'zip') {
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_richmedia/zip/0/$relativepath";
    } else {
        return false;
    }
    $fs = get_file_storage();
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }
    // finally send the file
    send_stored_file($file, $lifetime, 0, false);
}

function richmedia_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS: return false;
        case FEATURE_GROUPINGS: return false;
        case FEATURE_GROUPMEMBERSONLY: return false;
        case FEATURE_MOD_INTRO: return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE: return false;
        case FEATURE_GRADE_OUTCOMES: return false;
        case FEATURE_BACKUP_MOODLE2: return true;
        case FEATURE_SHOW_DESCRIPTION: return false;

        default: return null;
    }
}

/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settings The settings navigation object
 * @param navigation_node $richmedianode The node to add module settings to
 */
function richmedia_extend_settings_navigation(settings_navigation $settings, navigation_node $richmedianode) {
    global $PAGE;
    $context = context_module::instance($PAGE->cm->id);
    if (has_capability('mod/richmedia:addinstance', $context)) {
        $richmedianode->add(get_string('importexport','mod_richmedia'), new moodle_url('/mod/richmedia/importexport.php', array('id' => $PAGE->cm->id)));
        $richmedianode->add(get_string('createedit','mod_richmedia'), new moodle_url('/mod/richmedia/xmleditor.php', array('update' => $PAGE->cm->id)));
    }
}

function richmedia_user_complete($course, $user, $mod, $richmedia) {
    global $DB;

    if ($logs = $DB->get_records('log', array('userid' => $user->id, 'module' => 'richmedia',
        'action' => 'view', 'info' => $richmedia->id), 'time ASC')) {
        $numviews = count($logs);
        $lastlog = array_pop($logs);

        $strmostrecently = get_string('mostrecently');
        $strnumviews = get_string('numviews', '', $numviews);

        echo "$strnumviews - $strmostrecently " . userdate($lastlog->time);
    } else {
        print_string('neverseen', 'richmedia');
    }
}

class richmedia_package_file_info extends file_info_stored {

    public function get_parent() {
        if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
            return $this->browser->get_file_info($this->context);
        }
        return parent::get_parent();
    }

    public function get_visible_name() {
        if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
            return $this->topvisiblename;
        }
        return parent::get_visible_name();
    }

}