
$(document).ready(function() {
    $("#nav_configuration").addClass("active");
    $("#nav_configuration").addClass("open");
    $("#nav_custoumer_rating").addClass("active");
    $('#view-customer-rating').dataTable({
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
function DeleteCustomerRating(deleteid) {

    //alert(deleteid);
    alertify.confirm('TechXpert ', 'Do you really want to delete Customer Rating', function() {
            $.post("action/delete_customer_rating.php", {
                    ID: deleteid
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

function ExportCustomerRatingData() {
        $.ajax({
            url: "action/export_customer_rating.php",
            type: "POST",
            data: $("#import_form").serialize(),
            success: function (data) {
            window.location.href = "report.xls";
            },
        });
        return false;
    }
