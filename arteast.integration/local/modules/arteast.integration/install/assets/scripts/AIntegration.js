$(document).ready(function ()
{
    const REQUEST_URL = "/bitrix/admin/arteast.integration_import.php";

    var loaderHTML;

    GetImportStatus("Y");

    $(".import-categories").on("click", function () {
        $(this).prop("disabled", true);
        $(".import-products").prop("disabled", true);
        $(".import-attributes").prop("disabled", true);
        $(".import-multicategory").prop("disabled", true);
        GetInfoMessage(4, "CategoriesProcessing"); // Start processing message
    });

    $(".import-products").on("click", function () {
        $(this).prop("disabled", true);
        $(".import-categories").prop("disabled", true);
        $(".import-attributes").prop("disabled", true);
        $(".import-multicategory").prop("disabled", true);
        GetInfoMessage(0, "ProductsProcessing"); // Start processing message
    });

    $(".import-attributes").on("click", function () {
        $(this).prop("disabled", true);
        $(".import-categories").prop("disabled", true);
        $(".import-products").prop("disabled", true);
        $(".import-multicategory").prop("disabled", true);
        GetInfoMessage(7, "AttributesProcessing"); // Start processing message
    });

    $(".import-multicategory").on("click", function () {
        $(this).prop("disabled", true);
        $(".import-categories").prop("disabled", true);
        $(".import-products").prop("disabled", true);
        $(".import-attributes").prop("disabled", true);
        GetInfoMessage(7, "MulticategoryProcessing"); // Start processing message
    });

    function GetInfoMessage(message_id, event, loader = "N") {
        $.getJSON(REQUEST_URL + "?request=message&message_id=" + message_id, function (data) {
            if(loader == "Y")
            {
                loaderHTML = "<div class='loader'></div>";
            }else{
                loaderHTML = "";
            }
            $(".responseMessage").html("<div class='adm-info-message'><div class='info-message'>" + loaderHTML + "<div><div><b>" + data.title + "</b></div><div>" + data.message + "</div><div>" + data.page + "</div></div></div></div>");

            if (event == "CategoriesProcessing") {
                CategoriesProcessing();
            }

            if (event == "ProductsProcessing") {
                ProductsProcessing();
            }

            if (event == "AttributesProcessing") {
                AttributesProcessing();
            }

            if (event == "MulticategoryProcessing") {
                MulticategoryProcessing();
            }
        });
    }

    function CategoriesProcessing(){
        $.getJSON(REQUEST_URL + "?event=CategoriesProcessing", function (data) {
            // Start event
        });

        setTimeout(function() {
            GetImportStatus();
        }, 10000);
    }

    function ProductsProcessing(){
        $.getJSON(REQUEST_URL + "?event=ProductsProcessing", function (data) {
            // Start event
        });

        setTimeout(function() {
            GetImportStatus();
        }, 10000);
    }

    function AttributesProcessing(){
        $.getJSON(REQUEST_URL + "?event=AttributesProcessing", function (data) {
            // Start event
        });

        setTimeout(function() {
            GetImportStatus();
        }, 10000);
    }

    function MulticategoryProcessing(){
        $.getJSON(REQUEST_URL + "?event=MulticategoryProcessing", function (data) {
            // Start event
        });

        setTimeout(function() {
            GetImportStatus();
        }, 10000);
    }

    function GetImportStatus(checker = "N"){
        $.getJSON(REQUEST_URL + "?request=GetImportStatus", function (data) {
            if (data.status == "processing" || data.status == "connection")
            {
                if(data.type == "products"){
                    GetInfoMessage(1, "none", "Y"); // Processing message
                }
                else if(data.type == "categories"){
                    GetInfoMessage(5, "none", "Y"); // Processing message
                }
                else if(data.type == "attributes"){
                    GetInfoMessage(8, "none", "Y"); // Complete message
                }
                else if(data.type == "connection"){
                    GetInfoMessage(10, "none"); // Complete message
                }
                else if(data.type == "multicategory"){
                    GetInfoMessage(12, "none", "Y"); // Complete message
                }

                setTimeout(function() {
                    GetImportStatus();
                }, 10000);

                $(".import-categories").prop("disabled", true);
                $(".import-products").prop("disabled", true);
                $(".import-attributes").prop("disabled", true);
                $(".import-multicategory").prop("disabled", true);
            }
            else if (data.status == "complete" || data.status == "lost")
            {
                if(data.type == "products"){
                    GetInfoMessage(2, "none"); // Complete message
                }
                else if(data.type == "categories"){
                    GetInfoMessage(6, "none"); // Complete message
                }
                else if(data.type == "attributes"){
                    GetInfoMessage(9, "none"); // Complete message
                }
                else if(data.type == "lost"){
                    GetInfoMessage(11, "none"); // Complete message
                }
                else if(data.type == "multicategory"){
                    GetInfoMessage(13, "none"); // Complete message
                }

                $(".import-categories").removeAttr("disabled");
                $(".import-products").removeAttr("disabled");
                $(".import-attributes").removeAttr("disabled");
                $(".import-multicategory").removeAttr("disabled");
            }
        }).fail(function(){
            if(checker == "N") {
                GetInfoMessage(3, "none"); // Error message
                $(".import-products").removeAttr("disabled");
                $(".import-categories").removeAttr("disabled");
                $(".import-attributes").removeAttr("disabled");
                $(".import-multicategory").removeAttr("disabled");
            }
        });
    }
});