<?php
/**
 * Created by PhpStorm.
 * User: imokhles
 * Date: 10/11/2017
 * Time: 22:52
 */

namespace App\Helpers\Signer;


use Symfony\Component\Finder\Iterator\RecursiveDirectoryIterator;

class CodeSigner
{

    // commands files
    public $codesignCommandPath = "/usr/bin/codesign";
    public $cpCommandPath = "/bin/cp";
    public $cdCommandPath = "/usr/bin/cd";
    public $zipCommandPath = "/usr/bin/zip";
    public $unzipCommandPath = "/usr/bin/unzip";
    public $plistBuddyCommandPath = "/usr/libexec/PlistBuddy";
    public $chmodCommandPath = "/bin/chmod";
    public $xcrunCommandPath = "/usr/bin/xcrun";
    
    // advanced vars
    public $singleApp;
    public $duplicatesNumber;

    // output vars
    public $ipa_url;
    public $png_url;
    public $plist_url;

    // main vars
    public $ipaPath;
    public $certName;
    public $profilePath;
    public $outPutPath;

    // customize path
    public $appName;
    public $appBundleId;
    public $appIconUrl;

    /**
     * Create a new controller instance.
     * @param $ipaPath
     * @param $certName
     * @param $profilePath
     * @param $outPutPath
     * @param $appName
     * @param $appBundleId
     * @param $appIconUrl
     * @param $duplicatesNumber
     * @param $isSingleApp
     * @param $ipa_url
     * @param $png_url
     * @param $plist_url
     * @return mixed
     */
    public function __construct(
        $ipaPath,
        $certName,
        $profilePath,
        $outPutPath,
        $appName,
        $appBundleId,
        $appIconUrl,
        $duplicatesNumber,
        $isSingleApp,
        $ipa_url,
        $png_url,
        $plist_url
    )
    {

        $this->singleApp = $isSingleApp;

        $this->ipaPath = $ipaPath;
        $this->certName = $certName;
        $this->profilePath = $profilePath;
        $this->outPutPath = $outPutPath;
        $this->appName = $appName;
        $this->appBundleId = $appBundleId;
        $this->appIconUrl = $appIconUrl;
        $this->duplicatesNumber = $duplicatesNumber;
        $this->ipa_url = $ipa_url;
        $this->png_url = $png_url;
        $this->plist_url = $plist_url;

    }

    public function startSignProcess() {
        if ($this->singleApp == true) {

            if ($this->isIpaFile($this->ipaPath)) {

            }
        }
    }

    // Private Methods
    private function isIpaFile($file) {
        $fileInfo = pathinfo($file);
        if ($fileInfo['extension'] === "ipa") {
            return true;
        }
        return false;
    }
    private function isFileExecutable($file) {
        return is_executable($file);
    }
    private function deleteFileAtPath($file) {
        return unlink($file);
    }
    private function ensurePathExist($path) {
        if (file_exists($path) == false) {
            return mkdir($path);
        }
    }
    private function recursivePathsForResourcesOfTypes($types, $path) {
        $objects = new \RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, \FilesystemIterator::KEY_AS_PATHNAME), \RecursiveIteratorIterator::SELF_FIRST);
        $filesPaths = [];
        foreach($objects as $name => $object) {
            $path_parts = pathinfo($name);
            $nameP = $path_parts['basename'];
            foreach($types as $type) {
                if (str_contains($nameP, $type)) {
                    array_push($filesPaths, $name);
                }
            }
        }
        return $filesPaths;
    }
    private function getFullPathOfPngFilesInDirectory($path) {

        $filteredItems = array('..', '.','.DS_Store');
        $files = array_diff(scandir($path, 1), $filteredItems);
        $pngFiles = [];
        foreach ($files as $file) {
            $path_parts = pathinfo($file);
            $nameP = $path_parts['basename'];
            if (str_contains($nameP, "png")) {
                array_push($pngFiles, $path."/".$file);
            }
        }
    }
    private function createImportantDirsWithOutputPath($outPutPath) {
        // not implemented yet ;)
    }

}
