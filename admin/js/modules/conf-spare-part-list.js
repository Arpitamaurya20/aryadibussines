$(document).ready(function() 
    {
        $("#nav_configuration").addClass("active");
        $("#nav_configuration").addClass("open");
        $("#nav_spare_part_list").addClass("active");
        $('#view-spare-part').dataTable({
            responsive: true
        });

        $('.js-thead-colors a').on('click', function() {
            var theadColor = $(this).attr("data-bg");
            console.log(theadColor);
            $('#dt-basic-example thead').removeClassPrefix('bg-').addClass(theadColor);
        });

        $('.js-tbody-colors a').on('click', function() {
            var theadColor = $(this).attr("data-bg");
            console.log(theadColor);
            $('#dt-basic-example').removeClassPrefix('bg-').addClass(theadColor);
        });

    });
function OpenSparePart_modal() {
    $("#add_update_spare_part_form")[0].reset();
    $("#form_action").val("add");

    $("#add_edit_spare_part_modal").modal();
    $("#categories").select2();
    $("#uom").select2();

}
function UpdateSparePart_modal(spare_part_id)
{
    $.post("action/get_spare_part_details.php", {
        ID: spare_part_id
    },
    function(data, status) {
        var response = JSON.parse(data);
        if(response.error == false)
        {
            var spare_part = response.data.SparePart;
            var categories = response.data.Categories;
            var uom = response.data.UOM;
            var price = response.data.Price;
            $("#spare_part").val(spare_part);
            $("#categories").val(categories);
            $("#uom").val(uom);
            $("#price").val(price);
            $("#form_action").val("Update");
            $("#form_id").val(spare_part_id);
            $("#categories").select2();
            $("#uom").select2();

        }
    });
    $("#add_edit_spare_part_modal").modal();
}

function DeleteSparePart(spare_part_id) {
    alertify.confirm('TechXpert ', 'Do you really want to delete Spare Part', function() {
            $.post("action/delete_spare_part.php", {
                    ID: spare_part_id
                },
                function(data, status) {
                    var response = JSON.parse(data);
                    TechXAlert(response.message);
                    if (response.error == false)
                    {
                      setInterval(function(){
                        location.reload();
                      }, 2000);
                    }
                });
        },
        function() {
            alertify.error('Deletion Cancelled')
        });
}

function AddUpdateSparePart() {
    var SparePart = document.getElementById("spare_part").value;
    var Category = document.getElementById("categories").value;
    var UOM = document.getElementById("uom").value;
    if (SparePart == "") {
      TechXAlert("Spare Part cannot be blank");
      return false;
    }
    if (Category == -1) {
        TechXAlert("Category cannot be blank");
        return false;
    }
    if (UOM == -1) {
        TechXAlert("UOM cannot be blank");
        return false;
    }
    document.getElementById("submit").innerHTML ="Submiting....";
    $.ajax({
        url: "action/add_update_spare_part.php",
        type: "POST",
        data: $("#add_update_spare_part_form").serialize(),
        success: function(data) {
            var response = JSON.parse(data);
            TechXAlert(response.message);
            if (response.error == false)
            {
              setInterval(function(){
                location.reload();
              }, 2000);
            }
        },

    });
    return false;
}
  
function validPriceNumber(price) {
   const input = event.target;
  let value = input.value;
  value = value.replace(/[^0-9.]/g, ''); // Remove non-numeric characters
  input.value = value;
}

function ExportSparePartData() {
        $.ajax({
            url: "action/export_spare_part.php",
            type: "POST",
            data: $("#import_form").serialize(),
            success: function (data) {
            window.location.href = "report.xls";
            },
        });
        return false;
    }
function OpenCSVmodal(){
       $("#upload_csv").modal();
}

function UploadSpareParts_CSV() {
  let myForm = document.getElementById("uplaod_branch_csv"); 
    var formData = new FormData(myForm);
    $.ajax({
        url: "action/upload_csv.php",
        type: "POST",
        data: formData,
        success: function (data) {
            var response = JSON.parse(data);
            //TechXAlert(response.message);
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
