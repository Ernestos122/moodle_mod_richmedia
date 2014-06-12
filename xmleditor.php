<?php

/**
 * Edit the synchronization file
 * Author:
 * 	Adrien Jamot  (adrien_jamot [at] symetrix [dt] fr)
 * 
 * @package   mod_richmedia
 * @copyright 2011 Symetrix
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */
require_once("../../config.php");
require_once("lib.php");

$update = required_param('update', PARAM_INT);

$context = context_module::instance($update);

$url = new moodle_url('/mod/richmedia/xmleditor.php', array('update' => $update));

$PAGE->set_url('/mod/richmedia/xmleditor.php');
if (!$module = get_coursemodule_from_id('richmedia', $update)) {
    print_error('invalidcoursemodule');
}

if (!$course = $DB->get_record("course", array("id" => $module->course))) {
    print_error('coursemisconf');
}

if (!$courserichmedia = $DB->get_record("richmedia", array("id" => $module->instance))) {
    print_error('invalidid', 'richmedia');
}

require_login($course->id, true, $module);

require_capability('moodle/course:manageactivities', $context);

$streditxml = get_string('editxml', 'richmedia');

$PAGE->requires->jquery();
$PAGE->requires->js('/mod/richmedia/lib/adapter/ext/ext-base.js');
$PAGE->requires->css('/mod/richmedia/lib/resources/css/ext-all.css');
$PAGE->requires->js('/mod/richmedia/lib/ext-all.js');
$PAGE->requires->js('/mod/richmedia/xmleditor.js');

$PAGE->set_title(format_string($courserichmedia->name));
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($course->shortname, $CFG->wwwroot . "/course/view.php?id=" . $course->id);
$PAGE->navbar->add($courserichmedia->name, $CFG->wwwroot . "/mod/richmedia/view.php?id=" . $update);
$PAGE->navbar->add($streditxml);

$movie = $courserichmedia->referencesvideo;

$fs = get_file_storage();

// Prepare video record object
$fileinfovideo = new stdClass();
$fileinfovideo->component = 'mod_richmedia';
$fileinfovideo->filearea = 'content';
$fileinfovideo->contextid = $context->id;
$fileinfovideo->filepath = '/video/';
$fileinfovideo->itemid = 0;
$fileinfovideo->filename = $movie;
// Get file
$filevideo = $fs->get_file($fileinfovideo->contextid, $fileinfovideo->component, $fileinfovideo->filearea, $fileinfovideo->itemid, $fileinfovideo->filepath, $fileinfovideo->filename);
if ($filevideo) {
    $url = "{$CFG->wwwroot}/pluginfile.php/{$filevideo->get_contextid()}/mod_richmedia/content/";
    $filevideoname = $filevideo->get_filename();
    $filevideopath = $filevideo->get_filepath();
    $fileurl = $url . $filevideopath . $filevideoname;
} else {
    $fileurl = '';
}

// Prepare file record object
$fileinfo = new stdClass();
$fileinfo->component = 'mod_richmedia';
$fileinfo->filearea = 'content';
$fileinfo->contextid = $context->id;
$fileinfo->filepath = '/';
$fileinfo->itemid = 0;
$fileinfo->filename = $courserichmedia->referencesxml;
// Get file
$file = $fs->get_file($fileinfo->contextid, $fileinfo->component, $fileinfo->filearea, $fileinfo->itemid, $fileinfo->filepath, $fileinfo->filename);

$urlslide = "{$CFG->wwwroot}/pluginfile.php/{$context->id}/mod_richmedia/content/slides/";
// Read contents
if ($file) {
    $contenuxml = $file->get_content();
    $contenuxml = str_replace('&', '&amp;', $contenuxml);

    $xml = simplexml_load_string($contenuxml);

    foreach ($xml->titles[0]->title[0]->attributes() as $attribute => $value) {
        if ($attribute == 'label') {
            $title = $courserichmedia->name;
            if (!$title) {
                $value = str_replace("&rsquo;", iconv("CP1252", "UTF-8", "’"), $value);
                $value = str_replace("â€™", "’", $value);
                $value = str_replace("’", "'", $value);
                $title = $value;
            }
            break;
        }
    }
    $presentertitle = '';
    foreach ($xml->presenter[0]->attributes() as $attribute => $value) {
        if ($attribute == 'name') {
            $value = $courserichmedia->presentor;
            $value = str_replace("&rsquo;", iconv("CP1252", "UTF-8", "’"), $value);
            $value = str_replace("â€™", "’", $value);
            $value = str_replace("’", "'", $value);

            if (!$value) {
                $value = $USER->firstname . ' ' . $USER->lastname;
            }
            $presentername = $value;
        } else if ($attribute == 'title') {
            $value = str_replace("&rsquo;", iconv("CP1252", "UTF-8", "’"), $value);
            $value = str_replace("â€™", "’", $value);
            $value = str_replace("’", "'", $value);
            $presentertitle = $value;
        }
    }
    $defaultview = $courserichmedia->defaultview;
    $autoplay = $courserichmedia->autoplay;
    foreach ($xml->options[0]->attributes() as $attribute => $value) {
        if ($attribute == 'defaultview' && $value != '') {
            $defaultview = $value;
        }
    }

    foreach ($xml->design[0]->attributes() as $attribute => $value) {
        if ($attribute == 'fontcolor') {
            $fontcolor = $courserichmedia->fontcolor;
            if (!$fontcolor) {
                $fontcolor = substr($value, 2);
            } else if ($fontcolor[0] == '#') {
                $fontcolor = substr($fontcolor, 1);
            }
        }
        if ($attribute == 'font') {
            $font = $courserichmedia->font;
            if (!$font) {
                $font = $value;
            }
        }
    }
    $tabstep = array();

    $i = 0;

    foreach ($xml->steps[0]->children() as $childname => $childnode) {
        foreach ($childnode->attributes() as $attribute => $value) {
            if ($attribute == 'framein') {
                $tabstep[$i][$attribute] = richmedia_convert_time($value); // convert time
            } else if ($attribute == 'slide') {
                // Prepare video record object
                $fileinfoslide = new stdClass();
                $fileinfoslide->component = 'mod_richmedia';
                $fileinfoslide->filearea = 'content';
                $fileinfoslide->contextid = $context->id;
                $fileinfoslide->filepath = '/slides/';
                $fileinfoslide->itemid = 0;
                $fileinfoslide->filename = (String) $value;
                // Get file
                $fileslide = $fs->get_file($fileinfoslide->contextid, $fileinfoslide->component, $fileinfoslide->filearea, $fileinfoslide->itemid, $fileinfoslide->filepath, $fileinfoslide->filename);

                if ($fileslide) {
                    $fileslidename = $fileslide->get_filename();
                    $fileurlslide = $urlslide . $fileslidename;
                    $tabstep[$i]['url'] = $fileurlslide;
                } else {
                    $tabstep[$i]['url'] = '';
                }
                $tabstep[$i][$attribute] = (String) $value;
            } else {
                $tabstep[$i][$attribute] = (String) $value;
            }
        }
        $i++;
    }
} else {
    // file doesn't exist - do something
    $contenuxml = '';
    $title = get_string('title', 'richmedia');
    $presentername = '';
    $presentertitle = '';
    $fontcolor = 'FFFFFF';
    $font = 'Arial';
    $tabstep = array();
    $tabstep[0]['id'] = 0;
    $tabstep[0]['label'] = get_string('slidetitle', 'richmedia');
    $tabstep[0]['comment'] = '';
    $tabstep[0]['framein'] = '00:00';
    $tabstep[0]['slide'] = 'Diapositive1.JPG';
    $tabstep[0]['question'] = '';
    $defaultview = 1;
    $autoplay = 1;
}

//AVAILABLE FILES
$files = $fs->get_area_files($context->id, 'mod_richmedia', 'content', 0);
$available = array();
foreach ($files as $f) {
    $filename = $f->get_filename();
    $filenameextension = explode('.', $filename);
    if ($filename != '.' &&
            $filename != $courserichmedia->referencesxml &&
            $filename != $movie &&
            $filename != 'Thumbs.db' &&
            (end($filenameextension) != 'xml')
    ) {
        $available[] = $filename;
    }
}

$questions = array();
if (!empty($courserichmedia->quizid)) {
    $quizActivity = $DB->get_record('symquiz', array('id' => $courserichmedia->quizid));
    require_once $CFG->dirroot . '/mod/symquiz/quiz.php';
    $cmQuiz = get_coursemodule_from_instance('symquiz', $courserichmedia->quizid);
    $quiz = symquiz\quiz\Quiz::getQuiz($quizActivity, $cmQuiz->id);
    $quizQuestions = $quiz->getQuestions();
    foreach ($quizQuestions as $quizQuestion) {
        $questions[$quizQuestion->getId()] = strip_tags($quizQuestion->getText());
    }
}

$urlsubmit = $CFG->wwwroot . '/mod/richmedia/xmleditor_save.php';
$urlLocation = $CFG->wwwroot . '/course/modedit.php?update=' . $update;
$urlView = $CFG->wwwroot . '/mod/richmedia/view.php?id=' . $update;
$defaultview = (string) $defaultview;

$PAGE->requires->js_init_call(
        'M.mod_richmedia_xmleditor.init', array(
    $available,
    $tabstep,
    $fileurl,
    $movie,
    $title,
    $presentername,
    $presentertitle,
    $context->id,
    $update,
    $fontcolor,
    $font,
    $urlslide,
    $defaultview,
    $autoplay,
    $urlsubmit,
    $urlLocation,
    $urlView,
    $questions
        )
);

$PAGE->requires->strings_for_js(array(
    'down',
    'wait',
    'currentsave',
    'saveandreturn',
    'view',
    'savedone',
    'information',
    'test',
    'filenotavailable',
    'up',
    'delete',
    'addline',
    'cancel',
    'slidetitle',
    'save',
    'title',
    'video',
    'slidecomment',
    'presentation',
    'tile',
    'actions',
    'newline',
    'confirmdeleteline',
    'warning',
    'gettime',
    'slide',
    'delete',
    'up',
    'defaultview'
        ), 'mod_richmedia');

echo $OUTPUT->header();
echo $OUTPUT->heading($streditxml);

echo html_writer::tag('div', '', array('id' => 'tab', 'style' => 'margin:auto;'));
echo $OUTPUT->footer();

