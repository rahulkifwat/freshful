<?php
include_once('../functions/functions.php');

function get_cat_id($name){
	$qry=db_select_query("SELECT id FROM category WHERE name='$name'");
	if($qry){
		return $qry[0]['id'];
	}else{
		return 0;
	}
}

if(isset($_REQUEST))
{
	$file = array();

	if(!empty($_FILES['file_upload']['name'])){

		if(isset($_FILES['file_upload']['name']) && !empty($_FILES['file_upload']['name'])){
		$ext = pathinfo($_FILES["file_upload"]['name'],PATHINFO_EXTENSION);
		if($ext=='csv'){
			
			$i=0;
			$file = $_FILES['file_upload']['tmp_name'];
			$handle = fopen($file, "r");
			while(($filesopen = fgetcsv($handle, 1000, ",")) !== false){
				if($i==0){
					$i;
				}else{ 
					
					//print_r($filesopen); 
					
					$a00=$filesopen[0];
					$a11=get_cat_id($filesopen[1]);
					
					$insert123="INSERT INTO `items`(`item`,`cat_id`) VALUES ('$a00','$a11')";
				 	$query_run = db_insert_query($insert123);	
				}
				$i++;
			}	
			fclose($handle);
			
		}else{
			echo "no csv";
		}
	}

	}else{
		echo "no file";
	}
}
?>

<form action="" method="post" enctype="multipart/form-data">
	<input type="file" name="file_upload">
	<input type="submit">
</form>