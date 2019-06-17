<?php
/**
 * Created by PhpStorm.
 * User: linm
 * Date: 2019/6/6
 * Time: 14:59
 */

namespace app;


use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;

class AnalyzeMaterialFile
{
    public $sourceFileType;

    public $tag;

    public $filter = ['png', 'jpg', 'gif', 'svg'];

    private $analyzeType = ['backdrop', 'sprite', 'costume'];

    private $fileDriver;

    protected $sourceMaterialJson;

    protected $currentMaterialJson;

    public function __construct()
    {
        $this->fileDriver = new Filesystem();
    }

    public function getJsonFiles(FlashMaterial $flashMaterial)
    {
        $spriteJson = file_get_contents($flashMaterial->spriteJsonPath);
        $costumeJson = file_get_contents($flashMaterial->costumeJsonPath);
        $backdropJson = file_get_contents($flashMaterial->backdropJsonPath);
        if ($spriteJson) {
            $this->sourceMaterialJson['sprite'] = json_decode($spriteJson, true);
        }
        if ($costumeJson) {
            $this->sourceMaterialJson['costume'] = json_decode($costumeJson, true);
        }
        if ($backdropJson) {
            $this->sourceMaterialJson['backdrop'] = json_decode($backdropJson, true);
        }
    }

    public function doAnalyzingBackdrop(SplFileInfo $file)
    {
        #获取规则
        $init = $this->getRules('backdrop');
        #获取tag.txt
        $tagContent = file_get_contents($file->getPath().'/tag.txt');
        $tagContentArr = explode('|', trim($tagContent));
        #获取文件
        $newPathPrefix = 'xiaoma';
        $targetFileName = md5($file->getContents()).'.'.$file->getExtension();
        $targetFile = __DIR__.'/../out/'.$newPathPrefix.'/backdrop/'.$targetFileName;
        $this->fileDriver->copy($file->getPathname(), $targetFile);
        $md5Value = $newPathPrefix.'/backdrop/'.$targetFileName;
        $init['name'] = $file->getFilenameWithoutExtension();
        $init['md5'] = $md5Value;
        $init['tags'] = $tagContentArr ? $tagContentArr : [];
        $this->currentMaterialJson['backdrop'][] = $init;
        return $init;
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


    public function doAnalyzingSprite(Array $fileArray)
    {
        foreach ($fileArray as $files) {
            if (!empty($files['tag'])) {
                /* @var $tagText SplFileInfo*/
                $tagText = $files['tag'];
                $tagsArr = $this->getTags($tagText);
                $dirName = basename($tagText->getPath());
                $costumeCount = 0;
                $costumeInfo = [];
                foreach ($files as $imgFile) {
                    /* @var $imgFile SplFileInfo*/
                    if (in_array($imgFile->getExtension(), $this->filter) ) {
                        list($width, $height) = $this->getImageWidthAndHeight($imgFile);
                        #生成json文件
                        $md5Value = $this->newMd5CostumeJson($imgFile, $tagsArr);
                        $costumeCount += 1;
                        $costumeInfo[] = [
                            'costumeName' => $imgFile->getFilenameWithoutExtension(),
                            'baseLayerID' => -1,
                            'baseLayerMD5' => $md5Value,
                            'bitmapResolution' => 1,
                            'rotationCenterX' => ceil($width / 2),
                            'rotationCenterY' => ceil($height / 2)
                        ];
                    }
                }
                $this->newSpriteLibraryRecord(
                    $dirName, 'xiaoma/sprite/'.md5($dirName).'.json', $tagsArr, $costumeCount
                );
                $this->newMd5SpriteJson(basename($tagText->getPath()), $costumeInfo);
            }
        }
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

    private function newSpriteLibraryRecord($dirName, $md5Value, $targsArr, $costumeCount)
    {
        $this->currentMaterialJson['sprite'][] = [
            'name' => $dirName,
            'md5' => $md5Value,
            'type' => 'sprite',
            'tags' => $targsArr,
            'info' => [0, $costumeCount, 0]
        ];
    }

    private function newMd5SpriteJson($fileName, $costumesInfo)
    {
        $newPathPrefix = 'xiaoma';
        $targetFile = $this->putOutBasePath() . $newPathPrefix .'/sprite/'. md5($fileName).'.json';
        $targetContent = [
            'objName' => $fileName,
            'sounds' => [],
            'costumes' => $costumesInfo,
            'currentCostumeIndex' => 0,
            'scratchX' => 0,
            'scratchY' => 0,
            'scale' => 1,
            'direction' => 90,
            'rotationStyle' => 'normal',
            'isDraggable' => false,
            'indexInLibrary' => 100000,
            'visible' => true,
            'spriteInfo' => new \stdClass()
        ];
        $this->fileDriver->dumpFile($targetFile, json_encode($targetContent));
    }

    public function updateLibraryJson()
    {
        #sprite
        if (!empty($this->currentMaterialJson['sprite'])) {
            $newSpriteLibrary = array_merge($this->currentMaterialJson['sprite'], $this->sourceMaterialJson['sprite']);
            $this->fileDriver->dumpFile(
                $this->putOutBasePath().'/sprite-Library.json',
                json_encode($newSpriteLibrary)
            );
        }
        #backdrop
        if (!empty($this->currentMaterialJson['backdrop'])) {
            $newBackdropLibrary = array_merge($this->currentMaterialJson['backdrop'], $this->sourceMaterialJson['backdrop']);
            $this->fileDriver->dumpFile(
                $this->putOutBasePath().'/backdrop-Library.json',
                json_encode($newBackdropLibrary)
            );
        }

        #costume
        if (!empty($this->currentMaterialJson['costume'])) {
            $newCostumeLibrary = array_merge($this->currentMaterialJson['costume'], $this->sourceMaterialJson['costume']);
            $this->fileDriver->dumpFile(
                $this->putOutBasePath().'/costume-Library.json',
                json_encode($newCostumeLibrary)
            );
        }

    }

    private function newMd5CostumeJson(SplFileInfo $file, $tagArr)
    {
        $newPathPrefix = 'xiaoma';
        $fileMd5 = md5($file->getContents());
        $targetFileName = $fileMd5.'.'.$file->getExtension();
        $targetFile = $this->putOutBasePath().$newPathPrefix.'/costume/'.$targetFileName;
        $this->fileDriver->copy($file->getPathname(), $targetFile);
        $init = $this->getRules('costume');
        $init['md5'] =  $newPathPrefix.'/costume/'.$targetFileName;
        $init['name'] = $file->getFilenameWithoutExtension();
        $init['tags'] = $tagArr;
        $this->currentMaterialJson['costume'][] =$init;
        return $init['md5'];
    }


    private function putOutBasePath()
    {
        return __DIR__.'/../out/';
    }

    private function getImageWidthAndHeight(SplFileInfo $fileInfo)
    {
        $imaInfo = getimagesize($fileInfo->getPathname());
        return [$imaInfo[0], $imaInfo[1]];
    }

    private function getTags(SplFileInfo $file)
    {
        if ($file->getFilenameWithoutExtension() == 'tag' && $file->getExtension() == 'txt') {
            $tagsData = trim($file->getContents());
            return explode('|', $tagsData);
        }
        return [];
    }
}