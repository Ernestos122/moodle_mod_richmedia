/**
 * HTML5 richmedia player
 * Author:
 * 	Adrien Jamot  (adrien_jamot [at] symetrix [dt] fr)
 * 
 * @package   mod_richmedia
 * @copyright 2011 Symetrix
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

var quizPlayer = null;
if (typeof player !== 'undefined') {
    var quizPlayer = player;
}
var Player = {
    init: function(params, audioMode) {
        this.video = document.querySelector('#video');
        this.volumeBar = document.querySelector('#volume-bar');
        this.duration = Math.round(this.video.duration);
        this.slides = [];
        for (var s in params.tabslides) {
            params.tabslides[s].framein = parseInt(params.tabslides[s].framein);
            this.slides.push(params.tabslides[s]);
        }
        this.currentView;
        this.defaultview = params.defaultview;
        this.autoplay = params.autoplay;
        this.haspicture = params.haspicture;
        this.audioMode = audioMode;
        this.KEY_SPACE = 32;
        this.KEY_LEFT = 37;
        this.KEY_RIGHT = 39;
        this.locked = true;
        this.$richmedia = $('#richmedia');
        this.$symQuizPlayer = $('#symquiz_player');
        this.$subtitles = $('#subtitles');
        this.$cuePlayer = $('#cuePlayer');
        this.$video = $('#video');
        this.$text = $('#text');
        var that = this;

        this.preloadPlayerImages();

        this.$subtitles.draggable({appendTo: '#richmedia-content', containment: '#richmedia-fullcontent'}).draggable("enable");
        this.$cuePlayer.draggable({appendTo: '#richmedia-content', containment: '#richmedia-fullcontent'}).draggable("enable");
        
        this.resizePlayer();

        this.cuePlayerStyle = this.getStyleObject(this.$cuePlayer);
        this.subtitlesStyle = this.getStyleObject(this.$subtitles);
        this.textStyle  = this.getStyleObject(this.$text);
        this.videoStyle = this.getStyleObject(this.$video);

        this.initButtons();

        this.initKeyboard();


        cuepoint.init(that.slides, this.defaultview);

        if (this.autoplay == 1) {
            that.video.play();
        }

        // check video mode
        if ($.isEmptyObject(this.slides)) {
            $('#next').hide();
            $('#list').hide();
        }
        if ($.isEmptyObject(this.slides) || this.haspicture == 0) {
            this.videoFullScreen();
            $('#selectview').hide();
            $('#closed').hide();
        }

        this.$subtitles.bind("DOMNodeInserted", function() {
            if (that.currentView == 2) {
                that.centerImg();
            }
            if ($.isEmptyObject(that.slides) || that.haspicture == 0) {
                this.$subtitles.hide();
            }
        });

        this.initDialogs();

        this.initProgressBar();

        //CHROME
        video.ondurationchange = function() {
            that.duration = video.duration;
            that.initProgressBar();
        };

        //IOS
        this.video.addEventListener('loadedmetadata', function() {
            that.duration = this.duration;
            that.initProgressBar();
        });

        this.volumeBar.addEventListener("change", function() {
            that.video.volume = this.value;
        });

        this.video.addEventListener("ended", function() {
            if (quizPlayer) {
                quizPlayer.endQuiz();
                cuepoint.setQuiz();
            }
        });

        window.onresize = function(event) {
            that.resizePlayer();
            that.initProgressBar();
        };

        if (quizPlayer) {
            quizPlayer.init(false);
        }
    },
    preloadPlayerImages: function() {
        var sources = [];
        for (s in this.slides) {
            var src = this.slides[s].src;
            if (src) {
                if (typeof (src) == 'string' && src.substring(src.length - 1) != '/') {
                    sources.push(src);
                }
            }
        }
        sources.push(M.cfg.wwwroot + '/mod/richmedia/playerhtml5/pix/next.png');
        sources.push(M.cfg.wwwroot + '/mod/richmedia/playerhtml5/pix/pause.png');
        sources.push(M.cfg.wwwroot + '/mod/richmedia/playerhtml5/pix/play.png');
        sources.push(M.cfg.wwwroot + '/mod/richmedia/playerhtml5/pix/list.png');
        sources.push(M.cfg.wwwroot + '/mod/richmedia/playerhtml5/pix/prev.png');
        sources.push(M.cfg.wwwroot + '/mod/richmedia/playerhtml5/pix/credit.png');
        sources.push(M.cfg.wwwroot + '/mod/richmedia/playerhtml5/pix/closed_normal.png');
        sources.push(M.cfg.wwwroot + '/mod/richmedia/playerhtml5/pix/opened_normal.png');
        sources.push(M.cfg.wwwroot + '/mod/richmedia/playerhtml5/pix/closed_hover.png');
        sources.push(M.cfg.wwwroot + '/mod/richmedia/playerhtml5/pix/opened_hover.png');
        sources.push(M.cfg.wwwroot + '/mod/richmedia/playerhtml5/pix/next_roll.png');
        sources.push(M.cfg.wwwroot + '/mod/richmedia/playerhtml5/pix/pause_roll.png');
        sources.push(M.cfg.wwwroot + '/mod/richmedia/playerhtml5/pix/play_roll.png');
        sources.push(M.cfg.wwwroot + '/mod/richmedia/playerhtml5/pix/list_roll.png');
        sources.push(M.cfg.wwwroot + '/mod/richmedia/playerhtml5/pix/prev_roll.png');
        sources.push(M.cfg.wwwroot + '/mod/richmedia/playerhtml5/pix/credit_roll.png');
        sources.push(M.cfg.wwwroot + '/mod/richmedia/playerhtml5/pix/delimiter.png');
        sources.push(M.cfg.wwwroot + '/mod/richmedia/playerhtml5/pix/delimiter_question.png');
        sources.push(M.cfg.wwwroot + '/mod/richmedia/playerhtml5/pix/fullscreen.png');
        this.preloadimages(sources).done(function() {
            $('#loading').css('display', 'none');
            $('#richmedia').css('display', 'block');
        });
    },
    preloadimages: function(arr) {
        for (var i = 0; i < arr.length; i++) {
            if (typeof (arr[i]) != 'string') {
                arr.splice(i, 1);
            }
        }
        var newimages = [], loadedimages = 0;
        var postaction = function() {
        };
        var arr = (typeof arr != "object") ? [arr] : arr;
        function imageloadpost() {
            loadedimages++;
            if (loadedimages == arr.length) {
                postaction(newimages);
            }
        }
        for (var i = 0; i < arr.length; i++) {
            newimages[i] = new Image();
            newimages[i].src = arr[i];
            newimages[i].onload = function() {
                imageloadpost();
            };
            newimages[i].onerror = function() {
                imageloadpost();
            };
        }
        return {
            done: function(f) {
                postaction = f || postaction;
            }
        };
    },
    is_int: function(input) {
        return typeof (input) == 'number' && parseInt(input) == input;
    },
    convert_time: function(nbsecondes) {
        var temp = nbsecondes % 3600;
        var time2 = temp % 60;
        var time1 = (temp - time2) / 60;

        if (time1 == 0 || (this.is_int(time1) && time1 < 10)) {
            time1 = '0' + time1;
        }
        if (this.is_int(time2) && time2 < 10) {
            time2 = '0' + time2;
        }
        return time1 + ':' + time2;
    },
    changeDisplay: function(id, forced) {
        if (this.audioMode == 1)
            id = 2;
        else if (this.haspicture == 0) {
            id = 3;
        }
        if (forced || this.currentView != id) {
            this.currentView = id;
            if (id == 1) {
                this.defaultDisplay();
            }
            else if (id == 2) {
                this.slideFullScreen();
            }
            else if (id == 3) {
                this.videoFullScreen();
            }
        }
    },
    videoFullScreen: function() {
        this.$cuePlayer.css({
            width: '100%',
            height: '100%',
            position: 'absolute',
            top: '0',
            left: '0',
            margin: '0',
            'z-index': '0'
        }).show();

        this.$video.css({
            'width': '100%',
            'height': this.$cuePlayer.css('height')
        });

        if (this.subtitlesStyle) {
            this.$subtitles.css(this.subtitlesStyle).css({
                position: 'relative',
                width: '25%',
                height: '25%',
                'margin-right': '0',
                'z-index': '100'
            });
            this.$subtitles.draggable({containment: "#richmedia-fullcontent"}).draggable("enable");
        }
        
        this.$subtitles.find('img').css({
            height : 'auto',
            width : 'auto'
        });

        this.$text.hide();
        
        this.$cuePlayer.draggable({containment: "#richmedia-fullcontent"}).draggable("disable");

        this.centerVideo();
    },
    slideFullScreen: function() {
        this.$subtitles.css({
            width: '100%',
            height: '100%',
            'vertical-align': 'center',
            position: 'absolute',
            top: '0',
            left: '0',
            margin: '0',
            'z-index': '0',
            'background-color': '#000000'
        }).show();
        this.$subtitles.draggable("disable");
        
        if (this.cuePlayerStyle){
            this.$cuePlayer.css(this.cuePlayerStyle);
        }
        this.$cuePlayer.css({
            width: '30%',
            position: 'absolute',
            'z-index': '100',
            right : '2.2%',
            top : '15.2%'
        });
        
        if (this.videoStyle) {
            this.$video.css(this.videoStyle);
        }
        
        if (this.audioMode == 1) {
            this.$cuePlayer.css('top', '425px').css('left', '340px');
        }
        this.$cuePlayer.draggable({containment: "#richmedia-fullcontent"}).draggable("enable");

        this.$text.hide();
        
        this.$subtitles.find('img').css({
            height : this.$subtitles.height(),
            width : 'auto'
        });

        this.centerImg();
    },
    defaultDisplay: function() {
        this.$cuePlayer.css(this.cuePlayerStyle).show();
        this.$subtitles.css(this.subtitlesStyle).show();
        this.$video.css(this.videoStyle);
        this.$text.css(this.textStyle).show();

        this.$subtitles.draggable("disable");
        this.$cuePlayer.draggable("disable");
        
        this.$subtitles.find('img').css({
            height : 'auto',
            width : 'auto'
        });
    },
    prev: function() {
        var previndex;
        if (cuepoint.currentSlide) {
            if ((cuepoint.currentTime() - cuepoint.currentSlide.framein > 1)) {
                previndex = cuepoint.slides.indexOf(cuepoint.currentSlide);
            }
            else {
                previndex = cuepoint.slides.indexOf(cuepoint.currentSlide) - 1;
            }
            var prev = Math.max(previndex, 0);
            cuepoint.setTime(cuepoint.slides[prev].framein);
        }
        else {
            cuepoint.setTime(0);
        }
    },
    next: function() {
        if (cuepoint.currentSlide) {
            var next = Math.min(cuepoint.slides.indexOf(cuepoint.currentSlide) + 1, cuepoint.slides.length - 1);
            if (cuepoint.currentTime() < cuepoint.slides[next].framein) {
                cuepoint.setTime(cuepoint.slides[next].framein);
            }
        }
    },
    playControl: function() {
        if (this.video.paused == false) {
            this.pauseVideo();
        } else {
            this.playVideo();
        }
    },
    checkEventObj: function(_event_) {
        if (window.event)
            return window.event;
        else
            return _event_;
    },
    playVideo: function() {
        cuepoint.play();
    },
    pauseVideo: function() {
        cuepoint.pause();
    },
    showCredits: function() {
        var $copyright = $("#richmedia-copyright");
        if (!$copyright.dialog("isOpen")) {
            $copyright.dialog("open");
        }
        else {
            $copyright.dialog("close");
        }
    },
    displaySlides: function() {
        var $summary = $("#richmedia-summary");
        if (!$summary.dialog("isOpen")) {
            $summary.dialog("open");
        }
        else {
            $summary.dialog("close");
        }
    },
    getStyleObject: function(elem) {
       var dom = elem.get(0);
        var style;
        var returns = {};
        if (window.getComputedStyle) {
            var camelize = function(a, b) {
                return b.toUpperCase();
            };
            style = window.getComputedStyle(dom, null);
            for (var i = 0, l = style.length; i < l; i++) {
                var prop = style[i];
                var camel = prop.replace(/\-([a-z])/g, camelize);
                var val = style.getPropertyValue(prop);
                returns[camel] = val;
            }
            return returns;
        }
        if (style = dom.currentStyle) {
            for (var prop in style) {
                returns[prop] = style[prop];
            }
            return returns;
        }
        return elem.css();
    },
    centerImg: function() {
        var subtitlesheight = $('#richmedia-fullcontent').height();
        var $img = $('#subtitles img');
        if ($img.length > 0) {
            var marginTop = (subtitlesheight - $img.height()) / 2;
            if (marginTop >= 0) {
                $img.css('margin-top', marginTop);
            }
        }
    },
    centerVideo: function() {
        var cueplayerheight = this.$cuePlayer.height();
        var marginTop = (cueplayerheight - this.$video.height()) / 2;
        if (marginTop >= 0) {
            this.$video.css('margin-top', marginTop);
        }
    },
    resizePlayer: function() {
        var playerFullScreen = document.webkitIsFullScreen || document.mozFullScreen;
        if (playerFullScreen) {
            var width = $(document).width();
        }
        else {
            var width = $('#richmedia').parent().width();
        }

        if (width > 1254) {
            width = 1254;
        }
        var controlesHeight = $('#controles').height();
        var height = width * 0.6122 + controlesHeight;
        var $fullcontent = $('#richmedia-fullcontent');
        $('#richmedia').css({
            width: width + 'px',
            height: height + 'px'
        });
        
        $('#loading').height(height);
        height = height - controlesHeight;
        $fullcontent.css({
            height: height + 'px',
            width: width + 'px'
        });
        $('#controles').width(width);

        var $content = $('#richmedia-content');
        height = height - (width * 0.12);
        $content.height(height);
        $('#left').height(height);
        var textHeight = height - 200;
        this.$text.height(textHeight);

        height = this.$subtitles.width() * 0.75;
        this.$subtitles.height(height);

        if ($.isEmptyObject(this.slides) || this.haspicture == 0) {
            $('#cuePlayer, #video').css({
                width: $fullcontent.css('width'),
                height: $fullcontent.css('height')
            });
        }

        if (playerFullScreen) {
            if (!this.isChrome()){
                var screenHeight = $(document).height();
                var marginTop = (screenHeight - $fullcontent.height() - $('#controles').height()) / 2;
                $fullcontent.css('margin-top', marginTop + 'px');
            }
            else {
                $fullcontent.css('margin-top', 0);
                $('#richmedia').css('margin-top', 0);
            }
            $('#head').css('padding-top','3%');
            $('#title').css('font-size','31px');
        }
        else {
            $fullcontent.css('margin-top', 0);
            $('#head').css('padding-top','2.3%');
            $('#title').css('font-size','24px');
        }
        this.changeDisplay(this.currentView, true);

    },
    initButtons: function() {
        var that = this;
        //buttons management	
        $('#playbutton').click(function() {
            that.playControl();
        });

        //clic on prev button
        $('#prev').click(function() {
            that.prev();
        });

        //clic on next button
        $('#next').click(function() {
            that.next();
        });

        //clic on lock button
        $('#closed').click(function() {
            that.locked = !that.locked;
            if (that.locked) {
                $('#richmedia').removeClass('locked');
                $('#selectview').attr('disabled', 'disabled');
            }
            else {
                that.$richmedia.addClass('locked');
                $('#selectview').removeAttr("disabled");
            }
        });

        $('#selectview').on('change', function() {
            that.changeDisplay($(this).val());
        });
        $('#credit').on('click', function() {
            that.showCredits();
        });
        $('#list').click(function() {
            that.displaySlides();
        });

        $("#richmedia-summary tr").click(function() {
            var time = $(this).data('time');
            cuepoint.setTime(time);
            $("#richmedia-summary").dialog("close");
        });

        if (this.isIE()) {
            $('#fullscreen').hide();
        }
        else {
            $('#fullscreen').click(function() {
                var fullscreen = document.webkitIsFullScreen || document.mozFullScreen;
                if (fullscreen) {
                    that.exitFullscreen();
                }
                else {
                    that.setFullScreen(document.getElementById('richmedia'));
                }
            });
        }
    },
    initDialogs: function() {
        $("#richmedia-summary").dialog({
            autoOpen: false,
            appendTo: '#richmedia',
            resizable: false,
            draggable: false,
            closeText: M.util.get_string('close', 'mod_richmedia'),
            height: 350,
            width: 350,
            position: {my: "left bottom", at: "left bottom", of: '#richmedia-fullcontent'}
        });

        $("#richmedia-copyright").dialog({
            autoOpen: false,
            appendTo: '#richmedia',
            resizable: false,
            draggable: false,
            closeText: M.util.get_string('close', 'mod_richmedia'),
            width: 390,
            position: {my: "center center", at: "center center", of: '#richmedia-fullcontent'}
        });
    },
    initProgressBar: function() {
        if ($.isNumeric(this.duration) || this.slides.length > 0) {
            $('.img-preview').remove();
            var that = this;
            var width = $('#richmedia-fullcontent').width();
            for (var s in this.slides) {
                s = parseInt(s);
                if ((s - 1 > 0) && this.slides[s - 1].question) {
                    var delimiterImgUrl = M.cfg.wwwroot + '/mod/richmedia/playerhtml5/pix/delimiter_question.png';
                }
                else {
                    var delimiterImgUrl = M.cfg.wwwroot + '/mod/richmedia/playerhtml5/pix/delimiter.png';
                }
                var next = s + 1;
                var slide = this.slides[s];
                var posX = slide.framein * width / this.duration;
                var nextFrame = this.duration;
                if (typeof this.slides[next] != 'undefined') {
                    nextFrame = this.slides[next].framein;
                }
                var slideWidth = (nextFrame - slide.framein) * width / this.duration;
                $('<div class="img-preview" data-time="' + slide.framein + '" data-pos="' + s + '" title=""><img class="delimiter" src="' + delimiterImgUrl + '" title="' + that.convert_time(slide.framein) + '" data-pos="' + s + '"/></div>').appendTo('#progress-bar').css({
                    left: posX,
                    width: slideWidth
                }).tooltip({
                    content: function() {
                        var pos = $(this).data('pos');
                        if (that.haspicture == 1 && (typeof that.slides[pos].html != 'undefined')) {
                            return that.slides[pos].html + that.convert_time(that.slides[pos].framein);
                        }
                        else {
                            return that.slides[pos].slide + '<br />' + that.convert_time(that.slides[pos].framein);
                        }
                    },
                    position: {my: "bottom-20", at: "top left"}
                });
            }

            $('.img-preview').click(function() {
                cuepoint.setTime($(this).data('time'));
                if (that.isIOS()) {
                    $(this).tooltip("close");
                }
            });

            $('#progress-bar').click(function(e) {
                var parentOffset = $(this).parent().offset();
                var relX = e.pageX - parentOffset.left;
                var width = $(this).width();
                var time = relX * that.duration / width;
                cuepoint.setTime(time);
            });
        }
    },
    updateProgressBar: function(time) {
        if ($.isNumeric(this.duration)) {
            var ratio = time / this.duration * 100;
            if (ratio > 100){
                ratio = 100;
            }
            $('#progress').css('width', ratio + '%');
        }
    },
    initKeyboard: function() {
        var that = this;
        document.onkeydown = function(e) {
            var winObj = that.checkEventObj(e);
            var intKeyCode = winObj.keyCode;
            var $focused = $(':focus');
            if (!$focused.is('input[type="text"], textarea')){
                if (intKeyCode === that.KEY_RIGHT) {
                    that.next();
                    return false;
                }
                else if (intKeyCode === that.KEY_LEFT) {
                    that.prev();
                    return false;
                }
                else if (intKeyCode === that.KEY_SPACE) {
                    that.playControl();
                    return false;
                }
            }
        };
    },
    displayQuestion: function(questionid) {
        var position = this.getQuestionPosition(questionid);
        quizPlayer.openQuestion(position);
    },
    getQuestionPosition: function(questionid) {
        var questions = quizPlayer.currentQuiz.getQuestions();
        for (var q in questions) {
            if (questions[q].id == questionid) {
                return parseInt(q);
            }
        }
    },
    setFullScreen: function(element) {
        if (element.requestFullScreen) {
            element.requestFullScreen();
        } else if (element.webkitRequestFullScreen) {
            element.webkitRequestFullScreen(Element.ALLOW_KEYBOARD_INPUT);
        } else if (element.mozRequestFullScreen) {
            element.mozRequestFullScreen();
        } else {
            alert('Not supported by your browser');
        }
    },
    exitFullscreen: function() {
        if (document.cancelFullScreen) {
            document.cancelFullScreen();
        } else if (document.webkitCancelFullScreen) {
            document.webkitCancelFullScreen();
        } else if (document.mozCancelFullScreen) {
            document.mozCancelFullScreen();
        }
    },
    isIE: function() {
        var ua = window.navigator.userAgent;
        var msie = ua.indexOf('MSIE ');
        var trident = ua.indexOf('Trident/');

        if (msie > 0) {
            return parseInt(ua.substring(msie + 5, ua.indexOf('.', msie)), 10);
        }

        if (trident > 0) {
            var rv = ua.indexOf('rv:');
            return parseInt(ua.substring(rv + 3, ua.indexOf('.', rv)), 10);
        }
        return false;
    },
    isChrome: function(){
        return navigator.userAgent.toLowerCase().indexOf('chrome') > -1;
    },
    isIOS : function(){
        var deviceAgent = navigator.userAgent.toLowerCase();
        return deviceAgent.match(/(iphone|ipod|ipad)/);
    }

};