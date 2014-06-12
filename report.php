<?php

/**
 * Print reports
 * Author:
 * 	Adrien Jamot  (adrien_jamot [at] symetrix [dt] fr)
 * 
 * @package   mod_richmedia
 * @copyright 2011 Symetrix
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */
require_once("../../config.php");
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . '/mod/richmedia/locallib.php');
require_once($CFG->libdir . '/formslib.php');
define('RICHMEDIA_REPORT_DEFAULT_PAGE_SIZE', 10);
define('RICHMEDIA_REPORT_ATTEMPTS_ALL_STUDENTS', 0);
define('RICHMEDIA_REPORT_ATTEMPTS_STUDENTS_WITH', 1);
define('RICHMEDIA_REPORT_ATTEMPTS_STUDENTS_WITH_NO', 2);

$id = optional_param('id', '', PARAM_INT);    // Course Module ID, or
$user = optional_param('user', '', PARAM_INT);  // User ID
$action = optional_param('action', '', PARAM_ALPHA);
$download = optional_param('download', '', PARAM_RAW);

if (!empty($id)) {
    if (!$cm = get_coursemodule_from_id('richmedia', $id)) {
        print_error('invalidcoursemodule');
    }
    if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('coursemisconf');
    }
    if (!$richmedia = $DB->get_record('richmedia', array('id' => $cm->instance))) {
        print_error('invalidcoursemodule');
    }
}

$url = new moodle_url('/mod/richmedia/report.php',array('id'=>$id));
$PAGE->set_url($url);

require_login($course->id, false, $cm);

$contextmodule = context_module::instance($cm->id);

require_capability('mod/richmedia:viewreport', $contextmodule);

add_to_log($course->id, 'richmedia', 'report', 'report.php?id=' . $cm->id, $richmedia->id, $cm->id);

if ($action == "delete") {

    $joined = optional_param('joined', '', PARAM_RAW);
    $richmediaid = optional_param('richmediaid', null, PARAM_INT);
    $tab = explode(',', $joined);
    foreach ($tab as $elem) {
        richmedia_delete_track($elem, $richmediaid);
    }
    exit;
}
/// Print the page header
if (empty($download)) {

    $strscorms = get_string('modulenameplural', 'richmedia');
    $strscorm = get_string('modulename', 'richmedia');
    $strreport = get_string('report', 'richmedia');
    $strattempt = get_string('attempt', 'richmedia');
    $strname = get_string('name');

    $PAGE->set_title("$course->shortname: " . format_string($richmedia->name));
    $PAGE->set_heading($course->fullname);
    $PAGE->navbar->add($strreport, new moodle_url('/mod/richmedia/report.php', array('id' => $cm->id)));
    $PAGE->requires->jquery();
    $PAGE->requires->js_init_call('M.mod_richmedia.initReport', array($cm->id, $richmedia->id));
    $PAGE->requires->string_for_js('noselectedline', 'mod_richmedia');

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string("report") . " : " . format_string($richmedia->name));
}

if ($download == 'excel') {
    require_once("$CFG->libdir/excellib.class.php");
    $filename = 'richmedia_track_' . time();
    $filename .= ".xls";
    // Creating a workbook
    $workbook = new MoodleExcelWorkbook("-");
    // Sending HTTP headers
    $workbook->send($filename);
    // Creating the first worksheet
    $sheettitle = get_string('report', 'richmedia');
    $myxls = $workbook->add_worksheet($sheettitle);
    // format types
    $format = $workbook->add_format();
    $format->set_bold(0);
    $formatbc = $workbook->add_format();
    $formatbc->set_bold(1);
    $formatbc->set_align('center');
    $formatb = $workbook->add_format();
    $formatb->set_bold(1);
    $formaty = $workbook->add_format();
    $formaty->set_bg_color('yellow');
    $formatc = $workbook->add_format();
    $formatc->set_align('center');
    $formatr = $workbook->add_format();
    $formatr->set_bold(1);
    $formatr->set_color('red');
    $formatr->set_align('center');
    $formatg = $workbook->add_format();
    $formatg->set_bold(1);
    $formatg->set_color('green');
    $formatg->set_align('center');

    $headers = array();
    $headers[] = get_string('name');
    $headers[] = get_string('attempts', 'richmedia');
    $headers[] = get_string('started', 'richmedia');
    $headers[] = get_string('last', 'richmedia');
    $colnum = 0;
    foreach ($headers as $item) {
        $myxls->write(0, $colnum, $item, $formatbc);
        $colnum++;
    }

    $rownum = 1;
    $tracks = $DB->get_records('richmedia_track', array('richmediaid' => $richmedia->id));
    foreach ($tracks as $track) {
        $colnum = 0;
        $row = array();
        $user = $DB->get_record('user', array('id' => $track->userid));
        $rowname = $user->firstname . ' ' . $user->lastname;
        $rowattempt = $track->attempt;
        $rowstart = userdate($track->start, get_string("strftimedatetime", "langconfig"));
        $rowlast = userdate($track->last, get_string("strftimedatetime", "langconfig"));

        $myxls->write($rownum, $colnum, $rowname, $format);
        $colnum++;
        $myxls->write($rownum, $colnum, $rowattempt, $format);
        $colnum++;
        $myxls->write($rownum, $colnum, $rowstart, $format);
        $colnum++;
        $myxls->write($rownum, $colnum, $rowlast, $format);
        $colnum++;
        $rownum++;
    }
    $workbook->close();
    exit;
}

if (empty($download)) {

    $table = new flexible_table('mod-richmedia-report');

    $columns = array("checkbox", "picture", "fullname", "attempt", "start", "last");
    $headers = array(" ", " ", get_string('name'), get_string('attempts', 'richmedia'), get_string('started', 'richmedia'), get_string('last', 'richmedia'));
    $displayoptions = array();
    $displayoptions['id'] = $cm->id;
    $reporturlwithdisplayoptions = new moodle_url($CFG->wwwroot . '/mod/richmedia/report.php', $displayoptions);

    $table->define_columns($columns);
    $table->define_headers($headers);
    $table->define_baseurl($reporturlwithdisplayoptions->out());

    $table->sortable(true);
    $table->collapsible(true);

    $table->column_suppress('picture');
    $table->column_suppress('fullname');

    $table->no_sorting('checkbox');
    $table->no_sorting('picture');

    $table->column_class('picture', 'picture');
    $table->column_class('fullname', 'bold');

    $table->set_attribute('cellspacing', '0');
    $table->set_attribute('class', 'generaltable generalbox');

    $table->setup();

    $sqlcount = 'SELECT COUNT(DISTINCT id) AS total FROM {richmedia_track} WHERE richmediaid = ' . $richmedia->id;
    $count = $DB->get_record_sql($sqlcount);
    $total = $count->total;
    $table->pagesize(RICHMEDIA_REPORT_DEFAULT_PAGE_SIZE, $total);


    if (!$download) {
        $sort = $table->get_sql_sort();
    } else {
        $sort = '';
    }
    if (empty($sort)) {
        $sort = ' ORDER BY id';
    } else {
        $sort = ' ORDER BY ' . $sort;
    }

    // Start working -- this is necessary as soon as the niceties are over
    $sql = 'SELECT u.id,userid,picture,imagealt,email,firstname,lastname,attempt,start,last FROM {richmedia_track} as r,{user} as u WHERE r.userid=u.id AND richmediaid = ' . $richmedia->id . $sort;

    $params = array();
    list($twhere, $tparams) = $table->get_sql_where();
    if ($twhere) {
        $params = array_merge($params, $tparams);
    }

    $tracks = $DB->get_records_sql($sql, $params, $table->get_page_start(), $table->get_page_size());
    $total = count($tracks);
    if ($total <= 1) {
        $user = get_string('user', 'richmedia');
    } else {
        $user = get_string('users', 'richmedia');
    }
    echo '<div style="text-align : center;">' . $total . ' ' . $user . '</div>';
    foreach ($tracks as $track) {
        $row = array();
        $row[] = '<input type="checkbox" id="' . $track->userid . '" />';
        $user = (object) array('id' => $track->userid,
                    'picture' => $track->picture,
                    'imagealt' => $track->imagealt,
                    'email' => $track->email,
                    'firstname' => $track->firstname,
                    'lastname' => $track->lastname);
        $row[] = $OUTPUT->user_picture($user, array('courseid' => $course->id));
        $row[] = '<a href="' . $CFG->wwwroot . '/user/view.php?id=' . $track->userid . '&course=' . $course->id . '">' . $track->firstname . ' ' . $track->lastname . '</a>';
        $row[] = $track->attempt;
        $row[] = userdate($track->start, get_string("strftimedatetime", "langconfig"));
        $row[] = userdate($track->last, get_string("strftimedatetime", "langconfig"));
        $table->add_data($row);
    }
    echo '<a href="' . $CFG->wwwroot . '/mod/richmedia/report.php?id=' . $id . '&download=excel">' . get_string('downloadexcel', 'richmedia') . '</a><br /><br />';


    $table->finish_output();
    echo '<input type="button" id="checkall" value="' . get_string('selectall', 'richmedia') . '" />';
    echo '/';
    echo '<input type="button" id="uncheckall" value="' . get_string('deselectall', 'richmedia') . '" />';
    echo '<input type="button" id="deleterows" value="' . get_string('deleteselection', 'richmedia') . '" />';

    echo $OUTPUT->footer();
}
    

