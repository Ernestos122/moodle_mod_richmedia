/**
 * Javascript for sync editor
 * Author:
 * 	Adrien Jamot  (adrien_jamot [at] symetrix [dt] fr)
 * 
 * @package   mod_richmedia
 * @copyright 2011 Symetrix
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

getExtension = function(filename) {
    var parts = filename.split(".");
    return (parts[(parts.length - 1)]);
};

M.mod_richmedia_xmleditor = {
    init: function(Y, availableSlides, tabstep, fileurl, movie, title, presentername, presentertitle, contextid, update, fontcolor, fontvalue, urlSlides, defaultview, autoplay, urlSubmit, urlLocation, urlView, questions) {
        this.availableSlides = availableSlides;
        this.tabstep = tabstep;
        this.fileurl = fileurl;
        this.movie = movie;
        this.title = title;
        this.presentername = presentername;
        this.presentertitle = presentertitle;
        this.contextid = contextid;
        this.update = update;
        this.fontcolor = fontcolor;
        this.fontvalue = fontvalue;
        this.urlSlides = urlSlides;
        this.defaultview = defaultview;
        this.autoplay = autoplay;
        this.urlSubmit = urlSubmit;
        this.urlLocation = urlLocation;
        this.urlView = urlView;
        this.videoHTML = false;
        var that = this;
        this.questions = [];
        if (questions.length > 0) {
            this.questions[0] = '';
            for (var q in questions) {
                var qArray = [q, questions[q]];
                this.questions.push(qArray);
            }
        }

        this.storesteps = new Ext.data.JsonStore({
            fields: [
                {
                    name: 'id',
                    type: 'int'
                }, {
                    name: 'label',
                    type: 'string'
                }, {
                    name: 'framein',
                    type: 'string'
                }, {
                    name: 'slide',
                    type: 'string'
                }, {
                    name: 'question',
                    type: 'string'
                }, {
                    name: 'url',
                    type: 'string'
                }, {
                    name: 'view',
                    type: 'string'
                }
            ],
            data: that.tabstep
        });

        this.titlecmp = new Ext.form.Hidden({
            value: title,
            name: 'title'
        });

        this.moviecmp = new Ext.form.Hidden({
            value: that.movie,
            name: 'movie'
        });

        this.presenternamecmp = new Ext.form.Hidden({
            value: that.presentername,
            name: 'presentername'
        });

        this.presentertitlecmp = new Ext.form.Hidden({
            value: that.presentertitle,
            name: 'presentertitle'
        });

        this.color = new Ext.form.Hidden({
            value: that.fontcolor,
            name: 'fontcolor'
        });

        this.defaultviewcmp = new Ext.form.Hidden({
            value: that.defaultview,
            name: 'defaultview'
        });

        this.autoplaycmp = new Ext.form.Hidden({
            value: that.autoplay,
            name: 'autoplay'
        });

        this.fontcmp = new Ext.form.Hidden({
            value: that.fontvalue,
            name: 'font'
        });

        this.submitbtn = new Ext.Button({
            text: M.util.get_string('saveandreturn', 'mod_richmedia'),
            handler: function() {
                that.form.getForm().submit({
                    url: 'xmleditor_save.php',
                    waitTitle: M.util.get_string('wait', 'mod_richmedia'),
                    waitMsg: M.util.get_string('currentsave', 'mod_richmedia'),
                    params: {
                        steps: Ext.encode(that.gridsteps.getValue()),
                        contextid: that.contextid,
                        update: that.update
                    },
                    success: function(result, request) {
                        document.location.href = that.urlLocation;
                    },
                    failure: function(result, request) {
                        console.log(result, request);
                    }
                });
            }
        });

        this.cancelbtn = new Ext.Button({
            text: M.util.get_string('cancel', 'mod_richmedia'),
            handler: function() {
                document.location.href = that.urlLocation;
            }
        });

        this.addbtn = new Ext.Button({
            iconCls: 'add',
            text: M.util.get_string('addline', 'mod_richmedia'),
            handler: function() {
                that.getTime(that.storesteps.data.length);
            }
        });

        this.playbtn = new Ext.Button({
            iconCls: 'play',
            text: M.util.get_string('test', 'mod_richmedia'),
            handler: function() {
                that.form.getForm().submit({
                    url: 'xmleditor_save.php',
                    waitTitle: M.util.get_string('wait', 'mod_richmedia'),
                    waitMsg: M.util.get_string('currentsave', 'mod_richmedia'),
                    params: {
                        steps: Ext.encode(that.gridsteps.getValue()),
                        contextid: that.contextid,
                        update: that.update
                    },
                    success: function(result, request) {
                        window.open(that.urlView);
                    },
                    failure: function(result, request) {
                        console.log(result, request);
                    }
                });
            }
        });

        this.savebtn = new Ext.Button({
            iconCls: 'save',
            text: M.util.get_string('save', 'mod_richmedia'),
            handler: function() {
                that.form.getForm().submit({
                    url: 'xmleditor_save.php',
                    waitTitle: M.util.get_string('wait', 'mod_richmedia'),
                    waitMsg: M.util.get_string('currentsave', 'mod_richmedia'),
                    params: {
                        steps: Ext.encode(that.gridsteps.getValue()),
                        contextid: that.contextid,
                        update: that.update
                    },
                    success: function(result, request) {
                        Ext.Msg.show({
                            title: M.util.get_string('information', 'mod_richmedia'),
                            msg: M.util.get_string('savedone', 'mod_richmedia'),
                            buttons: Ext.Msg.OK
                        });
                    },
                    failure: function(result, request) {
                        console.log(result, request);
                    }
                });
            }
        });

        this.cmsteps = new Ext.grid.ColumnModel({
            defaults: {
                sortable: true
            },
            columns: [
                {
                    id: 'id',
                    header: 'Id',
                    dataIndex: 'id',
                    sortable: true,
                    width: 30
                },
                {
                    header: M.util.get_string('slidetitle', 'mod_richmedia'),
                    dataIndex: 'label',
                    id: 'label',
                    sortable: true,
                    editor: new Ext.form.TextField({
                        allowBlank: false
                    })
                }, {
                    id: 'framein',
                    header: "MM:SS",
                    dataIndex: 'framein',
                    sortable: true,
                    width: 50,
                    editor: new Ext.form.TextField({
                        allowBlank: false
                    })
                }, {
                    id: 'slide',
                    header: M.util.get_string('slide', 'mod_richmedia'),
                    dataIndex: 'slide',
                    sortable: true,
                    width: '20%',
                    editor: {
                        xtype: 'combo',
                        store: new Ext.data.ArrayStore({
                            fields: [{
                                    name: 'slide'
                                }],
                            data: that.availableSlides,
                            expandData: true
                        }),
                        displayField: 'slide',
                        valueField: 'slide',
                        mode: 'local',
                        typeAhead: false,
                        triggerAction: 'all',
                        lazyRender: true,
                        emptyText: 'Select a slide',
                        listWidth: 'auto'
                    }
                }, {
                    id: 'question',
                    header: 'Question',
                    dataIndex: 'question',
                    sortable: true,
                    renderer: that.renderQuestion,
                    editor: {
                        xtype: 'combo',
                        store: new Ext.data.ArrayStore({
                            fields: ['index', 'question'],
                            data: that.questions
                        }),
                        editable: false,
                        displayField: 'question',
                        valueField: 'index',
                        mode: 'local',
                        typeAhead: false,
                        triggerAction: 'all',
                        lazyRender: true,
                        emptyText: 'Select a question',
                        listWidth: 'auto'
                    },
                    hidden: that.questions.length == 0
                }, {
                    id: 'view',
                    header: M.util.get_string('view', 'mod_richmedia'),
                    width: '15%',
                    dataIndex: 'view',
                    sortable: true,
                    renderer: that.renderView,
                    editor: {
                        xtype: 'combo',
                        store: new Ext.data.ArrayStore({
                            fields: ['view', 'display'],
                            data: [["1", M.util.get_string('defaultview', 'mod_richmedia')], ["2", M.util.get_string('presentation', 'mod_richmedia')], ["3", M.util.get_string('video', 'mod_richmedia')]]
                        }),
                        displayField: 'display',
                        valueField: 'view',
                        mode: 'local',
                        typeAhead: false,
                        triggerAction: 'all',
                        editable: false,
                        lazyRender: true
                    }
                }, {
                    id: 'actions',
                    header: M.util.get_string('actions', 'mod_richmedia'),
                    sortable: false,
                    width: 100,
                    renderer: that.renderDel
                }
            ]
        });

        this.gridsteps = new Ext.grid.EditorGridPanel({
            store: that.storesteps,
            height: 500,
            width: '100%',
            loadMask: true,
            border: true,
            clicksToEdit: 1,
            cm: that.cmsteps,
            region: 'south',
            ddGroup: 'mygrid-dd',
            enableDragDrop: true,
            autoExpandColumn: 'label',
            sm: new Ext.grid.RowSelectionModel({
                singleSelect: true,
                listeners: {
                    beforerowselect: function(sm, i, ke, row) {
                        that.gridsteps.ddText = row.data.label;
                    }
                }
            }),
            tbar: [that.addbtn, that.savebtn, that.playbtn]
        });

        this.gridsteps.on('cellclick', function(grid, row, column) {
            var slide = that.storesteps.getAt(row).data;
            if (that.isAvailable(slide.slide)) {
                Ext.get('imageDisplay').update('<div><img src="' + that.urlSlides + slide.slide + '" width="100%"/></div>');
            }
            else {
                Ext.get('imageDisplay').update('<div>' + M.util.get_string('filenotavailable', 'mod_richmedia') + '</div>');
            }
        });

        this.gridsteps.on('keydown', function(event) {
            var last = this.getSelectionModel().last;
            if (event.button == 37) {
                last--;
            }
            else if (event.button == 39) {
                last++;
            }
            if (last >= 0 && last < that.storesteps.data.length) {
                var slide = that.storesteps.getAt(last).data;
                if (that.isAvailable(slide.slide)) {
                    Ext.get('imageDisplay').update('<div style="width : 338px;height : 218px;"><img src="' + that.urlSlides + slide.slide + '" height="100%"/></div>');
                }
                else {
                    Ext.get('imageDisplay').update('<div style="width : 338px;height : 218px;">' + M.util.get_string('filenotavailable', 'mod_richmedia') + '</div>');
                }
            }
        });

        this.gridsteps.getValue = function() {
            var ret = [];
            var storestepslength = this.getStore().data.length;
            for (var i = 0; i < storestepslength; i++) {
                ret[i] = new Array(this.getStore().data.items[i].data.id,
                        this.getStore().data.items[i].data.label, this.getStore().data.items[i].data.framein, this.getStore().data.items[i].data.slide, this.getStore().data.items[i].data.question, this.getStore().data.items[i].data.view);
            }
            return ret;
        };

        this.gridsteps.on('sortchange', function() {
            for (var i = 0; i < this.getStore().data.length; i++) {
                this.getStore().getAt(i).set('id', i);
            }
        });

        this.form = new Ext.form.FormPanel({
            id: "form",
            height: 250,
            bodyStyle: 'padding : 10px;',
            width: '100%',
            border: false,
            items: [that.titlecmp, that.moviecmp, that.presenternamecmp, that.presentertitlecmp, that.fontcmp, that.color, that.defaultviewcmp, that.autoplaycmp]
        });
        var extension = getExtension(that.fileurl);
        if (extension != 'flv' && extension != 'mp4') {
            that.videoHTML = true;
            var html = '<video id="video" controls src="' + that.fileurl + '" style="width:292px;height:241px;">Vidéo non disponible</video>';
        }
        else {
            var html = '<object id="myFlash" type="application/x-shockwave-flash" data="playerflash/player.swf" width="292" height="246"> <param name="wmode" value="transparent"><param name="movie" value="playerflash/player.swf" /><param name="FlashVars" value="flv=' + that.fileurl + '&amp;showtime=1&amp;showplayer=always&amp;autoload=1" /></object>';
        }

        this.panelprincipal = new Ext.Panel({
            layout: "border",
            style: "margin : auto;",
            height: 815,
            width: '100%',
            renderTo: 'tab',
            buttonAlign: 'center',
            items: [
                that.form,
                {
                    id: 'videoDisplay',
                    html: html,
                    xtype: "panel",
                    region: 'center',
                    width: '45%',
                    height: 246,
                    border: false
                }, {
                    id: 'imageDisplay',
                    html: '',
                    xtype: 'panel',
                    region: 'east',
                    width: '45%',
                    height: 237,
                    border: false
                },
                that.gridsteps
            ],
            buttons: [that.submitbtn, that.cancelbtn]
        });

        this.ddrow = new Ext.dd.DropTarget(that.gridsteps.getView().mainBody, {
            ddGroup: 'mygrid-dd',
            notifyDrop: function(dd, e, data) {
                var sm = that.gridsteps.getSelectionModel();
                var rows = sm.getSelections();
                var cindex = dd.getDragData(e).rowIndex;
                if (sm.hasSelection()) {
                    for (i = 0; i < rows.length; i++) {
                        that.storesteps.remove(that.storesteps.getById(rows[i].id));
                        that.storesteps.insert(cindex, rows[i]);
                    }
                    sm.selectRecords(rows);
                }
                for (var i = 0; i < that.storesteps.data.length; i++) {
                    that.storesteps.getAt(i).set('id', i);
                }
            }
        });
    },
    is_string: function(input) {
        return typeof (input) == 'string';
    },
    convertTime: function(nbsecondes) {
        if (this.is_string(nbsecondes)) {
            return nbsecondes;
        }
        nbsecondes = Math.floor(nbsecondes);
        temp = nbsecondes % 3600;
        var time = [];
        time[0] = (nbsecondes - temp) / 3600;
        time[2] = temp % 60;
        time[1] = (temp - time[2]) / 60;

        if (time[1] == 0 || ($.isNumeric(time[1]) && time[1] < 10)) {
            time[1] = '0' + time[1];
        }
        if ($.isNumeric(time[2]) && time[2] < 10) {
            time[2] = '0' + time[2];
        }
        return time[1] + ':' + time[2];
    },
    isAvailable: function(slide) {
        for (key in this.availableSlides) {
            if (this.availableSlides[key] == slide) {
                return true;
            }
        }
        return false;
    },
    moveLine: function(rowIndex, nb) {
        var row = this.storesteps.getAt(rowIndex);
        this.storesteps.remove(row);
        this.storesteps.insert(rowIndex + nb, row);
        for (var i = 0; i < this.storesteps.data.length; i++) {
            this.storesteps.getAt(i).set('id', i);
        }
    },
    deleteRow: function(id) {
        var that = this;
        Ext.Msg.show({
            title: M.util.get_string('warning', 'mod_richmedia'),
            msg: M.util.get_string('confirmdeleteline', 'mod_richmedia') + id + ' ?',
            buttons: Ext.Msg.YESNO,
            icon: Ext.MessageBox.WARNING,
            fn: function(res) {
                if (res == 'yes') {
                    var storestepslength = that.storesteps.data.length;
                    for (var i = 0; i < storestepslength; i++) {
                        if (that.storesteps.getAt(i).data.id == id) {
                            that.storesteps.remove(that.storesteps.getAt(i));
                            for (var j = 0; j < that.storesteps.data.length; j++) {
                                that.storesteps.getAt(j).set('id', j);
                            }
                            break;
                        }
                    }
                    that.gridsteps.getView().refresh();
                }
            }
        });
    },
    addLine: function(time) {
        time = this.convertTime(time);
        var slidenumber = this.storesteps.data.length + 1;
        if (slidenumber > this.availableSlides.length) {
            slidenumber = this.availableSlides.length;
        }
        this.storesteps.insert(this.storesteps.data.length, new Ext.data.Record({
            'id': this.storesteps.data.length,
            'label': M.util.get_string('newline', 'mod_richmedia'),
            'framein': time,
            'slide': M.util.get_string('slide', 'mod_richmedia') + slidenumber + '.JPG',
            'question': '',
            'url': this.urlSlides
        }));
        this.storesteps.singleSort('framein', 'DESC');
        this.storesteps.singleSort('framein', 'ASC');
        this.gridsteps.getView().refresh();
    },
    getTime: function(id) {
        var time;
        if (this.videoHTML) {
            var video = document.getElementById('video');
            time = video.currentTime;
        }
        else {
            time = document.myFlash.getCurrentTime("displayCurrentTime", id);
        }
        this.displayCurrentTime(id, time);
    },
    displayCurrentTime: function(id, time) {
        if ($.isNumeric(time)) {
            time = this.convertTime(time);
            if (id != this.storesteps.data.length) {
                record = this.storesteps.getAt(id);
                record.set('framein', time);
            }
            else {
                this.addLine(time);
            }
        }
    },
    renderDel: function(value, metaData, record, rowIndex, colIndex, store) {
        var ret = '<img src = "pix/application_edit.png" title="' + M.util.get_string('gettime', 'mod_richmedia') + '" width=16px height=16px alt="edit" onclick="M.mod_richmedia_xmleditor.getTime(' + record.data.id + ');" style="cursor:pointer;" />';
        ret += '<img src = "pix/cross.png" title="' + M.util.get_string('delete', 'mod_richmedia') + '" alt="suppr" onclick="M.mod_richmedia_xmleditor.deleteRow(' + record.data.id + ');" style="cursor:pointer;" />';
        if (rowIndex != 0) {
            ret += '<img src ="pix/up.png" title="' + M.util.get_string('up', 'mod_richmedia') + '" alt="suppr" onclick="M.mod_richmedia_xmleditor.moveLine(' + rowIndex + ',-1);" style="cursor:pointer;" />';
        }
        if (rowIndex != (store.data.length - 1)) {
            ret += '<img src = "pix/down.png" title="' + M.util.get_string('down', 'mod_richmedia') + '" alt="suppr" onclick="M.mod_richmedia_xmleditor.moveLine(' + rowIndex + ',1);" style="cursor:pointer;" />';
        }

        return ret;
    },
    renderView: function(value) {
        if (value == 1) {
            return M.util.get_string('defaultview', 'mod_richmedia');
        }
        else if (value == 2) {
            return M.util.get_string('presentation', 'mod_richmedia');
        }
        else if (value == 3) {
            return M.util.get_string('video', 'mod_richmedia');
        }
        else
            return null;
    },
    renderQuestion: function(value, metaData, record) {
        if (value) {
            var question = M.mod_richmedia_xmleditor.getQuestionById(value);
            return question[1];
        }
        else {
            return '';
        }
    },
    getQuestionById: function(id) {
        for (var q in this.questions) {
            if (this.questions[q][0] == id) {
                return this.questions[q];
            }
        }
        return null;
    }
};

//Called by Flash after getTime()
displayCurrentTime = function(ln, time) {
    M.mod_richmedia_xmleditor.displayCurrentTime(ln, time);
};

//Called by Flash
addLine = function(time) {
    M.mod_richmedia_xmleditor.addLine(time);
};