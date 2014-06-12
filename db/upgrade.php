<?php

/**
 * Called when plugin is upgraded
 * Author:
 * 	Adrien Jamot  (adrien_jamot [at] symetrix [dt] fr)
 * 
 * @package   mod_richmedia
 * @copyright 2011 Symetrix
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

function xmldb_richmedia_upgrade($oldversion = 0) {

    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2012062100) {
        $table = new xmldb_table('richmedia');
        $presentor = new xmldb_field('presentor');
        $presentor->set_attributes(XMLDB_TYPE_CHAR, '255', null, null, null, null, null);

        /// Launch add field whatgrade
        if (!$dbman->field_exists($table, $presentor)) {
            $dbman->add_field($table, $presentor);
        }
        $font = new xmldb_field('font');
        $font->set_attributes(XMLDB_TYPE_CHAR, '255', null, null, null, null, null);

        /// Launch add field whatgrade
        if (!$dbman->field_exists($table, $font)) {
            $dbman->add_field($table, $font);
        }
        $fontcolor = new xmldb_field('fontcolor');
        $fontcolor->set_attributes(XMLDB_TYPE_CHAR, '255', null, null, null, null, null);

        /// Launch add field whatgrade
        if (!$dbman->field_exists($table, $fontcolor)) {
            $dbman->add_field($table, $fontcolor);
        }
        $defaultview = new xmldb_field('defaultview');
        $defaultview->set_attributes(XMLDB_TYPE_INTEGER, 1, null, null, null, null, null);

        /// Launch add field whatgrade
        if (!$dbman->field_exists($table, $defaultview)) {
            $dbman->add_field($table, $defaultview);
        }
        $referencessynchro = new xmldb_field('referencessynchro');
        $referencessynchro->set_attributes(XMLDB_TYPE_CHAR, '255', null, null, null, null, null);

        /// Launch add field whatgrade
        if (!$dbman->field_exists($table, $referencessynchro)) {
            $dbman->add_field($table, $referencessynchro);
        }
        $autoplay = new xmldb_field('autoplay');
        $autoplay->set_attributes(XMLDB_TYPE_INTEGER, 1, null, null, null, null, null);

        /// Launch add field whatgrade
        if (!$dbman->field_exists($table, $autoplay)) {
            $dbman->add_field($table, $autoplay);
        }
        upgrade_mod_savepoint(true, 2012062100, 'richmedia');
    } else if ($oldversion < 2012071600) {
        $table = new xmldb_table('richmedia');
        $field = new xmldb_field('keywords');
        $field->set_attributes(XMLDB_TYPE_CHAR, '255', null, null, null, null, null);
        /// Launch add field whatgrade
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2012071600, 'richmedia');
    }

    if ($oldversion < 2014030300) {
        $table = new xmldb_table('richmedia');
        $field = new xmldb_field('quizid');
        $field->set_attributes(XMLDB_TYPE_INTEGER, 10, null, null, null, null, null);
        /// Launch add field whatgrade
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    return true;
}
