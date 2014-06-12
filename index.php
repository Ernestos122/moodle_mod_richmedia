<?php

/**
 * Display all instances
 * Author:
 * 	Adrien Jamot  (adrien_jamot [at] symetrix [dt] fr)
 * 
 * @package   mod_richmedia
 * @copyright 2011 Symetrix
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */
require_once("../../config.php");
require_once($CFG->dirroot . '/mod/richmedia/locallib.php');

$id = required_param('id', PARAM_INT);   // course id

$PAGE->set_url('/mod/richmedia/index.php', array('id' => $id));

if (!empty($id)) {
    if (!$course = $DB->get_record('course', array('id' => $id))) {
        print_error('invalidcourseid');
    }
} else {
    print_error('missingparameter');
}

require_course_login($course);
$PAGE->set_pagelayout('incourse');

add_to_log($course->id, "richmedia", "view all", "index.php?id=$course->id", "");

$strrichmedias = get_string("modulenameplural", "richmedia");
$strsectionname = get_string('sectionname', 'format_' . $course->format);
$strname = get_string("name");
$strsummary = get_string("summary");
$strreport = get_string("report", 'richmedia');
$strlastmodified = get_string("lastmodified");

$PAGE->set_title($strrichmedias);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strrichmedias);
echo $OUTPUT->header();

$usesections = course_format_uses_sections($course->format);
if ($usesections) {
    $sections = get_all_sections($course->id);
}

if ($usesections) {
    $sortorder = "cw.section ASC";
} else {
    $sortorder = "m.timemodified DESC";
}

if (!$richmedias = get_all_instances_in_course("richmedia", $course)) {
    notice(get_string('thereareno', 'moodle', $strrichmedias), "../../course/view.php?id=$course->id");
    exit;
}

$table = new html_table();

if ($usesections) {
    $table->head = array($strsectionname, $strname, $strsummary, $strreport);
    $table->align = array("center", "left", "left", "left");
} else {
    $table->head = array($strlastmodified, $strname, $strsummary, $strreport);
    $table->align = array("left", "left", "left", "left");
}

foreach ($richmedias as $richmedia) {
    $tt = "";
    if ($usesections) {
        if ($richmedia->section) {
            $tt = get_section_name($course, $sections[$richmedia->section]);
        }
    } else {
        $tt = userdate($richmedia->timemodified);
    }
    $reportshow = '&nbsp;';

    if (!$richmedia->visible) {
        //Show dimmed if the mod is hidden
        $table->data[] = array($tt, "<a class=\"dimmed\" href=\"view.php?id=$richmedia->coursemodule\">" . format_string($richmedia->name) . "</a>",
            format_module_intro('richmedia', $richmedia, $richmedia->coursemodule), $reportshow);
    } else {
        //Show normal if the mod is visible
        $table->data[] = array($tt, "<a href=\"view.php?id=$richmedia->coursemodule\">" . format_string($richmedia->name) . "</a>",
            format_module_intro('richmedia', $richmedia, $richmedia->coursemodule), $reportshow);
    }
}

echo "<br />";

echo html_writer::table($table);

echo $OUTPUT->footer();
