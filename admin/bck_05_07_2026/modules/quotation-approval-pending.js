
function addcategories() {
    $("#addcategories").modal();
}
$(document).ready(function() {
     $("#nav_ticket_quotation_approval").addClass("active");
    $('#view-quotation-approval-pending').dataTable({
        responsive: true
    });

    $('.js-thead-colors a').on('click', function() {
        var theadColor = $(this).attr("data-bg");
        console.log(theadColor);
        $('#view-quotation-approval-pending').removeClassPrefix('bg-').addClass(theadColor);
    });

    $('.js-tbody-colors a').on('click', function() {
        var theadColor = $(this).attr("data-bg");
        console.log(theadColor);
        $('#view-quotation-approval-pending').removeClassPrefix('bg-').addClass(theadColor);
    });

});




