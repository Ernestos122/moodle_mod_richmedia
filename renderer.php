<?php

/**
 * Richmedia renderer
 * Author:
 * 	Adrien Jamot  (adrien_jamot [at] symetrix [dt] fr)
 * 
 * @package   mod_richmedia
 * @copyright 2011 Symetrix
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/richmedia/lib.php');

class mod_richmedia_renderer extends plugin_renderer_base {

    /**
     * Print richmedia intro + keywords
     * @global type $CFG
     * @param type $richmedia
     * @return string
     */
    public function intro($richmedia) {
        global $CFG;
        $output = '<div id="intro-div" style="width : ' . $richmedia->width . 'px;">';
        $output .= $richmedia->intro;

        $output .= '<a class="keyword" href="' . $CFG->wwwroot . '/course/view.php?id=' . $richmedia->course . '"><span>' . get_string('return', 'richmedia') . '</span></a>';
        if (isset($richmedia->keywords) && $richmedia->keywords != '') {
            $output .= $this->keywords($richmedia);
        }
        $output .= '</div>';
        return $output;
    }

    /**
     * Print richmedia keywords
     * @global type $CFG
     * @param type $richmedia
     * @return string
     */
    public function keywords($richmedia) {
        global $CFG;
        $output = '';
        if (isset($richmedia->keywords)) {
            $output .= '<br /><br />';
            $output .= get_string('keywords', 'richmedia') . ' : ';

            $keywords = explode(',', $richmedia->keywords);
            if ($keywords) {
                if (richmedia_webtv_exists()) {
                    foreach ($keywords as $keyword) {
                        $output .= '<a href="' . $CFG->wwwroot . '/blocks/webtv/search.php?search=' . $keyword . '&course=' . $richmedia->course . '">' . $keyword . '</a>&nbsp;';
                    }
                } else {
                    foreach ($keywords as $keyword) {
                        $output .= $keyword . ' ';
                    }
                }
            }
        }
        return $output;
    }

    /**
     * Display richmedia at flash format
     * @global type $CFG
     * @param type $richmedia
     * @return string
     */
    public function richmedia_display($richmedia) {
        global $CFG;
        if (!isset($richmedia->cmid)) {
            $cm = get_coursemodule_from_instance('richmedia', $richmedia->id);
            $richmedia->cmid = $cm->id;
        }
        $context = context_module::instance($richmedia->cmid);
        $output = '';

        if (is_dir($CFG->dirroot . '/mod/richmedia/themes/' . $richmedia->theme)) {
            $width = 700;
            $height = 451;
            if ($richmedia->width && $richmedia->width != '' && $richmedia->width != 0) {
                $width = $richmedia->width;
            }
            if ($richmedia->height && $richmedia->height != '' && $richmedia->height != 0) {
                $height = $richmedia->height;
            }
            $fs = get_file_storage();
            $file = $fs->get_file($context->id, 'mod_richmedia', 'content', 0, '/', $richmedia->referencesxml);
            if ($file) {
                $url = "{$CFG->wwwroot}/pluginfile.php/{$file->get_contextid()}/mod_richmedia/content";
                $filename = $file->get_filename();
                if ($filename == $richmedia->referencesxml) {
                    $videoexplode = explode('.', $richmedia->referencesvideo);
                    $videoextension = end($videoexplode);
                    //show the flash player
                    if (!$richmedia->html5 || ($videoextension == 'swf') || ($videoextension == 'flv')) {
                        $output .= $this->flashPlayer($url, $width, $height, $richmedia->theme);
                    }
                }
            } else {
                $output .= get_string('xmlnotfound', 'richmedia');
            }
        } else {
            $output .= 'Theme ' . $richmedia->theme . ' doesn\'t exist'; //TODO Translate
        }
        return $output;
    }

    public function flashPlayer($url, $width, $height, $theme) {
        return '
            <div id="flashContent" style="text-align:center;">
                <object type="application/x-shockwave-flash"  data="playerflash/richmedia.swf" width="' . $width . '" height="' . $height . '">
                    <param name="movie" value="playerflash/richmedia.swf" />
                    <param name="quality" value="high" />
                    <param name="bgcolor" value="#999999" />
                    <param name="play" value="true" />
                    <param name="loop" value="true" />
                    <param name="wmode" value="transparent" />
                    <param name="scale" value="showall" />
                    <param name="menu" value="true" />
                    <param name="devicefont" value="false" />
                    <param name="salign" value="" />
                    <param name="allowScriptAccess" value="sameDomain" />
                    <param name="allowFullScreen" value="true" />
                    <param name="flashVars" value="urlContent=' . $url . '/&urlTheme=themes/' . $theme . '/&cb_view_label=' . get_string('display', 'richmedia') . '&cb_view1=' . get_string('tile', 'richmedia') . '&cb_view2=' . get_string('slide', 'richmedia') . '&cb_view3=' . get_string('video', 'richmedia') . '&scorm=0" />
                </object>
            </div>';
    }

}
