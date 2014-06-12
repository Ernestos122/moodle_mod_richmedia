<?php

/**
 * Save the settings into the settings.xml file
 * Author:
 * 	Adrien Jamot  (adrien_jamot [at] symetrix [dt] fr)
 * 
 * @package   mod_richmedia
 * @copyright 2011 Symetrix
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */
require_once("../../config.php");

$video = optional_param('movie', null, PARAM_RAW);
$presentertitle = optional_param('presentertitle', null, PARAM_RAW);
$presentername = optional_param('presentername', null, PARAM_RAW);
$tabsteps = optional_param('steps', null, PARAM_RAW);
$contextid = optional_param('contextid', null, PARAM_RAW);
$update = optional_param('update', null, PARAM_RAW);
$color = optional_param('fontcolor', null, PARAM_RAW);
$font = optional_param('font', null, PARAM_RAW);
$defaultview = optional_param('defaultview', null, PARAM_RAW);
$autoplay = optional_param('autoplay', null, PARAM_RAW);
$title = optional_param('title', null, PARAM_RAW);

if (!empty($video) && !empty($presentername) && !empty($tabsteps) && !empty($title) && !empty($contextid)) {
    $module = $DB->get_record('course_modules', array('id' => $update));
    $courserichmedia = $DB->get_record('richmedia', array('id' => $module->instance));

    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><settings></settings>');
    $movie = $xml->addChild('movie');
    $movie->addAttribute('src', 'video/' . $video);

    $design = $xml->addChild('design');
    $design->addAttribute('logo', 'logo.jpg');
    $design->addAttribute('font', $font);
    $design->addAttribute('background', 'background.jpg');
    $design->addAttribute('fontcolor', '0x' . $color);

    $options = $xml->addChild('options');
    $options->addAttribute('presenter', '1');
    $options->addAttribute('comment', '0');
    $options->addAttribute('defaultview', $defaultview);
    $options->addAttribute('btnfullscreen', 'true');
    $options->addAttribute('btninverse', 'false');
    if (!$autoplay || $autoplay == 0) {
        $autoplayxml = "false";
    } else {
        $autoplayxml = "true";
    }
    $options->addAttribute('autoplay', $autoplayxml);

    $presenter = $xml->addChild('presenter');
    $presenter->addAttribute('name', html_entity_decode($presentername));
    $presenter->addAttribute('biography', strip_tags(html_entity_decode($courserichmedia->intro)));
    $presenter->addAttribute('title', html_entity_decode($presentertitle));

    $titles = $xml->addChild('titles');
    $title1 = $titles->addChild('title');
    $title1->addAttribute('target', 'fdPresentationTitle');
    $title1->addAttribute('label', html_entity_decode($title));
    $title2 = $titles->addChild('title');
    $title2->addAttribute('target', 'fdMovieTitle');
    $title2->addAttribute('label', '');
    $title3 = $titles->addChild('title');
    $title3->addAttribute('target', 'fdSlideTitle');
    $title3->addAttribute('label', '');

    $steps = $xml->addChild('steps');
    //traitement des steps
    $tabsteps = substr($tabsteps, 1); // on enleve le 1er caractere
    $tabsteps = substr($tabsteps, 0, -1); // on enleve le dernier caractere
    $tabsteps = str_replace('\"', '', $tabsteps);
    $tabsteps = str_replace(',[', '', $tabsteps);
    $tabsteps = str_replace('[', '', $tabsteps);
    $tabsteps = explode("]", $tabsteps);

    $attrNames = array(
        'id',
        'label',
        'framein',
        'slide',
        'question',
        'view'
    );
    for ($i = 0; $i < count($tabsteps) - 1; $i++) {
        $step = $steps->addChild('step');
        $attributes = explode(',', $tabsteps[$i]);
        $j = 0;

        foreach ($attributes as $attribute) {
            $attribute = str_replace('"', '', $attribute);
            if ($attrNames[$j] == 'framein') {
                $tabframein = explode(':', $attribute);
                $attribute = 60 * $tabframein[0] + $tabframein[1];
            }
            $step->addAttribute($attrNames[$j], $attribute);
            $j++;
        }
    }
    $fs = get_file_storage();

    // Prepare file record object
    $fileinfo = new stdClass();
    $fileinfo->component = 'mod_richmedia';
    $fileinfo->filearea = 'content';
    $fileinfo->contextid = $contextid;
    $fileinfo->filepath = '/';
    $fileinfo->itemid = 0;
    $fileinfo->filename = 'settings.xml';
    // Get file
    $file = $fs->get_file($fileinfo->contextid, $fileinfo->component, $fileinfo->filearea, $fileinfo->itemid, $fileinfo->filepath, $fileinfo->filename);
    if ($file) {
        $file->delete();
    }
    $fs->create_file_from_string($fileinfo, $xml->asXML());

    if (!strpos($courserichmedia->referencesxml, '.xml')) {
        $courserichmedia->referencesxml = 'settings.xml';
    }
    $DB->update_record('richmedia', $courserichmedia);
} else {
    echo 1;
}
