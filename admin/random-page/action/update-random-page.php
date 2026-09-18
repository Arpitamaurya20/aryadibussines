<?php
session_start();
## Database configuration
include('../../controllers/common_controllers.php');
include('../../../function.inc.php');
$UserType = SessionCheck();

$conn = _connectodb();

$ID = $_POST['ID'];
if (isset($_POST['url'])) {
	//check url exist
	$url = $_POST['url'];
	$sql = "select url from random_page where url='$url' and ID!='$ID'";
	if (mysqli_num_rows(mysqli_query($conn, $sql)) > 0) {
		$_SESSION['randomExist'] = 'Url Already Exist !';
		redirect('../update-random-page.php?ID=' . $ID . '');
	} else {
		$title = $_POST['title'];
		$meta_title = $_POST['meta_title'];
		$meta_description = $_POST['meta_description'];
		$meta_keyword = $_POST['meta_keyword'];
		$url = $_POST['url'];
		$safety = $_POST['safety'];


		$desc_box_h1_desc = $_POST['desc_box_h1_desc'];
		$desc_box_h1_desc = addslashes($desc_box_h1_desc);
		$desc_box_h1_desc = trim($desc_box_h1_desc);



		$desc_box_h2_desc = $_POST['desc_box_h2_desc'];
		$desc_box_h2_desc = addslashes($desc_box_h2_desc);
		$desc_box_h2_desc = trim($desc_box_h2_desc);


		$desc_box_h3_desc = $_POST['desc_box_h3_desc'];
		$desc_box_h3_desc = addslashes($desc_box_h3_desc);
		$desc_box_h3_desc = trim($desc_box_h3_desc);

		if (isset($_POST['desc_box_h1'])) {
			$desc_box_h1 = $_POST['desc_box_h1'];
		} else {
			$desc_box_h1 = '';
		}
		if (isset($_POST['desc_box_h2'])) {
			$desc_box_h2 = $_POST['desc_box_h2'];
		} else {
			$desc_box_h2 = '';
		}
		if (isset($_POST['desc_box_h3'])) {
			$desc_box_h3 = $_POST['desc_box_h3'];
		} else {
			$desc_box_h3 = '';
		}
		if (isset($_POST['mainheading'])) {
			$mainheading = $_POST['mainheading'];
		} else {
			$mainheading = '';
		}

		if (isset($_POST['serviceLinkHeading'])) {
			$serviceLinkHeading = $_POST['serviceLinkHeading'];
		} else {
			$serviceLinkHeading = '';
		}
		if (isset($_POST['cityLinkHeading'])) {
			$cityLinkHeading = $_POST['cityLinkHeading'];
		} else {
			$cityLinkHeading = '';
		}
		
		
		$brand_heading = $_POST['brand_heading'];
		$subservice_heading = $_POST['subservice_heading'];

		$update_action = "UPDATE random_page SET title='$title',safety='$safety',mainheading='$mainheading', desc_box_h1_desc='$desc_box_h1_desc',desc_box_h2_desc='$desc_box_h2_desc',desc_box_h3_desc='$desc_box_h3_desc',desc_box_h1='$desc_box_h1',desc_box_h2='$desc_box_h2',desc_box_h3='$desc_box_h3',meta_title='$meta_title',meta_keyword='$meta_keyword',meta_description='$meta_description',url='$url',brand_heading='$brand_heading',subservice_heading='$subservice_heading',serviceLinkHeading='$serviceLinkHeading',cityLinkHeading='$cityLinkHeading',status='1' WHERE ID ='$ID' ";
		$result  = mysqli_query($conn, $update_action);


		if (isset($_FILES['safety_img']['name']) && $_FILES['safety_img']['name'] != '') {
			$safety_img   = $_FILES['safety_img']['name'];
			$upath1 = "../../media/safety/" . $_FILES['safety_img']['name'];
			move_uploaded_file($_FILES["safety_img"]["tmp_name"], $upath1);
			$update_service = "UPDATE random_page SET safety_img='$safety_img' WHERE ID ='$ID' ";
			//echo $update_service;
			$result_service 	= mysqli_query($conn, $update_service);
		}
		// update subservice (WORK FINE)
		if (isset($_POST['subservice']) != '') {
			if (isset($_POST['subserviceID']) != '') {
				$subserviceIDarr = $_POST['subserviceID'];
			} else {
				$subserviceIDarr = '';
			}
			$subserviceArr = $_POST['subservice'];
			foreach ($subserviceArr as $key => $val) {
				$subservice = $val;
				if (isset($subserviceIDarr[$key])) {
					$SubID = $subserviceIDarr[$key];
					mysqli_query($conn, "update randomsubservice set title='$subservice' where ID='$SubID'");
				} else {
					mysqli_query($conn, "insert into randomsubservice(random_page,title) values('$ID','$subservice')");
				}
			}
		}

		//update  faq (WORK FINE)
		if (isset($_POST['q']) != '' && isset($_POST['a']) != '') {
			if (isset($_POST['faqID'])) {
				$faqIDarr = $_POST['faqID'];
			} else {
				$faqIDarr = "";
			}
			$qArr = $_POST['q'];
			$aArr = $_POST['a'];
			foreach ($qArr as $key => $val) {
				$q = $val;
				$a = $aArr[$key];
				if (isset($faqIDarr[$key])) {
					$faqID = $faqIDarr[$key];
					mysqli_query($conn, "update randomfaq set q='$q', a='$a' where ID='$faqID'");
				} else {
					mysqli_query($conn, "insert into randomfaq(random_page,q,a) values('$ID','$q','$a')");
				}
			}
		}
		//update  keyword (WORK FINE)
		if (isset($_POST['keywordtext']) != '' && isset($_POST['keywordlink']) != '') {
			if (isset($_POST['keywordID'])) {
				$keywordIDarr = $_POST['keywordID'];
			} else {
				$keywordIDarr = "";
			}
			$keywordtextArr = $_POST['keywordtext'];
			$keywordlinkArr = $_POST['keywordlink'];
			$linkTypeArr = $_POST['linkType'];
			foreach ($keywordtextArr as $key => $val) {
				$keywordtext = $val;
				$keywordlink = $keywordlinkArr[$key];
				$linkType = $linkTypeArr[$key];
				if (isset($keywordIDarr[$key])) {
					$keywordID = $keywordIDarr[$key];
					mysqli_query($conn, "update random_keywords set keywordtext='$keywordtext', keywordlink='$keywordlink',linkType='$linkType' where keyword_id='$keywordID'");
				} else {
					mysqli_query($conn, "insert into random_keywords(random_page,keywordtext,keywordlink,linkType) values('$ID','$keywordtext' ,'$keywordlink' ,'$linkType')");
				}
			}
		}
		// update brand 

		if (isset($_POST['brand_title']) != '' && isset($_POST['brand_url']) != '') {
			$imgArr = $_FILES['brand_image']['name'];

			if (isset($_POST['BrandID'])) {
				$BrandIDArr = $_POST['BrandID'];
			} else {
				$BrandIDArr = '';
			}

			$brand_titleArr = $_POST['brand_title'];
			$brand_urlArr = $_POST['brand_url'];
			foreach ($brand_titleArr as $key => $val) {
				$brand_image = $imgArr[$key];
				$brand_title = $val;
				$brand_url = $brand_urlArr[$key];
				if (isset($BrandIDArr[$key])) {
					$BrandID = $BrandIDArr[$key];
					// if (isset($_FILES['brand_image']['name']) == '') {
					if ($_FILES['brand_image']['name'][$key] != '') {
						if ($_POST['BrandID'][$key]) {

							$update_sql = "update randomservicebrand set random_page='$ID',title='$brand_title',image='$brand_image',url='$brand_url' where ID='" . $_POST['BrandID'][$key] . "'";
							$path = "../../media/brand/" .  $brand_image;
							move_uploaded_file($_FILES["brand_image"]["tmp_name"][$key], $path);
						}
					} else {
						$update_sql = "update randomservicebrand set random_page='$ID',title='$brand_title',url='$brand_url' where ID='$BrandID'";
					}

					mysqli_query($conn, $update_sql);
				}


				// new brand insert 
				else {
					mysqli_query($conn, "insert into  randomservicebrand(random_page,title,image,url) values('$ID','$brand_title' ,'$brand_image','$brand_url')");
					$path = "../../media/brand/" .  $brand_image;
					move_uploaded_file($_FILES["brand_image"]["tmp_name"][$key], $path);


					// echo "new row insert";
				}
			}
		}

		redirect('../view-random-page.php');
	}
}
