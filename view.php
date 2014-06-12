<?php
/**
 * Display the Rich Media
 * Author:
 * 	Adrien Jamot  (adrien_jamot [at] symetrix [dt] fr)
 * 
 * @package   mod_richmedia
 * @copyright 2011 Symetrix
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */
require_once("../../config.php");
require_once($CFG->dirroot . '/mod/richmedia/locallib.php');

$id = optional_param('id', '', PARAM_INT);       // Course Module ID

if (!empty($id)) {
    if (!$cm = get_coursemodule_from_id('richmedia', $id)) {
        print_error('invalidcoursemodule');
    }
    if (!$course = $DB->get_record("course", array("id" => $cm->course))) {
        print_error('coursemisconf');
    }
    if (!$richmedia = $DB->get_record("richmedia", array("id" => $cm->instance))) {
        print_error('invalidcoursemodule');
    }
} else {
    print_error('missingparameter');
}

$url = new moodle_url('/mod/richmedia/view.php', array('id' => $cm->id));

$PAGE->set_url($url);
$PAGE->requires->jquery();
$PAGE->requires->js_init_call('M.mod_richmedia.init', array($richmedia->id));
require_login($course->id, false, $cm);

$context = context_course::instance($course->id);

$pagetitle = strip_tags($course->shortname . ': ' . format_string($richmedia->name));

add_to_log($course->id, 'richmedia', 'view', 'view.php?id=' . $cm->id, $richmedia->id, $cm->id);
require_once($CFG->libdir . '/completionlib.php');
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$videoExtArray = explode('.', $richmedia->referencesvideo);
$extension = end($videoExtArray);

$richmediainfos = array();
if ($richmedia->html5 && $extension != 'flv' && $extension != 'swf') {
    $richmediainfos = richmedia_get_html5_infos($richmedia);
    $audioMode = 0;
    if (richmedia_is_audio($richmedia)) {
        $audioMode = 1;
    }

    $PAGE->requires->css('/mod/richmedia/playerhtml5/css/playerhtml5.css');
    if (file_exists($CFG->dirroot . '/mod/richmedia/themes/' . $richmedia->theme . '/styles.css')) {
        $PAGE->requires->css('/mod/richmedia/themes/' . $richmedia->theme . '/styles.css');
    }
    $PAGE->requires->jquery();
    $PAGE->requires->jquery_plugin('ui');
    $PAGE->requires->jquery_plugin('ui-css');
    $PAGE->requires->js('/mod/richmedia/playerhtml5/js/jquery.punch.js');
    $PAGE->requires->js('/mod/richmedia/playerhtml5/js/cuepoint.js');
    $PAGE->requires->js('/mod/richmedia/playerhtml5/js/player.js');
    $PAGE->requires->strings_for_js(array('summary', 'close'), 'mod_richmedia');
    $PAGE->requires->js_init_call('M.mod_richmedia.initPlayerHTML5', array($richmediainfos, $audioMode));

    // QUIZ
    if (!empty($richmedia->quizid)) {
        require_once $CFG->dirroot . '/mod/symquiz/quiz.php';
        $preview = false;
        if (!$quizActivity = $DB->get_record('symquiz', array('id' => $richmedia->quizid))) {
            print_error('course module is incorrect');
        }
        $cmQuiz = get_coursemodule_from_instance('symquiz', $richmedia->quizid);
        $quiz = symquiz\quiz\Quiz::getQuiz($quizActivity, $cmQuiz->id);
        $richmediaQuiz = symquiz\quiz\Quiz::newQuiz($quizActivity, $cmQuiz);
        foreach ($richmediainfos->tabslides as $slide) {
            if (!empty($slide['question'])) {
                $question = $quiz->getQuestionById($slide['question']);
                $richmediaQuiz->addQuestion($question, false);
            }
        }

        $xmlFile = $CFG->wwwroot . '/mod/richmedia/quiz.php?id=' . $richmedia->id;
        $langFile = 'teststring.php?id=' . $cmQuiz->id;

        $plugins = $richmediaQuiz->getRequiredPlugins();

        $PAGE->requires->jquery();
        $PAGE->requires->jquery_plugin('ui');
        $PAGE->requires->jquery_plugin('ui-css');
        $PAGE->requires->css('/mod/symquiz/player/lib/styles.css');
        $PAGE->requires->css('/mod/symquiz/css/embed.css');
        foreach ($plugins as $plugin) {
            $PAGE->requires->css('/mod/symquiz/' . $plugin->getType() . '/' . $plugin->getTypeName() . '/player/styles.css');
        }
    }
    // END QUIZ
}

richmedia_add_track($USER, $richmedia);
//
// Print the page header
//	
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);

$renderer = $PAGE->get_renderer('mod_richmedia');

echo $OUTPUT->header();

if (has_capability('mod/richmedia:viewreport', $context)) {
    echo '<div style="float:right;margin-top:10px;">';
    echo '<a href="report.php?id=' . $id . '">' . get_string('showresults', 'richmedia') . '</a>';
    echo '</div>';
}

// Print the main part of the page
echo $OUTPUT->heading(format_string($richmedia->name));

echo $renderer->intro($richmedia);
if ($richmedia->html5 && $extension != 'flv' && $extension != 'swf') {
    require_once($CFG->dirroot . '/mod/richmedia/playerhtml5/playerhtml5_template.php');
} else {
    echo $renderer->richmedia_display($richmedia);
}

// QUIZ

if (!empty($richmedia->quizid) && !empty($richmediainfos)) {
    $jsFiles = array(
        $CFG->wwwroot . '/mod/symquiz/player/lib/jquery.color.min.js',
        $CFG->wwwroot . '/mod/symquiz/player/lib/jquery.mousewheel.js',
        $CFG->wwwroot . '/mod/symquiz/teststring.php?id=' . $cmQuiz->id,
        $CFG->wwwroot . '/mod/symquiz/player/lib/class.js',
        $CFG->wwwroot . '/mod/symquiz/player/lib/timer.js',
        $CFG->wwwroot . '/mod/symquiz/player/lib/player.js',
        $CFG->wwwroot . '/mod/symquiz/player/lib/collection.js'
    );

    foreach ($plugins as $plugin) {
        if ($plugin->fileExists($CFG->dirroot . '/mod/symquiz/player/lib')) {
            foreach ($plugin->getDirFiles($CFG->dirroot . '/mod/symquiz/player/lib', false) as $file) {
                if (preg_match('/\.js$/', $file)) {
                    $jsFiles[] = $plugin->getType() . '/' . $plugin->getTypeName() . '/' . $file;
                }
            }
        }
    }

    $jsFiles[] = $CFG->wwwroot . '/mod/symquiz/player/lib/quiz.js';
    $jsFiles[] = $CFG->wwwroot . '/mod/symquiz/player/lib/question.js';
    $jsFiles[] = $CFG->wwwroot . '/mod/symquiz/player/lib/feedbackquestion.js';

    foreach ($plugins as $plugin) {
        $jsFiles[] = $CFG->wwwroot . '/mod/symquiz/' . $plugin->getType() . '/' . $plugin->getTypeName() . '/player/' . $plugin->getTypeName() . '.js';
    }
    ?>
    <script>var xmlFile = <?php echo json_encode($xmlFile); ?>;</script>
    <?php
    foreach ($jsFiles as $jsFile) {
        echo '<script src=', $jsFile, '></script>';
    }

    echo '<div class="symquiz_content">';
    include $CFG->dirroot . '/mod/symquiz/player/template.php';
    echo '</div>';
}
// END QUIZ

echo $OUTPUT->footer();


