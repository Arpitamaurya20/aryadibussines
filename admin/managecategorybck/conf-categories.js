
$(document).ready(function() {
    $("#nav_configuration").addClass("active");
    $("#nav_configuration").addClass("open");
    $("#nav_manage_categories").addClass("active");
});

function addcategories() {
    $("#addcategories").modal();
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

    //alert(deleteid);
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

    var categories_name = document.getElementById('categories_name').value;

    if(categories_name == ""){
        alertify.alert("TechXpert", "Please Enter Categorie Name");
        return false;
    }
  document.getElementById("submit").innerHTML ="Submiting....";

    $.ajax({
        url: "./action/add_categories.php",
        type: "POST",
        data: $("#add_categories").serialize(),
        success: function(data) {
            var response = JSON.parse(data);
            alertify.alert("TechXpert", response.message);
            setTimeout(function() {
                location.href = "view-categories.php";
            }, 2000);
            return false;
        },
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
