CKEDITOR.dialog.add("addBlockDialog", function(editor) {
    return {
        title: "Add Block",
        minWidth: 200,
        minHeight: 60,
        contents: [
            {
                id: "tab-basic",
                elements: [
//                    {
//                        id: "loading",
//                        type: "html",
//                        html: "<img src=\"/bundles/wicrewblock/img/loading.gif\" alt=\"\" />"
//                    },
                    {
                        id: "block",
                        type: "select",
                        label: "Block",
                        items: [],
//                        default: "",
                        validate: CKEDITOR.dialog.validate.notEmpty("Please select a block."),
                        onContentLoad : function(api) {},
                        onChange: function(api) {}
                    }
                ]
            }
        ],
        onLoad: function() {
            let dialog = this;
            var inputSelect = dialog.getContentElement("tab-basic", "block");
            inputSelect.add("", "");
            jQuery.ajax({
                type: "GET",
                url: "/api/blocks",
//                data: {},
                dataType: "json",
                success: function(data) {
                    data.forEach(function(row) {
                        inputSelect.add(row[0], row[1]);
                    });
                }
            });
        },
        onShow: function() {},
        onOk: function() {
            let dialog = this;
            let identifier = dialog.getValueOf("tab-basic", "block");
            if (identifier.length > 0) {
                editor.insertHtml("{{ block identifier=\"" + identifier + "\" }}");
            }
        }
    };
});
