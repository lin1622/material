<?php
/**
 * Created by PhpStorm.
 * User: linm
 * Date: 2019/6/6
 * Time: 10:37
 */

namespace app;


use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class FlashMaterial
{

    protected $fileSourceDir;

    protected $fileTargetDir;

    public $spriteJsonPath;

    public $costumeJsonPath;

    public $backdropJsonPath;

    protected $fileDriver;

    protected $finderDriver;

    protected $materialJson;

    protected $analyzeSourceDriver;

    protected $sourceMaterialJson;


    /**
     * @return mixed
     */
    public function getMaterialJson()
    {
        return $this->materialJson;
    }

    /**
     * @param mixed $materialJson
     */
    public function setMaterialJson($materialJson): void
    {
        $this->materialJson = $materialJson;
    }

    /**
     * @return mixed
     */
    public function getSourceMaterialJson()
    {
        return $this->sourceMaterialJson;
    }

    /**
     * @param mixed $sourceMaterialJson
     */
    public function setSourceMaterialJson($sourceMaterialJson): void
    {
        $this->sourceMaterialJson = $sourceMaterialJson;
    }



    public function __construct()
    {
        $this->finderDriver = new Finder();
        $this->spriteJsonPath = 'https://xmcdn.xiaoma.wang/xm_world/scratch/locale/medialibraries/spriteLibrary.json';
        $this->costumeJsonPath = 'https://xmcdn.xiaoma.wang/xm_world/scratch/locale/medialibraries/costumeLibrary.json';
        $this->backdropJsonPath = 'https://xmcdn.xiaoma.wang/xm_world/scratch/locale/medialibraries/backdropLibrary.json';
        $this->fileSourceDir = __DIR__.'/../src';
        $this->fileTargetDir =__DIR__.'/../out';
        $this->analyzeSourceDriver = new AnalyzeMaterialFile();
    }



    public function scanSourceDir()
    {
        #背景
        $backdropFinder = new Finder();
        $backdropFinder->in($this->fileSourceDir.'/backdrop');

        foreach ($backdropFinder->files() as $row ) {
            /* @var $row SplFileInfo*/
            if (in_array($row->getExtension(), $this->analyzeSourceDriver->filter)) {
                $this->materialJson['backdrop'][] =
                    $this->analyzeSourceDriver->doAnalyzingBackdrop($row, 'backdrop');
            }
        }

        # 角色
        $dir = [];
        $spriteFinder = new Finder();
        $spriteFinder->depth('==0')->directories()->in($this->fileSourceDir.'/sprite');
        foreach ($spriteFinder as $row) {
            /* @var $row SplFileInfo*/
            $dir[] = $row->getPathname();
        }
        $spriteFiles = [];
        foreach ($dir as $path) {
            $fileFinder = new Finder();
            $fileFinder->depth('==0')->in($path);
            foreach ($fileFinder as $file) {
                if(!$file->isDir()) {
                    if ($file->getFilenameWithoutExtension() == 'tag') {
                        $spriteFiles[$file->getPath()]['tag'] = $file;
                    } else {
                        $spriteFiles[$file->getPath()][] = $file;
                    }
                };
            }
        }
        $this->analyzeSourceDriver->doAnalyzingSprite($spriteFiles);

        $this->analyzeSourceDriver->updateLibraryJson();
    }




    public function run()
    {
        $this->analyzeSourceDriver->getJsonFiles($this);
        $this->scanSourceDir();
    }
}