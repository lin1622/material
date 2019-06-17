<?php
/**
 * Created by PhpStorm.
 * User: DDX
 * Date: 2019/6/17
 * Time: 10:16
 */

namespace app;


use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class BackdropAnalyze extends AbstractAnalyze
{
    public function __construct()
    {
        $sourceData = file_get_contents(
            'https://xmcdn.xiaoma.wang/xm_world/scratch/locale/medialibraries/backdropLibrary.json'
        );
        $this->sourceJson['backdrop'] = json_decode($sourceData);
        $this->fileDriver = new Filesystem();
        $this->sourceDir = __DIR__ . '/../src';
    }


    public function doAnalyze()
    {
        // TODO: Implement doAnalyze() method.
        $backdropFinder = new Finder();
        $backdropFinder->in($this->sourceDir.'/backdrop');
        foreach ($backdropFinder->files() as $row ) {
            /* @var $row SplFileInfo*/
            if (in_array($row->getExtension(), $this->filter)) {
                    $this->doAnalyzingBackdrop($row, 'backdrop');
            }
        }
        $this->dumpLibraryJson('backdrop');

    }

    private function doAnalyzingBackdrop(SplFileInfo $file)
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
        $this->currentJson['backdrop'][] = $init;
    }
}