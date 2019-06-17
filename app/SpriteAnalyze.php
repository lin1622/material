<?php
/**
 * Created by PhpStorm.
 * User: DDX
 * Date: 2019/6/14
 * Time: 18:14
 */

namespace app;


use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class SpriteAnalyze extends AbstractAnalyze
{

    public $spriteDir;

    private $spriteFiles = [];

    public function __construct()
    {
        $sourceData = file_get_contents(
                'https://xmcdn.xiaoma.wang/xm_world/scratch/locale/medialibraries/spriteLibrary.json'
            );
        $costumeData = file_get_contents(
            'https://xmcdn.xiaoma.wang/xm_world/scratch/locale/medialibraries/costumeLibrary.json'
        );
        $this->sourceJson['sprite'] = json_decode($sourceData);
        $this->sourceJson['costume'] = json_decode($costumeData);
        $this->fileDriver = new Filesystem();
        $this->sourceDir = __DIR__ . '/../src';
    }

    public function doAnalyze()
    {
        // TODO: Implement doAnalyze() method.
        # 角色
        $dir = [];
        $spriteFinder = new Finder();
        $spriteFinder->depth('==0')->directories()->in($this->sourceDir.'/sprite');
        foreach ($spriteFinder as $row) {

            /* @var $row SplFileInfo*/
            $this->getFilesBySpriteDir($row);
        }
        $this->analyzeSpriteFiles();
        #生成文件
        $this->dumpLibraryJson('sprite');
        $this->dumpLibraryJson('costume');

    }

    private function getFilesBySpriteDir(SplFileInfo $spriteDir)
    {

        $finder = new Finder();
        $finder->depth('==0')->in($spriteDir->getPathname());

        foreach ($finder as $file) {

            if(!$file->isDir()) {
                if ($file->getFilenameWithoutExtension() == 'tag') {
                    $this->spriteFiles[$file->getPath()]['tag'] = $file;
                } else {
                    $this->spriteFiles[$file->getPath()][] = $file;
                }
            };
        }
    }

    private function analyzeSpriteFiles()
    {
        foreach ($this->spriteFiles as $files) {
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
        $this->currentJson['costume'][] =$init;
        return $init['md5'];
    }

    private function newSpriteLibraryRecord($dirName, $md5Value, $targsArr, $costumeCount)
    {
        $this->currentJson['sprite'][] = [
            'name' => $dirName,
            'md5' => $md5Value,
            'type' => 'sprite',
            'tags' => $targsArr,
            'info' => [0, $costumeCount, 0]
        ];
    }
}