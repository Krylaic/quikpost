<?php
namespace App\Services;


class HelperService extends BaseService {
    // Source - https://stackoverflow.com/a/26036013
    // Posted by Captain Red, modified by community. See post 'Timeline' for change history
    // Retrieved 2026-09-13, License - CC BY-SA 3.0

    //Function definition

    function timeAgo($time_ago) {
        $time_ago = strtotime($time_ago);
        $cur_time   = time();
        $time_elapsed   = $cur_time - $time_ago;
        $seconds    = $time_elapsed ;
        $minutes    = round($time_elapsed / 60 );
        $hours      = round($time_elapsed / 3600);
        $days       = round($time_elapsed / 86400 );
        $weeks      = round($time_elapsed / 604800);
        $months     = round($time_elapsed / 2600640 );
        $years      = round($time_elapsed / 31207680 );
        // Seconds
        if($seconds <= 60){
            return "just now";
        }
        //Minutes
        else if($minutes <=60){
            if($minutes==1){
                return "one minute ago";
            }
            else{
                return "$minutes minutes ago";
            }
        }
        //Hours
        else if($hours <=24){
            if($hours==1){
                return "an hour ago";
            }else{
                return "$hours hrs ago";
            }
        }
        //Days
        else if($days <= 7){
            if($days==1){
                return "yesterday";
            }else{
                return "$days days ago";
            }
        }
        //Weeks
        else if($weeks <= 4.3){
            if($weeks==1){
                return "a week ago";
            }else{
                return "$weeks weeks ago";
            }
        }
        //Months
        else if($months <=12){
            if($months==1){
                return "a month ago";
            }else{
                return "$months months ago";
            }
        }
        //Years
        else{
            if($years==1){
                return "one year ago";
            }else{
                return "$years years ago";
            }
        }
    }

    // taken from original quikpost
    // pretty sure i watched a youtube video on how to make this ?? i dont remember it was a long time ago lol 
    function uploadImageFile($inputName, $destinationDir, $maxFileSize = 10000000, $allowedExts = ['png', 'jpg', 'jpeg', 'bmp', 'gif', 'webp'], $onError = null) {
        if (!is_array($allowedExts)) {
            $allowedExts = ['png', 'jpg', 'jpeg', 'bmp', 'gif', 'webp'];
        }
        
        if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] !== 4) {
            $file = $_FILES[$inputName];
            $fileName = $file['name'];
            $fileTmpName = $file['tmp_name'];
            $fileSize = $file['size'];
            $fileError = $file['error'];

            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if (in_array($fileExt, $allowedExts)) {
                if ($fileError === 0) {
                    if ($fileSize < $maxFileSize) {
                        $targetExt = ($fileExt === 'gif') ? 'gif' : 'jpg';
                        $fileNameNew = uniqid('', true) . "." . $targetExt;

                        $relativePath = '/' . trim($destinationDir, '/') . '/' . $fileNameNew;
                        $fileFullDestination = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $relativePath;

                        if($fileExt == 'gif') {
                            if (move_uploaded_file($fileTmpName, $fileFullDestination)) {
                                return $relativePath;
                            } else {
                                if (is_callable($onError)) $onError("Failed to move uploaded file.");
                            }
                        }else {
                            switch ($fileExt) {
                                // taken from https://code-boxx.com/compress-images-php/
                                case 'png':
                                    // (A) IMAGE OBJECT
                                    $img = imagecreatefrompng($fileTmpName);
                                    if ($img !== false) {
                                        if (!imageistruecolor($img)) {
                                            imagepalettetotruecolor($img);
                                        }
                                        
                                        // (B) COMPRESS IMAGE
                                        // (B1) BY REDUCING QUALITY
                                        if (imagejpeg($img, $fileFullDestination, 50)) {
                                            imagedestroy($img);
                                            return $relativePath;
                                        }
                                        
                                        imagedestroy($img);
                                        if (is_callable($onError)) $onError("Failed to compress image");
                                    } else {
                                        if (is_callable($onError)) $onError("Failed to compress image");
                                    }
                                    break;
                                case 'jpeg':
                                case 'jpg':
                                    // (A) IMAGE OBJECT
                                    $img = imagecreatefromjpeg($fileTmpName);
                                    if ($img !== false) {
                                        if (!imageistruecolor($img)) {
                                            imagepalettetotruecolor($img);
                                        }
                                        
                                        // (B) COMPRESS IMAGE
                                        // (B1) BY REDUCING QUALITY
                                        if (imagejpeg($img, $fileFullDestination, 50)) {
                                            imagedestroy($img);
                                            return $relativePath;
                                        }
                                        
                                        imagedestroy($img);
                                        if (is_callable($onError)) $onError("Failed to compress image");
                                    } else {
                                        if (is_callable($onError)) $onError("Failed to compress image");
                                    }
                                    break;
                                case 'webp':
                                    // (A) IMAGE OBJECT
                                    $img = imagecreatefromwebp($fileTmpName);
                                    if ($img !== false) {
                                        if (!imageistruecolor($img)) {
                                            imagepalettetotruecolor($img);
                                        }
                                        
                                        // (B) COMPRESS IMAGE
                                        // (B1) BY REDUCING QUALITY
                                        if (imagejpeg($img, $fileFullDestination, 50)) {
                                            imagedestroy($img);
                                            return $relativePath;
                                        }
                                        
                                        imagedestroy($img);
                                        if (is_callable($onError)) $onError("Failed to compress image");
                                    } else {
                                        if (is_callable($onError)) $onError("Failed to compress image");
                                    }
                                    break;
                                case 'bmp':
                                    // (A) IMAGE OBJECT
                                    $img = imagecreatefrombmp($fileTmpName);
                                    if ($img !== false) {
                                        if (!imageistruecolor($img)) {
                                            imagepalettetotruecolor($img);
                                        }
                                        
                                        // (B) COMPRESS IMAGE
                                        // (B1) BY REDUCING QUALITY
                                        if (imagejpeg($img, $fileFullDestination, 50)) {
                                            imagedestroy($img);
                                            return $relativePath;
                                        }
                                        
                                        imagedestroy($img);
                                        if (is_callable($onError)) $onError("Failed to compress image");
                                    } else {
                                        if (is_callable($onError)) $onError("Failed to compress image");
                                    }
                                    break;
                            }
                        }
                    } else {
                        if (is_callable($onError)) $onError("File is too large.");
                    }
                } else {
                    if (is_callable($onError)) $onError("Upload error occurred.");
                }
            } else {
                if (is_callable($onError)) $onError("Invalid file type.");
            }
        }

        return null;
    }

}