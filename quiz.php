<?php

/**
 * Get symquiz xml
 * Author:
 * 	Adrien Jamot  (adrien_jamot [at] symetrix [dt] fr)
 * 
 * @package   mod_richmedia
 * @copyright 2011 Symetrix
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

header("Content-Type:text/xml");
require_once("../../config.php");
require_once $CFG->dirroot . '/mod/symquiz/quiz.php';
require_once $CFG->dirroot . '/mod/richmedia/lib.php';

$id = required_param('id', PARAM_INT);

if (!$richmedia = $DB->get_record('richmedia', array('id' => $id))) {
    print_error('incorrect richmedia id');
}

$richmediainfos = richmedia_get_html5_infos($richmedia);

if (!$quizActivity = $DB->get_record('symquiz', array('id' => $richmedia->quizid))) {
    print_error('course module is incorrect');
}
$cmQuiz = get_coursemodule_from_instance('symquiz', $richmedia->quizid);
$quiz = symquiz\quiz\Quiz::getQuiz($quizActivity, $cmQuiz->id);
$richmediaQuiz = symquiz\quiz\Quiz::newQuiz($quizActivity, $cmQuiz->id);
foreach ($richmediainfos->tabslides as $slide) {
    if (!empty($slide['question'])) {
        $question = $quiz->getQuestionById($slide['question']);
        $richmediaQuiz->addQuestion($question, false);
    }
}
echo $richmediaQuiz->getXml();
