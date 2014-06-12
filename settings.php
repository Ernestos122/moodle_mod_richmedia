<?php
/**
 * Settings of the plugin
 * Author:
 * 	Adrien Jamot  (adrien_jamot [at] symetrix [dt] fr)
 * 
 * @package   mod_richmedia
 * @copyright 2011 Symetrix
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once($CFG->dirroot . '/mod/richmedia/locallib.php');

    $settings->add(new admin_setting_configtext('richmedia/width', get_string('width','richmedia'), get_string('defaultwidth','richmedia'), 700));

    $settings->add(new admin_setting_configtext('richmedia/height', get_string('height','richmedia'), get_string('defaultheight','richmedia'), 451));
	
    $settings->add(new admin_setting_configselect('richmedia/font', get_string('police', 'richmedia'), get_string('defaultfont', 'richmedia'), 'Arial', array("Arial" => "Arial","Courier new"=>"Courier new","Georgia"=>"Georgia","Times New Roman"=>"Times New Roman","Verdana"=>"Verdana")));
	
    $settings->add(new admin_setting_configtext('richmedia/fontcolor', get_string('fontcolor','richmedia'), get_string('defaultfontcolor','richmedia'), '#000000'));
	
	$settings->add(new admin_setting_configselect('richmedia/autoplay', 'Lecture automatique', 'Lecture automatique par defaut', 0, array(0 => 'Non',1 => 'Oui')));
	
	$settings->add(new admin_setting_configselect('richmedia/defaultview', get_string('defaultview', 'richmedia'),  get_string('defaultdefaultview', 'richmedia'), 0, array(1 => get_string('tile','richmedia'),2 => get_string('presentation','richmedia'),3 => get_string('video','richmedia'))));

}
