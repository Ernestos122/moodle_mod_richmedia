<?php

/**
 * All functions not native to moodle
 * Author:
 * 	Adrien Jamot  (adrien_jamot [at] symetrix [dt] fr)
 * 
 * @package   mod_richmedia
 * @copyright 2011 Symetrix
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * * Extract files
 * */
function richmedia_parse($richmedia) {
    global $DB;
    if (!isset($richmedia->cmid)) {
        $cm = get_coursemodule_from_instance('richmedia', $richmedia->id);
        $richmedia->cmid = $cm->id;
    }
    $context = context_module::instance($richmedia->cmid);
    $newhash = $richmedia->sha1hash;

    $fs = get_file_storage();

    //SLIDES (rep data)
    $referenceslides = false;
    if ($referenceslides = $fs->get_file($context->id, 'mod_richmedia', 'package', 0, '/', $richmedia->referenceslides)) {
        $newhash = $referenceslides->get_contenthash();
    } else {
        $newhash = null;
    }
    if ($referenceslides) {
        // now extract files
        $packer = get_file_packer('application/zip');
        $referenceslides->extract_to_storage($packer, $context->id, 'mod_richmedia', 'content', 0, '/');
    }

    //VIDEO
    $referencesvideo = $fs->get_file(13, 'user', 'draft', 0, '/', $richmedia->referencesvideo);
    if ($referencesvideo) {
        $referencesvideo->copy_to_storage($context->id, 'mod_richmedia', 'content', 0, '/video/', $referencesvideo->get_filename());
    }

    //PICTURE
    $referencesfond = $fs->get_file(13, 'user', 'draft', 0, '/', $richmedia->referencesfond);
    if ($referencesfond) {
        $referencesfond->copy_to_storage($context->id, 'mod_richmedia', 'content', 0, '/picture/', $referencesfond->get_filename());
    }

    //XML (fichier HTM)
    $referencesxml = $fs->get_file(13, 'user', 'draft', 0, '/', $richmedia->referencesxml);
    if ($referencesxml) {
        $referencesxml->copy_to_storage($context->id, 'mod_richmedia', 'content', 0, '/', $referencesxml->get_filename());
    }
    //	
    $richmedia->revision++;
    $richmedia->sha1hash = $newhash;
    $DB->update_record('richmedia', $richmedia);
}

/**
 * * Add a user track for a richmedia
 * */
function richmedia_add_track($user, $richmedia) {
    global $DB;
    if ($track = $DB->get_record('richmedia_track', array('userid' => $user->id, 'richmediaid' => $richmedia->id))) {
        $track->attempt = $track->attempt + 1;
        $track->last = time();
        $DB->update_record('richmedia_track', $track);
        $id = $track->id;
    } else {
        $track = new stdClass();
        $track->userid = $user->id;
        $track->richmediaid = $richmedia->id;
        $track->attempt = 1;
        $track->start = time();
        $id = $DB->insert_record('richmedia_track', $track);
    }
    return $id;
}

/**
 * Export the rich media at scorm format
 * */
function richmedia_export_scorm($courserichmedia, $filename, $context, $scorm) {
    global $CFG;
    require_capability('moodle/course:manageactivities', $context);

    $zipper = get_file_packer('application/zip');

    $fs = get_file_storage();

    //VIDEO
    // Prepare video record object
    $fileinfovideo = new stdClass();
    $fileinfovideo->component = 'mod_richmedia';
    $fileinfovideo->filearea = 'content';
    $fileinfovideo->contextid = $context->id;
    $fileinfovideo->filepath = '/video/';
    $fileinfovideo->itemid = 0;
    $fileinfovideo->filename = $courserichmedia->referencesvideo;
    // Get file
    $filevideo = $fs->get_file($fileinfovideo->contextid, $fileinfovideo->component, $fileinfovideo->filearea, $fileinfovideo->itemid, $fileinfovideo->filepath, $fileinfovideo->filename);
    if ($filevideo) {
        $filevideoname = $filevideo->get_filename();
    }
    $files['richmedia/contents/content/video/' . $filevideoname] = $filevideo;

    //XML
    // Prepare video record object
    $fileinfoxml = new stdClass();
    $fileinfoxml->component = 'mod_richmedia';
    $fileinfoxml->filearea = 'content';
    $fileinfoxml->contextid = $context->id;
    $fileinfoxml->filepath = '/';
    $fileinfoxml->itemid = 0;
    $fileinfoxml->filename = $courserichmedia->referencesxml;
    // Get file
    $filexml = $fs->get_file($fileinfoxml->contextid, $fileinfoxml->component, $fileinfoxml->filearea, $fileinfoxml->itemid, $fileinfoxml->filepath, $fileinfoxml->filename);
    if ($filexml) {
        $filexmlname = $filexml->get_filename();
    }
    $files['richmedia/contents/content/' . $filexmlname] = $filexml;

    // SLIDES
    $slides = $fs->get_directory_files($context->id, 'mod_richmedia', 'content', 0, '/slides/');
    foreach ($slides as $slide) {
        $files['richmedia/contents/content/slides/' . $slide->get_filename()] = $slide;
    }

    //theme
    if (file_exists('themes/' . $courserichmedia->theme . '/logo.png')) {
        $files['richmedia/themes/' . $courserichmedia->theme . '/logo.png'] = 'themes/' . $courserichmedia->theme . '/logo.png';
    }
    if (file_exists('themes/' . $courserichmedia->theme . '/background.png')) {
        $files['richmedia/themes/' . $courserichmedia->theme . '/background.png'] = 'themes/' . $courserichmedia->theme . '/background.png';
    }
    if (file_exists('themes/' . $courserichmedia->theme . '/logo.jpg')) {
        $files['richmedia/themes/' . $courserichmedia->theme . '/logo.jpg'] = 'themes/' . $courserichmedia->theme . '/logo.jpg';
    }
    if (file_exists('themes/' . $courserichmedia->theme . '/background.jpg')) {
        $files['richmedia/themes/' . $courserichmedia->theme . '/background.jpg'] = 'themes/' . $courserichmedia->theme . '/background.jpg';
    }

    //swf file
    $files['richmedia/richmedia.swf'] = 'playerflash/richmedia.swf';
    $files['richmedia/playerflash/skin.swf'] = 'playerflash/skin.swf';

    if ($scorm) {
        //js files
        $files['richmedia/js/communicationAPI.js'] = 'export/include/communicationAPI.js';
        $files['richmedia/js/scorm12.js'] = 'export/include/scorm12.js';
    }

    //html file
    $filehtml = richmedia_create_index_html($context, $courserichmedia, $scorm);

    $files['richmedia/index.html'] = $filehtml;

    if ($scorm) {
        // SCORM FILES
        $files['adlcp_rootv1p2.xsd'] = 'export/include/adlcp_rootv1p2.xsd';
        $files['ims_xml.xsd'] = 'export/include/ims_xml.xsd';
        $files['imscp_rootv1p1p2.xsd'] = 'export/include/imscp_rootv1p1p2.xsd';
        $files['imsmanifest.xml'] = 'export/include/imsmanifest.xml';
        $files['imsmd_rootv1p2p1.xsd'] = 'export/include/imsmd_rootv1p2p1.xsd';
    }

    //create the zip
    if ($newfile = $zipper->archive_to_storage($files, $fileinfovideo->contextid, 'mod_richmedia', 'zip', '0', '/', $filename . '.zip')) {
        $lifetime = isset($CFG->filelifetime) ? $CFG->filelifetime : 86400;
        send_stored_file($newfile, $lifetime, 0, false);
    } else {
        echo 'Une erreur s\'est produite'; // TODO : translate
    }
}

/**
 * Delete a richmedia track
 * */
function richmedia_delete_track($userid, $richmediaid) {
    global $DB;
    $DB->delete_records('richmedia_track', array('userid' => $userid, 'richmediaid' => $richmediaid));
}

/**
 * Create the good index.html to export the richmedia
 * */
function richmedia_create_index_html($context, $richmedia, $scorm) {
    $fs = get_file_storage();

    // Prepare file record object
    $fileinfo = array(
        'contextid' => $context->id, // ID of context
        'component' => 'mod_richmedia', // usually = table name
        'filearea' => 'html', // usually = table name
        'itemid' => 0, // usually = ID of row in table
        'filepath' => '/', // any path beginning and ending in /
        'filename' => 'index.html'); // any filename


    $filehtml = $fs->get_file(
            $fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']
    );

    if ($filehtml) {
        $filehtml->delete();
    }

    if ($scorm) {
        $scorm = 1;
        $scripts = '
				<script type="text/javascript" src="js/communicationAPI.js"></script>
				<script type="text/javascript" src="js/scorm12.js"></script>';
        $unload = ' onUnload = "QuitWindow()"';
    } else {
        $scorm = 0;
        $scripts = '';
        $unload = '';
    }

    $filecontent = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml" lang="fr" xml:lang="fr">
			<head>
				<title>richmedia</title>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<style type="text/css" media="screen">
				html, body { height:100%; background-color: #999999;}
				body { margin:0; padding:0; overflow:hidden; }
				#flashContent { width:100%; height:100%; }
				</style>' . $scripts . '
			</head>
			<body' . $unload . '>
				<div id="flashContent">
					<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" width="100%" height="100%" id="richmedia" align="middle">
						<param name="movie" value="richmedia.swf" />
						<param name="quality" value="high" />
						<param name="bgcolor" value="#999999" />
						<param name="play" value="true" />
						<param name="loop" value="true" />
						<param name="wmode" value="window" />
						<param name="scale" value="showall" />
						<param name="menu" value="true" />
						<param name="devicefont" value="false" />
						<param name="salign" value="" />
						<param name="allowScriptAccess" value="sameDomain" />
						<param name="allowFullScreen" value="true" />
						<param name="flashVars" value="urlContent=contents/content/&urlTheme=themes/' . $richmedia->theme . '/&cb_view_label=' . get_string('display', 'richmedia') . '&cb_view1=' . get_string('tile', 'richmedia') . '&cb_view2=' . get_string('slide', 'richmedia') . '&cb_view3=' . get_string('video', 'richmedia') . '&scorm=' . $scorm . '" />
						<!--[if !IE]>-->
						<object type="application/x-shockwave-flash" data="richmedia.swf" width="100%" height="100%">
							<param name="movie" value="richmedia.swf" />
							<param name="quality" value="high" />
							<param name="bgcolor" value="#999999" />
							<param name="play" value="true" />
							<param name="loop" value="true" />
							<param name="wmode" value="window" />
							<param name="scale" value="showall" />
							<param name="menu" value="true" />
							<param name="devicefont" value="false" />
							<param name="salign" value="" />
							<param name="allowScriptAccess" value="sameDomain" />
							<param name="allowFullScreen" value="true" />
							<param name="flashVars" value="urlContent=contents/content/&urlTheme=themes/' . $richmedia->theme . '/&cb_view_label=' . get_string('display', 'richmedia') . '&cb_view1=' . get_string('tile', 'richmedia') . '&cb_view2=' . get_string('slide', 'richmedia') . '&cb_view3=' . get_string('video', 'richmedia') . '&scorm=' . $scorm . '" />
						<!--<![endif]-->
							<a href="http://www.adobe.com/go/getflash">
								<img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Obtenir Adobe Flash Player" />
							</a>
						<!--[if !IE]>-->
						</object>
						<!--<![endif]-->
					</object>
				</div>
			</body>
		</html>';
    $fs->create_file_from_string($fileinfo, $filecontent);

    return $filehtml;
}

/**
 * Generate the settings.xml file
 * */
function richmedia_generate_xml($richmedia) {
    global $CFG;
    $context = context_module::instance($richmedia->cmid);
    $referencesxml = $richmedia->referencesxml;
    $extension = explode('.', $referencesxml);
    if (!$referencesxml || end($extension) != 'xml') {
        $richmedia->referencesxml = "settings.xml";
    }
    $fs = get_file_storage();

    // Prepare file record object
    $fileinfo = new stdClass();
    $fileinfo->component = 'mod_richmedia';
    $fileinfo->filearea = 'content';
    $fileinfo->contextid = $context->id;
    $fileinfo->filepath = '/';
    $fileinfo->itemid = 0;
    $fileinfo->filename = $referencesxml;
    // Get file
    $file = $fs->get_file($fileinfo->contextid, $fileinfo->component, $fileinfo->filearea, $fileinfo->itemid, $fileinfo->filepath, $fileinfo->filename);

    // Read contents
    if ($file) {
        $contenuxml = $file->get_content();
        $contenuxml = str_replace('&', '&amp;', $contenuxml);

        $oldxml = \simplexml_load_string($contenuxml);
    }
    //

    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><settings></settings>');
    $movie = $xml->addChild('movie');
    $movie->addAttribute('src', 'video/' . $richmedia->referencesvideo);

    $design = $xml->addChild('design');

    if (file_exists($CFG->dirroot . '/mod/richmedia/themes/' . $richmedia->theme . '/logo.png')) {
        $design->addAttribute('logo', 'logo.png');
    } else if (file_exists($CFG->dirroot . '/mod/richmedia/themes/' . $richmedia->theme . '/logo.jpg')) {
        $design->addAttribute('logo', 'logo.jpg');
    }

    $design->addAttribute('font', $richmedia->font);

    if (file_exists($CFG->dirroot . '/mod/richmedia/themes/' . $richmedia->theme . '/background.png')) {
        $design->addAttribute('background', 'background.png');
    } else if (file_exists($CFG->dirroot . '/mod/richmedia/themes/' . $richmedia->theme . '/background.jog')){
        $design->addAttribute('background', 'background.jpg');
    }
    if ($richmedia->fontcolor[0] == '#') {
        $richmedia->fontcolor = substr($richmedia->fontcolor, 1);
    }
    $design->addAttribute('fontcolor', '0x' . $richmedia->fontcolor);
    if ($richmedia->autoplay == 0) {
        $richmedia->autoplay = 'false';
    } else {
        $richmedia->autoplay = 'true';
    }

    $options = $xml->addChild('options');
    $options->addAttribute('presenter', '1');
    $options->addAttribute('comment', '0');
    $options->addAttribute('defaultview', $richmedia->defaultview);
    $options->addAttribute('btnfullscreen', 'true');
    $options->addAttribute('btninverse', 'false');
    $options->addAttribute('autoplay', $richmedia->autoplay);

    $presenter = $xml->addChild('presenter');
    $presenter->addAttribute('name', html_entity_decode($richmedia->presentor));
    $presenter->addAttribute('biography', strip_tags(html_entity_decode($richmedia->intro)));

    $titles = $xml->addChild('titles');
    $title1 = $titles->addChild('title');
    $title1->addAttribute('target', 'fdPresentationTitle');
    $title1->addAttribute('label', html_entity_decode($richmedia->name));
    $title2 = $titles->addChild('title');
    $title2->addAttribute('target', 'fdMovieTitle');
    $title2->addAttribute('label', '');
    $title3 = $titles->addChild('title');
    $title3->addAttribute('target', 'fdSlideTitle');
    $title3->addAttribute('label', '');

    //traitement des steps
    $steps = $xml->addChild('steps');
    if ($file) {
        if ($oldxml) {
            foreach ($oldxml->steps[0]->children() as $childname => $childnode) {
                $step = $steps->addChild('step');
                foreach ($childnode->attributes() as $attribute => $value) {
                    $step->addAttribute($attribute, $value);
                }
            }
        }
    }

    if ($file) {
        $file->delete();
    }
    $fs->create_file_from_string($fileinfo, $xml->asXML());
}

function richmedia_is_audio($richmedia) {
    $media = $richmedia->referencesvideo;
    $extensionExplode = explode('.', $media);
    $extension = end($extensionExplode);
    return $extension == 'mp3';
}

function richmedia_webtv_exists() {
    global $DB;
    return $DB->record_exists('block', array('name' => 'webtv'));
}

function richmedia_convert_to_html($value) {
    $value = str_replace("é", "&eacute;", $value);
    $value = str_replace("è", "&egrave;", $value);
    $value = str_replace("ê", "&ecirc;", $value);
    $value = str_replace("à", "&agrave;", $value);
    $value = str_replace("ç", "&ccedil;", $value);
    $value = str_replace("û", "&ucirc;", $value);
    return $value;
}

function richmedia_convert_time($nbsecondes) {
    $temp = $nbsecondes % 3600;
    $time[0] = ( $nbsecondes - $temp ) / 3600;
    $time[2] = $temp % 60;
    $time[1] = ( $temp - $time[2] ) / 60;

    if ($time[1] == 0 || (is_int($time[1]) && $time[1] < 10)) {
        $time[1] = '0' . $time[1];
    }
    if (is_int($time[2]) && $time[2] < 10) {
        $time[2] = '0' . $time[2];
    }
    return $time[1] . ':' . $time[2];
}

/**
 * Get richmedia infos used by html5 player
 * @global type $CFG
 * @param type $richmedia
 * @return \stdClass
 */
function richmedia_get_html5_infos($richmedia) {
    global $CFG;
    $cm = get_coursemodule_from_instance('richmedia', $richmedia->id);
    $context = context_module::instance($cm->id);
    $repslides = "{$CFG->wwwroot}/pluginfile.php/{$context->id}/mod_richmedia/content/slides/";
    $filevideo = "{$CFG->wwwroot}/pluginfile.php/{$context->id}/mod_richmedia/content/video/" . $richmedia->referencesvideo;
    $fs = get_file_storage();
    $fileinfo = new stdClass();
    $fileinfo->component = 'mod_richmedia';
    $fileinfo->filearea = 'content';
    $fileinfo->contextid = $context->id;
    $fileinfo->filepath = '/';
    $fileinfo->itemid = 0;
    $fileinfo->filename = $richmedia->referencesxml;
// Get file
    $file = $fs->get_file($fileinfo->contextid, $fileinfo->component, $fileinfo->filearea, $fileinfo->itemid, $fileinfo->filepath, $fileinfo->filename);

// Read contents

    $richmedia_infos = new stdClass();
    $richmedia_infos->haspicture = 0;

    if ($file) {
        $contenuxml = $file->get_content();
        $contenuxml = str_replace('&', '&amp;', $contenuxml);

        $xml = simplexml_load_string($contenuxml);

        foreach ($xml->titles[0]->title[0]->attributes() as $attribute => $value) {
            if ($attribute == 'label') {
                $value = str_replace("&rsquo;", iconv("CP1252", "UTF-8", "’"), $value);
                $value = str_replace("â€™", "’", $value);
                $value = str_replace("’", "'", $value);
                $value = richmedia_convert_to_html($value);
                $title = $value;
                break;
            }
        }
        foreach ($xml->presenter[0]->attributes() as $attribute => $value) {
            if ($attribute == 'name') {
                $value = str_replace("&rsquo;", iconv("CP1252", "UTF-8", "’"), $value);
                $value = str_replace("â€™", "’", $value);
                $value = str_replace("’", "'", $value);
                $presentername = richmedia_convert_to_html($value);
            } else if ($attribute == 'biography') {
                $value = str_replace("&rsquo;", iconv("CP1252", "UTF-8", "’"), $value);
                $value = str_replace("â€™", "’", $value);
                $value = str_replace("’", "'", $value);
                $presenterbio = richmedia_convert_to_html($value);
            } else if ($attribute == 'title') {
                $value = str_replace("&rsquo;", iconv("CP1252", "UTF-8", "’"), $value);
                $value = str_replace("â€™", "’", $value);
                $value = str_replace("’", "'", $value);
                $presentertitle = richmedia_convert_to_html($value);
            }
        }
        foreach ($xml->design[0]->attributes() as $attribute => $value) {
            if ($attribute == 'fontcolor') {
                $fontcolor = substr($value, 2);
                break;
            }
        }
        $font = $richmedia->font;
        $tabstep = array();
        $i = 0;
        $tabslides = array();
        foreach ($xml->steps[0]->children() as $childname => $childnode) {
            foreach ($childnode->attributes() as $attribute => $value) {
                $tabstep[$i][$attribute] = (String) $value;
            }
            $time = $tabstep[$i]['framein'];

            $tabslides[$i]['framein'] = $time;
            $tabslides[$i]['slide'] = $tabstep[$i]['label'];

            if (!array_key_exists('view', $tabstep[$i]) || $tabstep[$i]['view'] == '') {
                $tabstep[$i]['view'] = $richmedia->defaultview;
            }
            $tabslides[$i]['view'] = $tabstep[$i]['view'];

            if ($f = $fs->get_file($context->id, 'mod_richmedia', 'content', 0, '/slides/', $tabstep[$i]['slide'])) {
                $tabslides[$i]['src'] = $repslides . $tabstep[$i]['slide'];
                $tabslides[$i]['html'] = '<img src="' . $repslides . $tabstep[$i]['slide'] . '" width="100%" view="' . $tabstep[$i]['view'] . '"/><br/><span class="slide-title">' . $tabstep[$i]['label'] . '</span>&nbsp;';

                $richmedia_infos->haspicture = 1;
            }
            else {
                $tabslides[$i]['src'] = '';
            }

            //$tabslides[$i]['comment'] = $tabstep[$i]['comment'];
            if (isset($tabstep[$i]['question'])) {
                $tabslides[$i]['question'] = $tabstep[$i]['question'];
            }
            else {
                $tabslides[$i]['question'] = '';
            }

            $i++;
        }

        if (file_exists($CFG->dirroot . '/mod/richmedia/themes/' . $richmedia->theme . '/logo.jpg')) {
            $logo = $richmedia->theme . '/logo.jpg';
        } else {
            $logo = $richmedia->theme . '/logo.png';
        }

        if (file_exists($CFG->dirroot . '/mod/richmedia/themes/' . $richmedia->theme . '/background.jpg')) {
            $background = $richmedia->theme . '/background.jpg';
        } else {
            $background = $richmedia->theme . '/background.png';
        }
        $defaultview = $richmedia->defaultview;
        $autoplay = $richmedia->autoplay;
    }

    $richmedia_infos->tabslides = $tabslides;
    $richmedia_infos->defaultview = $defaultview;
    $richmedia_infos->autoplay = $autoplay;
    $richmedia_infos->background = $background;
    $richmedia_infos->logo = $logo;
    $richmedia_infos->fontcolor = $fontcolor;
    $richmedia_infos->font = $font;
    $richmedia_infos->title = $title;
    $richmedia_infos->filevideo = $filevideo;
    $richmedia_infos->presentername = $presentername;
    $richmedia_infos->presenterbio = $presenterbio;

    return $richmedia_infos;
}