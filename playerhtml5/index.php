<?php
/**
 * Print the Rich Media player in HTML5
 * Author:
 * 	Adrien Jamot  (adrien_jamot [at] symetrix [dt] fr)
 * 
 * @package   mod_richmedia
 * @copyright 2011 Symetrix
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */
require_once("../../../config.php");
require_once("../lib.php");

$richmediaid = required_param('richmedia', PARAM_INT);

if ($richmedia = $DB->get_record('richmedia', array('id' => $richmediaid))) {
    $richmediainfos = richmedia_get_html5_infos($richmedia);
    $audioMode = 0;
    if (richmedia_is_audio($richmedia)) {
        $audioMode = 1;
    }
    $course = $DB->get_record('course',array('id'=>$richmedia->course));
} else {
    print_error('Richmedia inexistant');
}

$cm = get_coursemodule_from_instance('richmedia', $richmediaid);
require_login($course, true, $cm);

$url = new moodle_url('/mod/richmedia/playerhtml5/index.php', array('richmedia' => $richmediaid));

$context = context_module::instance($cm->id);
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title($richmediainfos->title);
$PAGE->set_heading($course->fullname);

$PAGE->requires->css('/mod/richmedia/lib/resources/css/ext-all.css');
$PAGE->requires->css('/mod/richmedia/playerhtml5/css/playerhtml5.css');

if (file_exists($CFG->dirroot . '/mod/richmedia/themes/'.$richmedia->theme.'/styles.css')){
    $PAGE->requires->css('/mod/richmedia/themes/'.$richmedia->theme.'/styles.css');
}
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');
$PAGE->requires->js('/mod/richmedia/playerhtml5/js/jquery.punch.js');
$PAGE->requires->js('/mod/richmedia/playerhtml5/js/cuepoint.js');
$PAGE->requires->js('/mod/richmedia/playerhtml5/js/player.js');
$PAGE->requires->strings_for_js(array('summary','close'), 'mod_richmedia');
$PAGE->requires->js_init_call('M.mod_richmedia.initPlayerHTML5', array($richmediainfos, $audioMode));
$PAGE->navbar->add('Player HTML5', $url);

require_once($CFG->libdir . '/completionlib.php');
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

echo $OUTPUT->header();

echo $OUTPUT->heading(format_string($richmedia->name));

require_once($CFG->dirroot . '/mod/richmedia/playerhtml5/playerhtml5_template.php');

echo $OUTPUT->footer();
