$(document).ready(function () {
    $("#nav_configuration").addClass("active");
    $("#nav_configuration").addClass("open");
    $("#nav_arc").addClass("active");
    $('#view-arc').dataTable({
        responsive: true
    });

    $('.js-thead-colors a').on('click', function () {
        var theadColor = $(this).attr("data-bg");
        console.log(theadColor);
        $('#dt-basic-example thead').removeClassPrefix('bg-').addClass(theadColor);
    });

    $('.js-tbody-colors a').on('click', function () {
        var theadColor = $(this).attr("data-bg");
        console.log(theadColor);
        $('#dt-basic-example').removeClassPrefix('bg-').addClass(theadColor);
    });

});

function openARC() {
    $("#arc_modal_title").html("Add ARC Items");
    $("#add_update_arc_form")[0].reset();
    $("#form_action").val("add");
    $("#add_edit_arc_modal").modal();
    $("#item_categories").select2();
    $("#item_uom").select2();
}

function UpdateARC_modal(arc_id) {
    $("#arc_modal_title").html("Update ARC Items");
    $.post("action/get_arc_details.php", {
        ID: arc_id
    },
        function (data, status) {
            var response = JSON.parse(data);
            if (response.error == false) {
                var Item_name = response.data.ItemName;
                var Item_decs = response.data.ItemDescription;
                var Item_price = response.data.ItemPrice;
                var Item_code = response.data.ItemCode;
                var Item_categories = response.data.ItemCategories;
                var Item_UOM = response.data.ItemUOM;

                $("#item_name").val(Item_name);
                $("#item_decs").val(Item_decs);
                $("#item_price").val(Item_price);
                $("#item_code").val(Item_code);
                $("#item_categories").val(Item_categories);
                $("#item_uom").val(Item_UOM);
                $("#form_action").val("Update");
                $("#form_id").val(arc_id);
                $("#add_edit_arc_modal").modal();
                $("#item_categories").select2();
                $("#item_uom").select2();
            }
        });

}

function DeleteARCAssets(arc_id) {
    alertify.confirm('TechXpert ', 'Do you really want to delete ARC Item', function () {
        $.post("action/delete_arc.php", {
            ID: arc_id
        },
            function (data, status) {
                var response = JSON.parse(data);
                TechXAlert(response.message);
                if (response.error == false) {
                    setInterval(function () {
                        location.reload();
                    }, 2000);
                }
            });

    },
        function () {
            alertify.error('Deletion Cancelled')
        });
}

function AddUpdateARC() {
     var item_name = document.getElementById("item_name").value;

    if(item_name == ""){
            TechXAlert("Please Enter Item  Name");
            return false;
        }

    var item_price = document.getElementById("item_price").value;

    if(item_price == ""){
            TechXAlert("Please Enter Item Price");
            return false;
        }

    var item_categories = document.getElementById("item_categories").value;

    if(item_categories == "-1"){
            TechXAlert("Please Enter Item Category");
            return false;
        }

    var item_uom = document.getElementById("item_uom").value;

    if(item_uom == "-1"){
            TechXAlert("Please Enter Item UOM");
            return false;
        }

        

    let myForm = document.getElementById("add_update_arc_form");
    var formData = new FormData(myForm);
    $.ajax({
        url: "action/add_update_arc.php",
        type: "POST",
        data: formData,
        success: function (data) {
            var response = JSON.parse(data);
            TechXAlert(response.message);
            if (response.error == false) {
                setInterval(function() {
                    location.reload();
                }, 2000);
            }
        },
        cache: false,
        contentType: false,
        processData: false,
    });
    return false;
}

function validISNumber(basic) {
   const input = event.target;
  let value = input.value;
  
  value = value.replace(/[^0-9.]/g, ''); // Remove non-numeric characters
  
  input.value = value;
}

function ExportARCData() {
        $.ajax({
            url: "action/export_arc.php",
            type: "POST",
            data: $("#import_form").serialize(),
            success: function (data) {
            window.location.href = "report.xls";
            },
        });
        return false;
    }