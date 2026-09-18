<footer class="page-footer" role="contentinfo">

    <div class="text-center text-muted w-100">
        <!-- <ul class="list-table m-0">
            <li><a href="#" class="text-secondary fw-700">About</a></li>
            <li class="pl-3"><a href="#" class="text-secondary fw-700">Documentation</a></li>

        </ul> -->
        <p class="m-0">© 
            <script>
                document.write(new Date().getFullYear())
            </script> 
            <?php 
            if($ProductName == "TechXpert")
            {
                ?>
                 <span>Techxpert Facilities India Private Limited. All rights reserved.</span>
                <?php
            }
            else
            {
                ?>
                <span><?php echo $ProductName;?> All rights reserved.</span>
                <?php
            }
            ?>
           
        </p>
    </div>
</footer>