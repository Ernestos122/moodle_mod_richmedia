/**
 * Js of the module
 * Author:
 * 	Adrien Jamot  (adrien_jamot [at] symetrix [dt] fr)
 * 
 * @package   mod_richmedia
 * @copyright 2011 Symetrix
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

M.mod_richmedia = {
    init: function(Y, richmediaid) {
        $(window).unload(function() {
            $.post(M.cfg.wwwroot + '/mod/richmedia/close.php', {richmediaid: richmediaid});
        });
    },
    initPlayerHTML5: function(Y, richmediainfos, audioMode) {
        Player.init(richmediainfos, audioMode);
    },
    initReport: function(Y, id, richmediaId) {
        this.id = id;
        this.richmediaId = richmediaId;
        var that = this;
        $('#checkall').on('click', function() {
            that.checkAll(true);
        });
        $('#uncheckall').on('click', function() {
            that.checkAll(false);
        });
        $('#deleterows').on('click', function() {
            that.deleteRows();
        });
    },
    deleteAll: function(joined) {
        $.post('report.php', {
            action: 'delete',
            id: this.id,
            richmediaid: this.richmediaId,
            joined: joined
        }).done(function(data) {
            window.location.reload(true);
        });
    },
    checkAll: function(bool) {
        var inputs = document.getElementsByTagName('input');
        for (var k = 0; k < inputs.length; k++) {
            var input = inputs[k];
            if (input.type === "checkbox") {
                input.checked = bool;
            }
        }
    },
    deleteRows: function() {
        var inputs = document.getElementsByTagName('input');
        var checked = new Array();
        for (var k = 0; k < inputs.length; k++) {
            var input = inputs[k];
            if (input.type == "checkbox" && input.checked) {
                checked.push(input.id);
            }
        }
        if (checked.length == 0) {
            alert(M.util.get_string('noselectedline', 'mod_richmedia'));
        }
        else {
            var joined = checked.join(',');
            this.deleteAll(joined);
        }
    },
    initThemeManager: function() {
        this.storetheme;
        this.cancelbtn;
        this.addbtn;
        this.cmsteps;
        this.gridtheme;
        this.panelprincipal;
        var that = this;

        function renderDel(value, metaData, record, rowIndex, colIndex, store) {
            ret = '<img src = "' + M.cfg.wwwroot + '/mod/richmedia/pix/cross.png" alt = "suppr" onclick="M.mod_richmedia.deleteRow(' + record.data.id + ');"/><img src = "' + M.cfg.wwwroot + ' /mod/richmedia/pix/image_add.png" alt = "edit" onclick="M.mod_richmedia.editRow(' + record.data.id + ');"/>';
            return ret;
        }

        this.storetheme = new Ext.data.JsonStore({
            fields: [{name: 'nom', type: 'string'}, {name: 'logo', type: 'string'}, {name: 'background', type: 'string'}, {name: 'id', type: 'int'}],
            url: 'save_theme.php?store=1'
        });
        this.storetheme.load();
        cancelbtn = new Ext.Button({
            text: M.util.get_string('return', 'mod_richmedia')
        });
        cancelbtn.on('click', function() {
            history.go(-1);
        });
        addbtn = new Ext.Button({
            text: M.util.get_string('addtheme', 'mod_richmedia')
        });
        addbtn.on('click', function() {
            var panelUpload = new Ext.form.FormPanel({
                fileUpload: true,
                width: 450,
                height: 180,
                bodyStyle: 'padding: 10px 10px 10px 10px;',
                labelWidth: 50,
                defaults: {
                    anchor: '95%',
                    allowBlank: false,
                    msgTarget: 'side'
                },
                items: [
                    {
                        xtype: 'textfield',
                        name: 'nom',
                        fieldLabel: M.util.get_string('name', 'mod_richmedia'),
                        vtype: 'alphanum'
                    }, {
                        xtype: 'panel',
                        border: false,
                        html: '<label for="logoupload">' + M.util.get_string('logo', 'mod_richmedia') + ' :</label><input id="logoupload" name="logoupload" type="file" size="50" maxlength="100000">'
                    }, {
                        xtype: 'panel',
                        border: false,
                        html: '<label for="backgroundupload">' + M.util.get_string('fond', 'mod_richmedia') + ' :</label><input id="backgroundupload" name="backgroundupload" type="file" size="50" maxlength="100000">'
                    }
                ]
            });
            fenetreImport = new Ext.Window({
                title: M.util.get_string('themeimport', 'mod_richmedia'),
                closeAction: 'close',
                layout: 'fit',
                resizable: false,
                height: 180,
                width: 500,
                items: [panelUpload],
                buttonAlign: 'center',
                buttons: [{
                        text: 'OK',
                        handler: function() {
                            panelUpload.getForm().submit({
                                url: "save_theme.php?upload=1",
                                waitTitle: M.util.get_string('wait', 'mod_richmedia'),
                                timeout: 3500000,
                                waitMsg: M.util.get_string('currentsave', 'mod_richmedia'),
                                success: function(obj, action) {
                                    Ext.Msg.show({
                                        title: M.util.get_string('success', 'mod_richmedia'),
                                        msg: M.util.get_string('importdone', 'mod_richmedia'),
                                        buttons: Ext.Msg.OK
                                    });
                                    delete that.storetheme.lastParams;
                                    that.storetheme.reload();
                                    fenetreImport.close();
                                },
                                failure: function(form, action) {
                                    Ext.Msg.show({
                                        title: M.util.get_string('error', 'mod_richmedia'),
                                        msg: action.result.msg.reason,
                                        buttons: Ext.Msg.OK
                                    });
                                }
                            });
                        }
                    }, {
                        text: M.util.get_string('cancel', 'mod_richmedia'),
                        handler: function() {
                            fenetreImport.close();
                        }
                    }]
            });
            fenetreImport.show();
        });
        cmsteps = new Ext.grid.ColumnModel({
            defaults: {
                sortable: true
            },
            columns: [
                {
                    header: M.util.get_string('name', 'mod_richmedia'),
                    dataIndex: 'nom',
                    sortable: true,
                    width: 150
                },
                {
                    header: M.util.get_string('logo', 'mod_richmedia'),
                    dataIndex: 'logo',
                    sortable: true,
                    width: 130
                },
                {
                    header: M.util.get_string('fond', 'mod_richmedia'),
                    dataIndex: 'background',
                    sortable: true,
                    width: 200
                }, {
                    header: M.util.get_string('actions', 'mod_richmedia'),
                    sortable: true,
                    width: 60,
                    renderer: renderDel
                }
            ]
        });
        gridtheme = new Ext.grid.EditorGridPanel({
            store: this.storetheme,
            height: 380,
            width: 570,
            loadMask: true,
            border: true,
            clicksToEdit: 2,
            cm: cmsteps
        });
        panelprincipal = new Ext.Panel({
            style: "margin : auto;margin-top : 50px;",
            layout: 'fit',
            autoHeight: true,
            width: 570,
            renderTo: 'tab',
            buttonAlign: 'center',
            items: [gridtheme],
            buttons: [cancelbtn, addbtn]
        });
    },
    editRow: function(id) {
        var that = this;
        var the = this.storetheme.getById(id);
        var panelEdit = new Ext.form.FormPanel({
            fileUpload: true,
            width: 450,
            height: 200,
            bodyStyle: 'padding: 10px 10px 10px 10px;',
            labelWidth: 50,
            defaults: {
                anchor: '95%',
                allowBlank: false,
                msgTarget: 'side'
            },
            items: [
                {
                    xtype: 'hidden',
                    name: 'anciennom',
                    value: the.data.nom
                }, {
                    xtype: 'textfield',
                    name: 'nom',
                    fieldLabel: M.util.get_string('name', 'mod_richmedia'),
                    vtype: 'alphanum',
                    value: the.data.nom
                }, {
                    xtype: 'panel',
                    border: false,
                    html: M.util.get_string('logo', 'mod_richmedia') + ':&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input name="logoupload" type="file" size="50" maxlength="100000">'
                }, {
                    xtype: 'panel',
                    border: false,
                    html: M.util.get_string('fond', 'mod_richmedia') + ':&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input name="backgroundupload" type="file" size="50" maxlength="100000">'
                }
            ]
        });
        fenetreEdit = new Ext.Window({
            title: M.util.get_string('themeedition', 'mod_richmedia'),
            closeAction: 'close',
            layout: 'fit',
            resizable: false,
            height: 180,
            width: 500,
            items: [panelEdit],
            buttonAlign: 'center',
            buttons: [{
                    text: 'OK',
                    handler: function() {
                        panelEdit.getForm().submit({
                            url: "save_theme.php?edit=1",
                            waitTitle: M.util.get_string('wait', 'mod_richmedia'),
                            timeout: 3500000,
                            waitMsg: M.util.get_string('currentsave', 'mod_richmedia'),
                            success: function(obj, action) {
                                Ext.Msg.show({
                                    title: M.util.get_string('success', 'mod_richmedia'),
                                    msg: M.util.get_string('importdone', 'mod_richmedia'),
                                    buttons: Ext.Msg.OK
                                });
                                delete that.storetheme.lastParams;
                                that.storetheme.reload();
                                fenetreEdit.close();
                            },
                            failure: function(form, action) {
                                Ext.Msg.show({
                                    title: M.util.get_string('error', 'mod_richmedia'),
                                    msg: action.result.msg.reason,
                                    buttons: Ext.Msg.OK
                                });
                            }
                        });
                    }
                }, {
                    text: M.util.get_string('cancel', 'mod_richmedia'),
                    handler: function() {
                        fenetreEdit.close();
                    }
                }]
        });
        fenetreEdit.show();
    },
    deleteRow: function(id) {
        var that = this;
        var the = this.storetheme.getById(id);
        Ext.Msg.show({
            title: M.util.get_string('warning', 'mod_richmedia'),
            msg: M.util.get_string('removetheme', 'mod_richmedia') + ' ' + the.data.nom + ' ?',
            buttons: Ext.Msg.YESNO,
            fn: function(btn) {
                if (btn == 'yes') {
                    Ext.Ajax.request({
                        url: 'save_theme.php?delete=1'
                        , method: 'POST'
                        , params: {
                            nom: the.data.nom
                        }
                        , success: function(result, request) {
                            if (result.responseText == 1) {
                                Ext.Msg.show({
                                    title: M.util.get_string('information', 'mod_richmedia'),
                                    msg: M.util.get_string('deletedtheme', 'mod_richmedia'),
                                    buttons: Ext.Msg.OK
                                });
                                delete that.storetheme.lastParams;
                                that.storetheme.reload();
                            }
                        }
                    });
                }
            }
        });
    },
    setModForm: function() {
        $('#id_fontcolor').iris();
        $('#editsync').click(function() {
            var frm = document.forms['mform1'];
            _qfMsg = '';
            _qfMsg = _qfMsg + '\n - ' + M.util.get_string('required', 'moodle');
            var myValidator = validate_mod_richmedia_mod_form;
            if (myValidator(frm)) {
                frm.action = M.cfg.wwwroot + '/mod/richmedia/modedit.php';
                frm.submit();
            }
            frm.action = "modedit.php";
        });
    }
};