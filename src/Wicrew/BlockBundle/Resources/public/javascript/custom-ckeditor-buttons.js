jQuery('document').ready(function() {
    if (typeof CKEDITOR == "object" && typeof CKEDITOR.instances == "object") {
        const editorKeys = Object.keys(CKEDITOR.instances);
        for (const editorKey of editorKeys) {
            if (CKEDITOR.instances[editorKey]) {
                CKEDITOR.instances[editorKey].destroy(true);
                delete CKEDITOR.instances[editorKey];
            }

            // Bind editor
            var editor = CKEDITOR.replace(editorKey);

            // Create named command
//            editor.addCommand("addBlock", {
//                exec: function(edt) {
//                    edt.insertHtml("{{ block indentifier=\"\" }}");
//                }
//            });
            CKEDITOR.dialog.add("addBlockDialog", "/bundles/wicrewblock/javascript/dialogs/add-block.js");
            editor.addCommand("addBlock", new CKEDITOR.dialogCommand("addBlockDialog"));

            // Add new button and bind our command
            editor.ui.addButton("AddBlock", {
                label: "Add block",
                command: "addBlock",
                toolbar: "insert",
                icon: "/bundles/wicrewblock/img/wall.png"
            });
        }
    }
});
