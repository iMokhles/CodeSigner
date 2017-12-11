<?php
/**
 * Created by PhpStorm.
 * User: imokhles
 * Date: 10/11/2017
 * Time: 22:52
 */

namespace App\Helpers\Signer;


use App\Helpers\Apple\PlistHelper;
use App\Helpers\Downloader\Command\Executor;
use CFPropertyList\CFPropertyList;
use Illuminate\Support\Facades\Log;
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
            if (str_contains($nameP, ".png")) {
                array_push($pngFiles, $path."/".$file);
            }
        }
        return $pngFiles;
    }
    private function createImportantDirsWithOutputPath($outPutPath) {
        // not implemented yet ;)

        if (file_exists($this->processWorkPath()) == true) {
            $this->deleteFileAtPath($this->processWorkPath());
        } else {
            $this->ensurePathExist($this->processWorkPath());
        }

        if (file_exists($this->tempPath()) == true) {
            $this->deleteFileAtPath($this->tempPath());
        } else {
            $this->ensurePathExist($this->tempPath());
        }

        if (file_exists($this->tempIconsPath()) == true) {
            $this->deleteFileAtPath($this->tempIconsPath());
        } else {
            $this->ensurePathExist($this->tempIconsPath());
        }

        if (file_exists($this->extractedPath()) == true) {
            $this->deleteFileAtPath($this->extractedPath());
        } else {
            $this->ensurePathExist($this->extractedPath());
        }

        if (file_exists($this->outPutPath) == true) {
//            $this->deleteFileAtPath($this->outPutPath);
        } else {
            $this->ensurePathExist($this->outPutPath);
        }
    }
    private function unZipFileToPath($ipaPath, $extractedPath) {
        $extracted = Executor::execute("$this->unzipCommandPath -oqq $ipaPath -d $extractedPath");
        if ($extracted['exit_status'] != 0) {
            Log::error("Failed to unzip ipa file.");
        }
    }
    private function zipFileToPath($ipaOutPutPath) {

        $extractedPath = $this->extractedPath();
        $appIpaFileName = $this->appName.".ipa";


        $extracted = Executor::execute("cd $extractedPath && $this->zipCommandPath $appIpaFileName -qry Payload");
        if ($extracted['exit_status'] == 0) {
            $zippedIpaPath = $this->extractedPath()."/".$appIpaFileName;
            $outPutIpaPath = $ipaOutPutPath."/".$appIpaFileName;

            $extracted = Executor::execute("$this->cpCommandPath $zippedIpaPath $outPutIpaPath");
            if ($extracted['exit_status'] != 0) {
                Log::error("Failed to copy ipa file to output folder.");
            }
        } else {
            Log::error("Failed to zip ipa file.");
        }
    }
    private function getCorrectBundleIDFormProfile($profilePath) {

        $codesignEntitlements = $this->defaultEntitlements();
        $entitlementsPath = PlistHelper::getMainAppEntitlements($profilePath, $codesignEntitlements);

    }


    // Private Paths
    private function processWorkPath() {
        return $this->outPutPath."/WorkingDir";
    }
    private function tempPath() {
        return $this->processWorkPath()."/Temp";
    }
    private function tempIconsPath() {
        return $this->tempPath()."/Icons";
    }
    private function extractedPath() {
        return $this->tempPath()."/ExtractedPath";
    }
    private function payloadAppPath() {
        return $this->extractedPath()."/Payload";
    }
    private function extractedAppBundlePath() {
        $path = $this->payloadAppPath();
        $filteredItems = array('..', '.','.DS_Store');
        $files = array_diff(scandir($path, 1), $filteredItems);
        $payloadFiles=[];
        foreach ($files as $file) {
            $path_parts = pathinfo($file);
            $nameP = $path_parts['basename'];
            if (str_contains($nameP, ".png")) {
                array_push($payloadFiles, $path."/".$file);
            }
        }
        return $path."/".$payloadFiles[0];
    }
    private function appPluginPath() {
        return $this->extractedAppBundlePath()."/PlugIns";
    }
    private function appWatchPluginPath() {
        return $this->extractedAppBundlePath()."/Watch";
    }
    private function appFrameworksPath() {
        return $this->extractedAppBundlePath()."/Frameworks";
    }
    private function defaultEntitlements() {
        return $this->processWorkPath()."/app.entitlements";
    }
    private function defaultTempEntitlementsPlist() {
        return $this->processWorkPath()."/tempEnt.plist";
    }
    private function appInfoPlistPath() {
        return $this->extractedAppBundlePath()."/Info.plist";
    }
    private function appInfoPlistDictionary() {
        $infoPlist = $this->appInfoPlistPath();
        $plist = new CFPropertyList();
        $plist->parse($infoPlist);
        return $plist->toArray();

    }


}
