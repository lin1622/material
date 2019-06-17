<?php
/**
 * Created by PhpStorm.
 * User: DDX
 * Date: 2019/6/17
 * Time: 10:29
 */

namespace app;


class Material
{
    public function run()
    {
        $spriteAnalyze = new SpriteAnalyze();
        $spriteAnalyze->checkMaterialSize();
        $spriteAnalyze->doAnalyze();
        $backdropAnalyze = new BackdropAnalyze();
        $backdropAnalyze->doAnalyze();
    }
}