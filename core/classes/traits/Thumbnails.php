<?php

namespace core\classes\traits;


trait Thumbnails {
	protected function makeThumbnails($updir, $img) {
		$max_image_width  = 200;
		$max_image_height = 150;
		$thumb_beforeword = "tn-";
		$arr_image_details = getimagesize("$updir" . "$img"); // pass id to thumb name
		$new_width = $original_width = $arr_image_details[0];
		$new_height = $original_height = $arr_image_details[1];
		if ($original_width > $max_image_width || $original_height > $max_image_height) {
			$ratio = 0;
			$ratio_x = $max_image_width  / $original_width;
			$ratio_y = $max_image_height / $original_height;
			if ($ratio_x < $ratio_y) {
				$ratio = $ratio_x;
			}
			else {
				$ratio = $ratio_y;
			}
			$new_width = (int)($ratio * $original_width);
			$new_height = (int)($ratio * $original_height);
		}

		if ($arr_image_details[2] == 1) {
			$imgt = "ImageGIF";
			$imgcreatefrom = "ImageCreateFromGIF";
		}
		if ($arr_image_details[2] == 2) {
			$imgt = "ImageJPEG";
			$imgcreatefrom = "ImageCreateFromJPEG";
		}
		if ($arr_image_details[2] == 3) {
			$imgt = "ImagePNG";
			$imgcreatefrom = "ImageCreateFromPNG";
		}
		if ($imgt) {
			$old_image = $imgcreatefrom("$updir" . "$img");
			$new_image = imagecreatetruecolor($new_width, $new_height);
			imagecopyresampled($new_image, $old_image, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height);
			$imgt($new_image, "$updir" . "$thumb_beforeword" . "$img");
		}
	}
}