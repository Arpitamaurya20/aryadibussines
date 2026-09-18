
$(document).ready(function() {
    $("#nav_configuration").addClass("active");
    $("#nav_configuration").addClass("open");
    $("#nav_manage_sub_categories").addClass("active");
});

function addsubcategories() {
    $("#addsubcategories").modal();
    $("#categories").select2();
}
$(document).ready(function() {

    $('#view-subcategories').dataTable({
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

function DeleteSubCategories(deleteid) {

    //alert(deleteid);
    alertify.confirm('TechXpert ', 'Do you really want to delete Sub-Categories', function() {
            $.post("action/delete_sub_categories.php", {
                    deleteid: deleteid
                },
                function(data, status) {
                    // alert(data);
                    // alert(status);
                    status = status.trim()
                    if (status == 'success') {
                        alertify.alert('TechXpert ', "Sub-Categories has been Deleted");
                        setTimeout(function() {
                            location.href = "view-sub-categories.php";
                        }, 2000);
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

function add_subcategories() {

    var categories = document.getElementById('categories').value;

    if(categories == '-1'){
        alertify.alert("TechXpert", 'Please Select Categorie');
        return false;
    }

    var subcategories_name = document.getElementById('subcategories_name').value;

    if(subcategories_name == ''){
        alertify.alert("TechXpert", 'Please Enter Sub Categorie Name');
        return false;
    }
    document.getElementById("submit").innerHTML ="Submiting...";
    $.ajax({
        url: "./action/add_sub_categories.php",
        type: "POST",
        data: $("#add_subcategories").serialize(),
        success: function(data) {
            var response = JSON.parse(data);
            alertify.alert("TechXpert", response.message);
            setTimeout(function() {
                location.href = "view-sub-categories.php";
            }, 2000);
            return false;
        },
    });

    return false;
}

function ExportSubCategoriesData() {
    $.ajax({
        url: "action/export_subcategories.php",
        type: "POST",
        data: $("#import_form").serialize(),
        success: function (data) {
        window.location.href = "report.xls";
        },
    });
    return false;
}
