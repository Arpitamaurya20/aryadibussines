<?php 
$site_visit_observations = $site_visits_obj->GetSiteObservations($SiteVisitID);

foreach($site_visit_observations as $site_visit_observation)
{
	$image_observation_media = array();
	foreach($site_visit_observation['observation_media'] as $observation_media)
    {
       
        $compressed_image_path = '../media/site_visits/compressed_' . $observation_media['Image'];

        $imageDate=$observation_media['CreatedDate'];
        $imageTime=$observation_media['CreatedTime'];
        
        // Compress the image
        
        $image_observation_media[] = '
    <div class="col-md-3">
        <div style="" class="product_attachment">
            <img style="width: 100px;" class="mt-3" src="'.$compressed_image_path.'" alt="">
            <div class="mt-2" style="font-size: 10px; justify-content: space-between;">
                <span style="font-size: 8px;">'.$imageDate.'</span>
                <span style="font-size: 8px;">'.$imageTime.'</span>
            </div>
        </div>
    </div>
';
    }
	?>
	<div class="card mb-g border shadow-0">
    <div class="card-header bg-white">
        <div class="row no-gutters align-items-center">
            <div class="col">
                <span class="h6 font-weight-bold text-uppercase">Observation</span>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <!-- Full-width sections -->
        <div class="row no-gutters">
            <div class="col-12">
                <div class="p-3">
                    <h5 class="text-bold text-italic">Category</h5>
                    <p><?php echo $site_visit_observation['Category']; ?></p>
                </div>
            </div>
        </div>
        <div class="row no-gutters">
            <div class="col-12">
                <div class="p-3">
                    <h5 class="text-bold text-italic">Priority</h5>
                    <p><?php echo $site_visit_observation['Priority']; ?></p>
                </div>
            </div>
        </div>
        <div class="row no-gutters">
            <div class="col-12">
                <div class="p-3">
                    <h5 class="text-bold text-italic">Observation</h5>
                    <p><?php echo $site_visit_observation['Observation']; ?></p>
                </div>
            </div>
        </div>
        <div class="row no-gutters">
            <div class="col-12">
                <div class="p-3">
                    <h5 class="text-bold text-italic">Company Recommendation</h5>
                    <p><?php echo $site_visit_observation['CompanyRecommendation']; ?></p>
                </div>
            </div>
        </div>
        <div class="row no-gutters">
            <div class="col-12">
                <div class="p-3">
                    <h5 class="text-bold text-italic">Client Recommendation</h5>
                    <p><?php echo $site_visit_observation['ClientRecommendation']; ?></p>
                </div>
            </div>
        </div>
        <div class="row no-gutters">
            <div class="col-12">
                <div class="p-3">
                    <h5 class="text-bold text-italic">Location</h5>
                    <p><?php echo $site_visit_observation['Location']; ?></p>
                </div>
            </div>
        </div>
        <div class="row no-gutters">
            <div class="col-12">
                <div class="p-3">
                    <h5 class="text-bold text-italic">Created On</h5>
                    <p><?php echo $site_visit_observation['CreatedDate'].' | '.$site_visit_observation['CreatedTime']; ?></p>
                </div>
            </div>
        </div>
        <div class="row no-gutters">
            <div class="col-12">
                <div class="p-3">
                    <h5>Image(s)</h5>
                    <div class="row"><?php echo implode("", $image_observation_media); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

	<?php	
}
?>