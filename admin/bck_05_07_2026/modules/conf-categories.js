
$(document).ready(function() {
    $("#nav_configuration").addClass("active");
    $("#nav_configuration").addClass("open");
    $("#nav_manage_categories").addClass("active");
});

function addcategories() {

    // Reset form fields
    $("#add_categories")[0].reset();

    // Clear hidden ID (MOST IMPORTANT)
    $("#category_id").val("");

    // Reset modal title & button
    $("#modalTitle").text("Add Categories");
    $("#submit").text("Submit").prop("disabled", false);

    // Open modal
    $("#addcategories").modal("show");
}

$(document).ready(function() {

    $('#view-categories').dataTable({
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

function DeleteCategories(deleteid) {

   
    alertify.confirm('TechXpert ', 'Do you really want to delete Categories', function() {
            $.post("action/delete_categories.php", {
                    deleteid: deleteid
                },
                function(data, status) {
                    // alert(data);
                    // alert(status);
                    status = status.trim()
                    if (status == 'success') {
                        alertify.alert('TechXpert ', "Categories has been Deleted");
                        setTimeout(function() {
                            location.href = "view-categories.php";
                        }, 1000);
                        /*window.location.assign("user_dashboard.php");*/
                    } else {
                        alertify.alert(data);
                    }
                });

        },
        function() {
            alertify.error('Deletion Cancelled')
        });
}

function add_categories() {

    var categories_name = $("#categories_name").val();
    var category_type   = $("#category_type").val();

    if (categories_name === "") {
        alertify.alert("TechXpert", "Please Enter Category Name");
        return false;
    }

    if (category_type === "") {
        alertify.alert("TechXpert", "Please Select Category Type");
        return false;
    }

    $("#submitBtn").text("Submitting...").prop("disabled", true);

    $.ajax({
        url: "./action/add_categories.php",
        type: "POST",
        data: $("#add_categories").serialize(),
        success: function (data) {
            var response = JSON.parse(data);

            alertify.alert("TechXpert", response.message);

            setTimeout(function () {
                location.reload();
            }, 1500);
        }
    });

    return false;
}


function ExportCategoriesData() {
        $.ajax({
            url: "action/export_categories.php",
            type: "POST",
            data: $("#import_form").serialize(),
            success: function (data) {
            window.location.href = "report.xls";
            },
        });
        return false;
    }

function EditCategory(id, name, type) {

    $("#exampleModalLabel").text("Update Category");
    $("#submit").text("Update");

    $("#category_id").val(id);
    $("#categories_name").val(name);
    $("#category_type").val(type);

    $("#addcategories").modal("show");
}
