<?php

/**
 * Save and edit themes
 * Author:
 * 	Adrien Jamot  (adrien_jamot [at] symetrix [dt] fr)
 * 
 * @package   mod_richmedia
 * @copyright 2011 Symetrix
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */
//supprime un repertoire et son contenu
function rrmdir($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (filetype($dir . '/' . $object) == "dir")
                    rrmdir($dir . '/' . $object);
                else
                    unlink($dir . '/' . $object);
            }
        }
        reset($objects);
        rmdir($dir);
    }
}

require_once("../../config.php");

if (isset($_GET['upload'])) {
    $repthemes = 'themes/';
    chmod($repthemes, 0775);
    if (isset($_POST['nom'])) {
        $nom = $_POST['nom'];
        if (isset($_FILES['logoupload']) && $_FILES['logoupload']['name'] != '') {
            if (isset($_FILES['backgroundupload']) && $_FILES['backgroundupload']['name'] != '') {
                if (!is_dir($repthemes . $nom)) {
                    mkdir($repthemes . $nom, 0775);
                }
                chmod($repthemes . $nom, 0775);
                if (is_dir($repthemes . $nom)) {
                    if (move_uploaded_file($_FILES['logoupload']['tmp_name'], $repthemes . $nom . '/logo.jpg')) {
                        if (move_uploaded_file($_FILES['backgroundupload']['tmp_name'], $repthemes . $nom . '/background.jpg')) {
                            echo "{success: true}";
                        }
                    } else {
                        echo $repthemes . $nom . '/logo.jpg';
                    }
                }
            } else {
                echo "{failure: true, msg:{reason:'Fichier de fond manquant'}}";
            }
        } else {
            echo "{failure: true, msg:{reason:'Logo manquant'}}";
        }
    } else {
        echo "{failure: true, msg:{reason:'Nom manquant'}}";
    }
} else if (isset($_GET['store'])) {

    $tabtheme = array();
    $repthemes = 'themes/';
    chmod($repthemes, 0775);
    $dossierthemes = $repthemes;
    if ($dossier = opendir($dossierthemes)) {
        $i = 0;
        while (false !== ($fichier = readdir($dossier))) {
            if (is_dir($dossierthemes . $fichier) && $fichier != '.' && $fichier != '..' && $fichier != '.svn') {
                $tabtheme[$i]['nom'] = $fichier;
                if (is_file($dossierthemes . $fichier . '/logo.jpg')) {
                    $tabtheme[$i]['logo'] = 'logo.jpg';
                }
                else if (is_file($dossierthemes . $fichier . '/logo.png')){
                    $tabtheme[$i]['logo'] = 'logo.png';
                }
                else {
                    $tabtheme[$i]['logo'] = '';
                }
                if (is_file($dossierthemes . $fichier . '/background.jpg')) {
                    $tabtheme[$i]['background'] = 'background.jpg';
                }
                else if (is_file($dossierthemes . $fichier . '/background.png')) {
                    $tabtheme[$i]['background'] = 'background.png';
                }
                else {
                    $tabtheme[$i]['background'] = '';
                }
                $tabtheme[$i]['id'] = $i;
                $i++;
            }
        }
    }
    echo json_encode($tabtheme);
} else if (isset($_GET['delete'])) {
    $rep = $_POST['nom'];
    $repthemes = 'themes/';
    chmod($repthemes, 0775);
    if (is_dir($repthemes . $rep)) {
        chmod($repthemes . $rep, 0775);
        rrmdir($repthemes . $rep);
        echo 1;
    } else {
        echo 0;
    }
}
else if (isset($_GET['edit'])) {
    $anciennom = $_POST['anciennom'];
    if (isset($_POST['nom'])) {
        $nom = $_POST['nom'];
        $repthemes = 'themes/';
        chmod($repthemes, 0775);
        if (isset($_FILES['logoupload']) && $_FILES['logoupload']['name'] != '') {
            if ($anciennom != $nom) {
                rrmdir($repthemes . $anciennom);
            }
        } else {
            rename($repthemes . $anciennom, $repthemes . $nom);
        }
        if (isset($_FILES['logoupload']) && $_FILES['logoupload']['name'] != '') {
            if (!is_dir($repthemes . $nom)) {
                mkdir($repthemes . $nom, 0775);
            }
            unlink($repthemes . $nom . '/logo.jpg');
            move_uploaded_file($_FILES['logoupload']['tmp_name'], $repthemes . $nom . '/logo.jpg');
        }
        if (isset($_FILES['backgroundupload']) && $_FILES['backgroundupload']['name'] != '') {
            if (!is_dir($repthemes . $nom)) {
                mkdir($repthemes . $nom, 0775);
            }
            unlink($repthemes . $nom . '/background.jpg');
            move_uploaded_file($_FILES['backgroundupload']['tmp_name'], $repthemes . $nom . '/background.jpg');
        }
        echo"{success: true}";
    } else {
        echo "{failure: true, msg:{reason:'Nom manquant'}}";
    }
}
