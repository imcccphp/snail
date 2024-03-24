<?php

namespace Imccc\Snail\Helper;

class ImageHelper
{
    /**
     * 图片缩放
     * @param $src
     * @param $dst
     * @param $width
     * @param $height
     * @param int $type
     */
    public static function imageResize($src, $dst, $width, $height, $type = 1)
    {
        $imageInfo = getimagesize($src);
        $srcWidth = $imageInfo[0];
        $srcHeight = $imageInfo[1];
        $srcType = $imageInfo[2];
        $srcImage = null;
        switch ($srcType) {
            case 1:
                $srcImage = imagecreatefromgif($src);
                break;
            case 2:
                $srcImage = imagecreatefromjpeg($src);
                break;
            case 3:
                $srcImage = imagecreatefrompng($src);
                break;
        }
        $dstImage = imagecreatetruecolor($width, $height);
    }

    /**
     * 图片base64编码
     * @param $image
     * @param string $type
     * @return string
     */
    public static function imageToBase64($image, $type = 'jpg')
    {
        $base64 = '';
        $image = file_get_contents($image);
        switch ($type) {
            case 'jpg':
                $base64 = 'data:image/jpg;base64,' . base64_encode($image);
                break;
            case 'gif':
                $base64 = 'data:image/gif;base64,' . base64_encode($image);
                break;
            case 'png':
                $base64 = 'data:image/png;base64,' . base64_encode($image);
                break;
        }
        return $base64;
    }

    /**
     * base64解码
     * @param $base64
     * @param string $type
     * @return string
     */
    public static function base64ToImage($base64, $type = 'jpg')
    {
        $image = '';
        switch ($type) {
            case 'jpg':
                $image = base64_decode(str_replace('data:image/jpg;base64,', '', $base64));
                break;
            case 'gif':
                $image = base64_decode(str_replace('data:image/gif;base64,', '', $base64));
                break;
            case 'png':
                $image = base64_decode(str_replace('data:image/png;base64,', '', $base64));
                break;
        }
        return $image;
    }

    /**
     * base64保存到文件
     * @param $base64
     * @param $path
     * @param string $type
     * @return bool
     */
    public static function base64ToFile($base64, $path, $type = 'jpg')
    {
        $image = '';
        $image = static::base64ToImage($base64, $image, $type);
        if (!file_put_contents($path, $image)) {
            return false;
        }
        return true;
    }

    /**
     * 图片水印
     * @param $src
     * @param $dst
     * @param $watermark
     * @param string $type
     * @return bool
     */
    public static function imageWatermark($src, $dst, $watermark, $type = 1)
    {
        $imageInfo = getimagesize($src);
        $srcWidth = $imageInfo[0];
        $srcHeight = $imageInfo[1];
        $srcType = $imageInfo[2];
        $srcImage = null;
        switch ($srcType) {
            case 1:
                $srcImage = imagecreatefromgif($src);
                break;
            case 2:
                $srcImage = imagecreatefromjpeg($src);
                break;
            case 3:
                $srcImage = imagecreatefrompng($src);
                break;
        }
        $watermarkImage = imagecreatefrompng($watermark);
        $watermarkWidth = imagesx($watermarkImage);
    }
}
