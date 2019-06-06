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

    protected $spriteJsonPath;

    protected $costumeJsonPath;

    protected $backdropJsonPath;

    protected $fileDriver;

    protected $finderDriver;

    protected $materialJson;

    protected $analyzeSourceDriver;

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

    protected $sourceMaterialJson;

    public function __construct()
    {
        $this->finderDriver = new Finder();
        $this->spriteJsonPath = 'https://xmcdn.xiaoma.wang/xm_world/scratch/locale/medialibraries/spriteLibrary.json';
        $this->costumeJsonPath = 'https://xmcdn.xiaoma.wang/xm_world/scratch/locale/medialibraries/costumeLibrary.json';
        $this->backdropJsonPath = 'https://xmcdn.xiaoma.wang/xm_world/scratch/locale/medialibraries/backdropLibrary.json';
        $this->fileSourceDir = __DIR__.'/../src';
        $this->fileTargetDir =__DIR__.'/../out';
        $this->analyzeSourceDriver = new AnalyzeSourceFile();
    }



    public function scanSourceDir()
    {
//        $this->finderDriver->in($this->fileSourceDir.'/backdrop');
//
//        foreach ($this->finderDriver->files() as $row ) {
//            /* @var $row SplFileInfo*/
//            if (in_array($row->getExtension(), $this->analyzeSourceDriver->filter)) {
//                $this->materialJson['backdrop'][] =
//                    $this->analyzeSourceDriver->doAnalyzing($row, 'backdrop');
//            }
//        }
//        $this->analyzeSourceDriver->mvLibraryJson($this, 'backdrop');
        $this->finderDriver->in($this->fileSourceDir.'/sprite');
        foreach ($this->finderDriver->directories() as $row) {
            /* @var $row SplFileInfo*/
            print_r($row);
        }
        die();
    }



    public function getJsonFiles()
    {
        $spriteJson = file_get_contents($this->spriteJsonPath);
        $costumeJson = file_get_contents($this->costumeJsonPath);
        $backdropJson = file_get_contents($this->backdropJsonPath);
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


    public function run()
    {
        $this->getJsonFiles();
        $this->scanSourceDir();
    }
}