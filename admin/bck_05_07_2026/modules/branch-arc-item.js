 $(document).ready(function() {
        $("#nav_corporate").addClass("open");
        $("#nav_corporate").addClass("active");
        $("#nav_branch").addClass("active");
    });

 $(document).ready(function() {

        $('#view-branch-arc').dataTable({
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

 function DeleteARCItem(delete_id) {
            alertify.confirm('TechXpert ', 'Do you really want to delete Branch ARC Item.', function() {
                    $.post("action/delete_arc_item.php", {
                            ID: delete_id
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

 
  function UpdateBranchARC(ID) {
  
  $.post(
    "action/get_branch_arc_details.php",
    {
      ID: ID,
    },
    function (data, status) {
      var response = JSON.parse(data);
      if (response.error == false) {
        var arc_price = response.data.Price;
        var arc_id = response.data.ID;
        $("#arc_price").val(arc_price);
        $("#branch_arc_id").val(arc_id);
        
      }
    }
  );
  $("#edit_branch_arc").modal();
}

function AddtoCart_modal(productid,categorieid,price,branchid){
       
       $("#add_arc_id").val(productid);
       $("#add_arc_category").val(categorieid);
       $("#add_arc_price").val(price);
       $("#add_branch_id").val(branchid);
       $("#add_to_cart").modal();
}      


function AddUpdateBranch() {
 
  document.getElementById("branch_arc_btn").innerHTML ="Please Wait....";

  $.ajax({
    url: "action/update_action.php",
    type: "POST",
    data: $("#branch_arc_update").serialize(),
    success: function (data) {
      var response = JSON.parse(data);
      TechXAlert(response.message);
      if (response.error == false) 
      {
        setInterval(function () {
          location.reload();
        }, 2000);
      }
      document.getElementById("branch_btn").innerHTML ="Submit";
    },
  });
  return false;
}

function AddToCart() {

  document.getElementById("add_to_cart_btn").innerHTML ="Please Wait....";

  $.ajax({
    url: "action/add_to_cart.php",
    type: "POST",
    data: $("#add_to_cart_form").serialize(),
    success: function (data) {
      var response = JSON.parse(data);
      TechXAlert(response.message);
      if (response.error == false) 
      {
        setInterval(function () {
          location.reload();
        }, 2000);
      }
      document.getElementById("add_to_cart_btn").innerHTML ="Add To Cart";
    },
  });
  return false;
}


function OpenCSVmodal(){
       $("#upload_csv").modal();
}

// function UploadBranchARC_CSV() {

//   document.getElementById("upload_csv_btn").innerHTML ="Please Wait....";

//   $.ajax({
//     url: "action/upload_csv.php",
//     type: "POST",
//     data: $("#uplaod_branch_arc_csv").serialize(),
//     success: function (data) {
//       var response = JSON.parse(data);
//       TechXAlert(response.message);
//       if (response.error == false) 
//       {
//         setInterval(function () {
//           location.reload();
//         }, 2000);
//       }
//     },
//   });
//   return false;
// }

function UploadBranchARC_CSV() {
  let myForm = document.getElementById("uplaod_branch_arc_csv"); 
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

function Exportbranch_arcData() {
  $.ajax({
      url: "action/export_branch_arc.php",
      type: "POST",
      data: $("#import_form").serialize(),
      success: function (data) {
          window.location.href = "report.xls";
      },
  });
  return false;
}

function Downloadbranch_arcFileFormat(){
  // Replace 'file_url' with the URL of the file you want to download
  var file_url = 'http://localhost/Projects/techxpertindia/admin/branch-arc-items/branch_arc.xlsx';
  
  // Create a new anchor element
  var link = document.createElement('a');
  
  // Set the href attribute to the file URL
  link.href = file_url;
  
  // Set the download attribute to the file name
  link.setAttribute('download', 'branch_arc.xlsx');
  
  // Simulate a click on the anchor element to initiate the download
  link.click();
}

