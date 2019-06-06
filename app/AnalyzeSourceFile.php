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

class AnalyzeSourceFile
{
    public $sourceFileType;

    public $tag;

    public $filter = ['png', 'jpg', 'gif', 'svg'];

    private $analyzeType = ['backdrop', 'sprite'];

    private $fileDriver;

    public function __construct()
    {
        $this->fileDriver = new Filesystem();
    }

    public function doAnalyzing(SplFileInfo $file, $analyzeType)
    {
        #获取规则
        $init = $this->getRules($analyzeType);
        #获取tag.txt
        $tagContent = file_get_contents($file->getPath().'/tag.txt');
        $tagContentArr = explode('|', trim($tagContent));
        #获取文件
        switch ($analyzeType) {
            case 'backdrop':
                $md5Value = $this->mvNewImg($file, $analyzeType);
                $init['name'] = $file->getFilenameWithoutExtension();
                $init['md5'] = $md5Value;
                break;
            case 'sprite':

                break;
        }
        $init['tags'] = $tagContentArr ? $tagContentArr : [];
        return $init;
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

    public function mvNewImg(SplFileInfo $file, $analyzeType)
    {
        $newPathPrefix = 'xiaoma';
        $targetFileName = md5($file->getContents()).'.'.$file->getExtension();
        $targetFile = __DIR__.'/../out/'.$newPathPrefix.'/'.$analyzeType.'/'.$targetFileName;
        $this->fileDriver->copy($file->getPathname(), $targetFile);
        $md5Value = $newPathPrefix.'/'.$analyzeType.'/'.$targetFileName;
        return $md5Value;
    }


    public function mvLibraryJson(FlashMaterial $flashMaterial, $analyzeType)
    {
        $oldJsonFiles = $flashMaterial->getSourceMaterialJson();
        $newJsonFile =$flashMaterial->getMaterialJson();

        if (!empty($oldJsonFiles[$analyzeType]) && !empty($newJsonFile[$analyzeType])) {

            $newJson = array_merge($newJsonFile[$analyzeType], $oldJsonFiles[$analyzeType]);

            $targetFile = __DIR__.'/../out/'.$analyzeType.'Library.json';
            $this->fileDriver->dumpFile($targetFile, json_encode($newJson));
        }

    }
}