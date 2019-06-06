<?php
/**
 * Created by PhpStorm.
 * User: linm
 * Date: 2019/6/6
 * Time: 11:23
 */
require_once './vendor/autoload.php';
require_once './app/FlashMaterial.php';
require_once './app/AnalyzeSourceFile.php';
$flashMaterial = new \app\FlashMaterial();
$flashMaterial->run();