<?php

namespace Adempier\ImageScript\Helper;

class Image
{

    function scaleImage($image_width, $image_height, $max_width, $max_height)
    {
        $scaleImg = array();
        $old_width = $image_width;
        $old_height = $image_height;
        $scale = min($max_width / $old_width, $max_height / $old_height);
        $new_width = ceil($scale * $old_width);
        $new_height = ceil($scale * $old_height);
        $scaleImg = array("w" => $new_width, "h" => $new_height);
        return $scaleImg;
    }

    public function copyInAnotherFolder($imageName)
    {
        if (file_exists(BACKUP_DIR . DIRECTORY_SEPARATOR . $imageName)) {
            $this->storeImage($imageName);
        }
        $this->backupImage($imageName);
    }

    public function storeImage($imageName)
    {
        $exImage = explode('.', $imageName);
        $sku = $exImage[0];
        $exImage = $exImage[1];
        rename(BACKUP_DIR . DIRECTORY_SEPARATOR . $imageName, BACKUP_DIR . DIRECTORY_SEPARATOR . $sku . '-' . date('d-m-Y') . '.' . $exImage);
    }

    public function backupImage($imageName)
    {
        $sourceFile = UPLOAD_DIR . DIRECTORY_SEPARATOR . $imageName;
        $targetFile = BACKUP_DIR . DIRECTORY_SEPARATOR . $imageName;
        if (copy($sourceFile, $sourceFile)) {
            unlink($sourceFile);
        }
    }

}
