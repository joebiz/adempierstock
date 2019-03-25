<?php
header('Content-Type: application/json');
chdir('images_to_update');
$respones = glob('*.{jpg,png,gif}', GLOB_BRACE);
echo json_encode($respones);


