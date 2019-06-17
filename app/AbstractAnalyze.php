<?php
/**
 * Created by PhpStorm.
 * User: DDX
 * Date: 2019/6/14
 * Time: 18:30
 */

namespace app;


use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

abstract class AbstractAnalyze implements AnalyzeInterface
{

    const OUT_PATH_PREFIX = 'xiaoma';

    protected $analyzeType = ['backdrop', 'sprite', 'costume'];

    public $filter = ['png', 'jpg', 'gif', 'svg'];

    public $sourceJson;

    public $currentJson;

    public $sourceDir;

    public $outDirt;

    protected $fileDriver;

    public function getTags(SplFileInfo $file)
    {
        if ($file->getFilenameWithoutExtension() == 'tag' && $file->getExtension() == 'txt') {
            $tagsData = trim($file->getContents());
            return explode('|', $tagsData);
        }
        return [];
    }

    public function getImageWidthAndHeight(SplFileInfo $fileInfo)
    {
        $imaInfo = getimagesize($fileInfo->getPathname());
        return [$imaInfo[0], $imaInfo[1]];
    }

    public function putOutBasePath()
    {
        return __DIR__.'/../out/';
    }

    public function getRules($analyzeType)
    {
        if (in_array($analyzeType, $this->analyzeType)) {
            return [
                'name' => '',
                'md5' => '',
                'type' => $analyzeType,
                'tags' => [],
                'info' => []
            ];
        }
    }

    protected function dumpLibraryJson($type)
    {

        if (!empty($this->currentJson[$type])) {
            $newSpriteLibrary = array_merge($this->currentJson[$type], $this->sourceJson[$type]);
            $this->fileDriver->dumpFile(
                $this->putOutBasePath().'/'.$type.'-Library.json',
                json_encode($newSpriteLibrary)
            );
        }
    }

    public function checkMaterialSize()
    {
        $reduce = new Finder();
        $reduce->in($this->sourceDir.'/');
        foreach ($reduce->files() as $row) {
            /* @var $row SplFileInfo*/
            if (in_array($row->getExtension(), $this->filter)) {
                $this->doAnalyzingImageSize($row);
            }
        }
    }

    public function doAnalyzingImageSize(SplFileInfo $file)
    {
        list($width, $height) = $this->getImageWidthAndHeight($file);
        if ($width > 480 || $height > 360) {
            $this->mkThumbnail(
                $file->getPathname(),
                480,
                360,
                $file->getPathname()
            );
        }
    }

    public function mkThumbnail($src, $width = null, $height = null, $filename = null) {

        if (!isset($width) && !isset($height))
            return false;

        if (isset($width) && $width <= 0)
            return false;

        if (isset($height) && $height <= 0)
            return false;

        $size = getimagesize($src);

        if (!$size)
            return false;

        list($src_w, $src_h, $src_type) = $size;

        $src_mime = $size['mime'];

        switch($src_type) {
            case 1 :
                $img_type = 'gif';
                break;
            case 2 :
                $img_type = 'jpeg';
                break;
            case 3 :
                $img_type = 'png';
                break;
            case 15 :
                $img_type = 'wbmp';
                break;
            default :
                return false;
        }

        if (!isset($width))
            $width = $src_w * ($height / $src_h);

        if (!isset($height))
            $height = $src_h * ($width / $src_w);

        $imagecreatefunc = 'imagecreatefrom' . $img_type;
        $src_img = $imagecreatefunc($src);
        $dest_img = imagecreatetruecolor($width, $height);
        imagealphablending($dest_img,false);//这里很重要,意思是不合并颜色,直接用$img图像颜色替换,包括透明色;
        imagesavealpha($dest_img,true);//这里很重要,意思是不要丢了$thumb图像的透明色;
        imagecopyresampled($dest_img, $src_img, 0, 0, 0, 0, $width, $height, $src_w, $src_h);
        $imagefunc = 'image' . $img_type;
        if ($filename) {
            $imagefunc($dest_img, $filename);
        } else {
            header('Content-Type: ' . $src_mime);
            $imagefunc($dest_img);
        }
        imagedestroy($src_img);
        imagedestroy($dest_img);
        return true;

    }
}