<div class="panel-container show">
   <div class="panel-content">
       <ul class="nav nav-tabs mb-3" id="branchStatusTabs" role="tablist">
           <li class="nav-item">
               <a class="nav-link active" id="active-branches-tab" data-toggle="tab" href="#active-branches-pane" role="tab" aria-controls="active-branches-pane" aria-selected="true">Active Branches</a>
           </li>
           <li class="nav-item">
               <a class="nav-link" id="inactive-branches-tab" data-toggle="tab" href="#inactive-branches-pane" role="tab" aria-controls="inactive-branches-pane" aria-selected="false">Inactive Branches</a>
           </li>
       </ul>

       <div class="tab-content">
           <div class="tab-pane fade show active" id="active-branches-pane" role="tabpanel" aria-labelledby="active-branches-tab">
               <table id="view-branch-active" class="table table-bordered table-hover table-striped w-100">
                   <thead>
                       <tr>
                           <th>#</th>
                           <th>Company Account</th>
                           <th>Branch Name</th>
                           <th>Mobile / Alternate / Email</th>
                           <th>City / State</th>
                           <th>Branch Assets</th>
                           <th>Spare Part</th>
                           <th>Access</th>
                           <?php if($UserType == "Admin" || $corporate_account_admin || $TicketManager){ ?>
                               <th>Update</th>
                               <?php if(true){ ?>
                                   <th>Action</th>
                               <?php } ?>
                           <?php } ?>
                           <th>Site Incharge</th>
                       </tr>
                   </thead>
               </table>
           </div>

           <div class="tab-pane fade" id="inactive-branches-pane" role="tabpanel" aria-labelledby="inactive-branches-tab">
               <table id="view-branch-inactive" class="table table-bordered table-hover table-striped w-100">
                   <thead>
                       <tr>
                           <th>#</th>
                           <th>Company Account</th>
                           <th>Branch Name</th>
                           <th>Mobile / Alternate / Email</th>
                           <th>City / State</th>
                           <th>Branch Assets</th>
                           <th>Spare Part</th>
                           <th>Access</th>
                           <?php if($UserType == "Admin" || $corporate_account_admin || $TicketManager){ ?>
                               <th>Update</th>
                               <?php if(true){ ?>
                                   <th>Action</th>
                               <?php } ?>
                           <?php } ?>
                           <th>Site Incharge</th>
                       </tr>
                   </thead>
               </table>
           </div>
       </div>
   </div>
</div>