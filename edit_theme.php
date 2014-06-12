<?php

/**
 * Theme manager
 * Author:
 * 	Adrien Jamot  (adrien_jamot [at] symetrix [dt] fr)
 * 
 * @package   mod_richmedia
 * @copyright 2011 Symetrix
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */
require_once("../../config.php");
$courseid = required_param('course', PARAM_INT);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

$context = context_course::instance($course->id);
require_capability('moodle/course:manageactivities', $context);
$url = new moodle_url('/mod/richmedia/edit_theme.php');
$PAGE->set_url($url);
require_login();

$strapropos = get_string('themeedition', 'richmedia');
//barre de navigation
$PAGE->set_context($context);
$PAGE->navbar->add($strapropos);
$PAGE->set_title(format_string($strapropos));
$PAGE->set_heading($course->fullname);

$PAGE->requires->css('/mod/richmedia/lib/resources/css/ext-all.css');
$PAGE->requires->js('/mod/richmedia/lib/adapter/ext/ext-base.js');
$PAGE->requires->js('/mod/richmedia/lib/ext-all.js');
$PAGE->requires->js_init_call('M.mod_richmedia.initThemeManager');
$PAGE->requires->strings_for_js(array(
    'name',
    'logo',
    'fond',
    'return',
    'addtheme',
    'actions',
    'error',
    'cancel',
    'importdone',
    'wait',
    'success',
    'currentsave',
    'themeimport',
    'deletedtheme',
    'information',
    'removetheme',
    'warning',
    'noselectedline',
    'themeedition'
        ), 'mod_richmedia');

//headers utf8
echo $OUTPUT->header();
//titre
echo $OUTPUT->heading($strapropos);

echo '<div id="tab"></div>';

echo $OUTPUT->footer();


