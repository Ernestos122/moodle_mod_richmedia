<div id="loading"><img src="<?php echo $CFG->wwwroot . '/mod/richmedia/playerhtml5/pix/ajax-loader.gif' ?>"/></div>
<div id="richmedia">
    <div id="richmedia-fullcontent" class="richmedia-fullcontent" style="font-family:<?php echo $richmediainfos->font ?>;background-image:url(<?php echo $CFG->wwwroot . '/mod/richmedia/themes/' . $richmediainfos->background; ?>)">
        <div id="head">
            <?php
            if (file_exists($CFG->dirroot . '/mod/richmedia/themes/' . $richmediainfos->logo)) {
                ?>
                <img id="richmedia-logo" src="<?php echo $CFG->wwwroot . '/mod/richmedia/themes/' . $richmediainfos->logo ?>"/>
                <?php
            }
            ?>
            <span id="richmedia-title" style="color: #<?php echo $richmediainfos->fontcolor ?>"><?php echo $richmediainfos->title ?></span>
        </div>	
        <div id="richmedia-content" class="richmedia-content">
            <div>
                <div id="left">
                    <section id="cuePlayer">

                        <?php
                        if (!$audioMode) {
                            ?>
                            <video id="video" preload="auto" onpause="Player.pauseVideo()" onplay="Player.playVideo()">
                                <source src="<?php echo $richmediainfos->filevideo ?>" type="video/mp4" />
                                <source src="<?php echo $richmediainfos->filevideo ?>" type="video/ogg" />
                                <source src="<?php echo $richmediainfos->filevideo ?>" type="video/webm" />
                            </video>
                            <?php
                        } else {
                            ?>
                            <audio id="video" preload="auto" onpause="Player.pauseVideo()" onplay="Player.playVideo()">
                                <source src="<?php echo $richmediainfos->filevideo ?>" type="audio/mpeg" />
                                <source src="<?php echo $richmediainfos->filevideo ?>" type="audio/ogg" />
                            </audio>

                            <?php
                        }
                        ?>

                    </section>
                    <div id="text">
                        <p id="presentername"><?php echo $richmediainfos->presentername ?></p>
                        <p id="presenterbio"><?php echo $richmediainfos->presenterbio ?></p>
                    </div>	
                </div>	
                <div id="subtitles"></div>
            </div>
        </div>
    </div>	
    <!-- barre de controle -->
    <div id="controles">
        <div id="progress-bar">
            <div id="progress"></div>
        </div>
        <div id="controles-left">
            <input id="list" type="button" title="<?php echo get_string('summary','mod_richmedia') ?>"/>
            <input type="button" id="prev" title="<?php echo get_string('prev','mod_richmedia') ?>"/>
            <input type="button" id="playbutton"/>
            <input type="button" id="next" title="<?php echo get_string('next','mod_richmedia') ?>"/>
        </div>
        <div id="controles-right">
            <input type="button" id="credit" />
        <?php
        if (!$audioMode) {
            ?>
                <input type="button" id="closed" />
                <select id="selectview" disabled="disabled">
                    <option value="#" selected="selected" disabled="disabled"><?php echo get_string('display', 'richmedia') ?></option>
                    <option value="1"><?php echo get_string('tile', 'richmedia') ?></option>
                    <option value="2"><?php echo get_string('slide','richmedia') ?></option>
                    <option value="3"><?php echo get_string('video','richmedia') ?></option>
                </select>
            <?php
        }
        ?>
            <input type="range" id="volume-bar" min="0" max="1" step="0.1" value="1">
            <input type="button" id="fullscreen" />
        </div>
    </div>
    <div id="richmedia-summary" title="<?php echo get_string('summary', 'richmedia') ?>">
        <?php if (count($richmediainfos->tabslides) > 0) { ?>
            <table>
                <thead></thead>
                <tbody>
                    <?php
                    $i = 1;
                    foreach ($richmediainfos->tabslides as $slide) {
                        ?>
                        <tr data-time="<?php echo $slide['framein'] ?>">
                            <td><?php echo $i ?></td>
                            <td><?php echo $slide['slide'] ?></td>
                            <td><?php echo richmedia_convert_time($slide['framein']) ?></td>
                        </tr>
                    <?php $i++; } ?>
                </tbody>
            </table>
        <?php } ?>
    </div>
    <div id="richmedia-copyright" title="About RichMedia plugin for Moodle...">
        <a href="http://www.elearning-symetrix.fr/produits/moodle_20-1/" target="_blank"><img width=61px height=52px src="<?php echo $CFG->wwwroot . '/mod/richmedia/playerhtml5/pix/logo_rm.png' ?>" /></a>
        <br />RichMedia Player version 2.4 (revised 19/03/2014)<br />For help and support, please contact<br/><br/>
        <a href="mailto:richmedia@symetrix.fr">richmedia@symetrix.fr</a>
        <br />
        <a href="http://www.elearning-symetrix.fr/produits/moodle_20-1/" target="_blank">www.symetrix.fr</a>
    </div>
</div>