CKEDITOR.plugins.add('addBlock', {
    init: function (editor) {
        var pluginName = 'addBlock';
        var mypath = this.path;

        CKEDITOR.dialog.add("addBlockDialog", "/bundles/wicrewblock/javascript/dialogs/add-block.js");
        editor.addCommand("addBlock", new CKEDITOR.dialogCommand("addBlockDialog"));

        editor.ui.addButton("AddBlock", {
            label: "Add block",
            command: "addBlock",
            toolbar: "insert",
            icon: "/bundles/wicrewblock/img/wall.png"
        });
    }
});
