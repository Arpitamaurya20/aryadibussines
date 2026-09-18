$(document).ready(function () {
    // $('#view-cart').dataTable({
    //     responsive: true
    // });

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

function Order() {
     
  document.getElementById("order_btn").innerHTML ="Please Wait....";

  $.ajax({
    url: "action/add_order.php",
    type: "POST",
    data: $("#add_order").serialize(),
    success: function (data) {
      var response = JSON.parse(data);
      TechXAlert(response.message);
      if (response.error == false) 
      {
        setInterval(function () {
          location.reload();
        }, 2000);
      }
    },
  });
  return false;
}

function validISNumber(basic) {
   const input = event.target;
  let value = input.value;
  
  value = value.replace(/[^0-9.]/g, ''); // Remove non-numeric characters
  
  input.value = value;
}




// Variable to store the total price
let totalPrice = 0;

// Function to calculate the total price
function calculateTotal() {
  // Update the total price element
  document.getElementById("totalPrice").textContent = totalPrice.toFixed(2);
}

// Function to add a new product
function addProduct() {
  // Get the product price from the input field
  var order_id = document.getElementById("order_id").innerHTML;
  alert(order_id);  
  var productPrice = parseFloat(document.getElementById(`price_${order_id}`).innerHTML);

  alert(productPrice);

  // Add the product price to the total
  totalPrice += productPrice;

  // Calculate the new total price
  calculateTotal();

}
