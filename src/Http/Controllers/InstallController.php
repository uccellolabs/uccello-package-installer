<?php

namespace Uccello\PackageInstaller\Http\Controllers;

use Illuminate\Http\Request;
use Uccello\Core\Http\Controllers\Core\IndexController;
use Uccello\Core\Models\Domain;
use Uccello\Core\Models\Module;
use \ZipArchive;

class InstallController extends IndexController
{
    const ZIP_MIME_TYPE_ERROR = 'error.zip_mime_type';
    const ZIP_NOT_OPENABLE_ERROR = 'error.zip_not_openable';
    const COMPOSER_JSON_NOT_EXISTS_ERROR = 'error.composer_json_not_exists';
    const PACKAGE_DIRECTORY_EXISTS_ERROR = 'error.package_directory_exists';

    /**
     * Name of the file input in the form.
     *
     * @var string
     */
    protected $zipInputFieldName = 'zip_file';

    /**
     * Zip Archive instance
     *
     * @var ZipArchive
     */
    protected $zip;

    /**
     * Package information (fullName, vendor, package)
     *
     * @var Object
     */
    protected $packageInformation;

    /**
     * Check user permissions
     */
    protected function checkPermissions()
    {
        $this->middleware('uccello.permissions:admin');
    }

    /**
     * Installs a package from a zip file.
     *
     * @param \Uccello\Core\Models\Domain|null $domain
     * @param \Uccello\Core\Models\Module $module
     * @param \Illuminate\Http\Request $request
     *
     * @return void
     */
    public function installPackageFromZip(?Domain $domain, Module $module, Request $request)
    {
        $this->preProcess($domain, $module, $request);

        // Check file mime-type
        if (!$this->isMimeTypeCorrect('application/zip')) {
            return $this->displayErrorMessage(static::ZIP_MIME_TYPE_ERROR);
        }

        // Open zip file
        if (!$this->openZipFile()) {
            return $this->displayErrorMessage(static::ZIP_NOT_OPENABLE_ERROR);
        }

        // Check if contains composer.json file
        if (!$this->composerJsonFileExists()) {
            return $this->displayErrorMessage(static::COMPOSER_JSON_NOT_EXISTS_ERROR);
        }

        // Check if package directory already exists
        if ($this->packageDirectoryExists()) {
            return $this->displayErrorMessage(static::PACKAGE_DIRECTORY_EXISTS_ERROR, [
                'path' => $this->getPackageDirectoryPath()
            ]);
        }

        // Unzip package
        $this->unzipPackage();

        // Require packckage
        $this->requirePackageIntoComposerJsonFile();

        // Install package
        $this->installPackageWithComposer();
    }

    /**
     * Checks if the file's mime type is part of allowed mime types.
     *
     * @param string $expectedMimeType
     *
     * @return boolean
     */
    protected function isMimeTypeCorrect(string $expectedMimeType) : bool
    {
        $mimeType = $this->request->file($this->zipInputFieldName)->getMimeType();

        return $mimeType === $expectedMimeType;
    }

    /**
     * Returns uploaded file path.
     *
     * @return string
     */
    protected function getUploadedFilePath() : string
    {
        return $this->request->file($this->zipInputFieldName)->path();
    }

    /**
     * Opens zip archive and returns if it succeded.
     *
     * @return bool
     */
    protected function openZipFile()
    {
        $uploadedZipFilePath = $this->getUploadedFilePath();

        $this->zip = new ZipArchive;
        return $this->zip->open($uploadedZipFilePath);
    }

    /**
     * Checks if composer.json file exists in the zip file.
     *
     * @return bool
     */
    protected function composerJsonFileExists()
    {
        return $this->zip->locateName('composer.json', ZipArchive::FL_NODIR);
    }

    /**
     * Extracts and analyses composer.json file, and returns package information.
     * If information are already knowed, returns them directly without reading composer.json another time.
     *
     * @return object
     */
    protected function getPackageInformation()
    {
        if (!empty($this->packageInformation)) {
            return $this->packageInformation;
        }

        $composerJsonContent = $this->zip->getFromIndex($this->zip->locateName('composer.json', ZipArchive::FL_NODIR));
        $composerJson = json_decode($composerJsonContent);
        $packageParts = explode('/', $composerJson->name);
        $vendorName = $packageParts[0];
        $packageName = $packageParts[1];

        $this->packageInformation = (object) [
            'fullName' => $composerJson->name,
            'vendor' => $vendorName,
            'package' => $packageName
        ];

        return $this->packageInformation;
    }

    /**
     * Returns package full name (vendor/package).
     *
     * @return string
     */
    protected function getPackageFullName()
    {
        $packageInformation = $this->getPackageInformation();

        return $packageInformation->fullName;
    }

    /**
     * Returns package name without vendor.
     *
     * @return string
     */
    protected function getPackageName()
    {
        $packageInformation = $this->getPackageInformation();

        return $packageInformation->package;
    }

    /**
     * Returns path of vendor directory in which packages will be written.
     *
     * @param boolean $absolutePath
     *
     * @return string
     */
    protected function getPackageVendorDirectoryPath($absolutePath = true)
    {
        $packageInformation = $this->getPackageInformation();

        $vendor = $packageInformation->vendor;

        $packageDirectory = rtrim(config('uccello.packages.local_directory', 'packages'), '/');

        $path = "$packageDirectory/$vendor";

        return $absolutePath ? base_path($path) : $path;
    }

    /**
     * Return path of package directory
     *
     * @param boolean $absolutePath
     *
     * @return void
     */
    protected function getPackageDirectoryPath($absolutePath = true)
    {
        $packageVendorPath = $this->getPackageVendorDirectoryPath($absolutePath);
        $packageName = $this->getPackageName();

        return $packageVendorPath.'/'.$packageName;
    }

    /**
     * Checks if package directory already exits.
     *
     * @return bool
     */
    protected function packageDirectoryExists()
    {
        return is_dir($this->getPackageDirectoryPath());
    }

    /**
     * Unzips package into vendor directory.
     *
     * @return void
     */
    protected function unzipPackage()
    {
        $unzippedDirectoryName = rtrim($this->zip->getNameIndex(0), '/');

        $packageVendorDirectoryPath = $this->getPackageVendorDirectoryPath();
        $packageDirectoryPath = $this->getPackageDirectoryPath();

        if (!is_dir($packageVendorDirectoryPath)) {
            mkdir($packageVendorDirectoryPath);
        }

        $this->zip->extractTo($packageVendorDirectoryPath);
        rename("$packageVendorDirectoryPath/$unzippedDirectoryName", $packageDirectoryPath);

        $this->zip->close();
    }

    /**
     * Returns application's composer.json file content as JSON object.
     *
     * @return Object
     */
    protected function getAppComposerJson()
    {
        $appComposerJsonContent = file_get_contents(base_path('composer.json'));
        return json_decode($appComposerJsonContent);
    }

    /**
     * Appends package local repository and require the package into application's composer.json file.
     *
     * @return void
     */
    protected function requirePackageIntoComposerJsonFile()
    {
        $appComposerJson = $this->getAppComposerJson();

        $packageFullName = $this->getPackageFullName();

        if (!isset($appComposerJson->require->{$packageFullName})) {
            $appComposerJson->require->{$packageFullName} = '*';
        }

        // Update application's composer.json file
        $this->updateAppComposerJson($appComposerJson);

        // Add local repository for installed package
        $this->addLocalRepositoryForPackage();
    }

    /**
     * Adds local repository to application's composer.json file.
     *
     * @return void
     */
    protected function addLocalRepositoryForPackage()
    {
        $appComposerJson = $this->getAppComposerJson();

        // Add repository if does not exist
        if (!isset($appComposerJson->repositories)) {
            $appComposerJson->repositories = [];
        }

        $repository = [
            'type' => 'path',
            'url' => './' . $this->getPackageDirectoryPath(false)
        ];

        $appComposerJson->repositories[] = $repository;

        $this->updateAppComposerJson($appComposerJson);
    }

    /**
     * Updates application's composer.json file with new content.
     *
     * @param Object $contentAsJson
     *
     * @return void
     */
    protected function updateAppComposerJson($contentAsJson)
    {
        $content = str_replace('\/', '/', json_encode($contentAsJson, JSON_PRETTY_PRINT));
        file_put_contents(base_path('composer.json'), $content);
    }

    /**
     * Executes "composer update" command to install the new package.
     *
     * @return string|null
     */
    protected function installPackageWithComposer()
    {
        $packageName = $this->getPackageName();
        $basePath = base_path();

        return shell_exec("cd $basePath && composer update $packageName");
    }

    /**
     * Informs Uccello to display an error message into a notification.
     *
     * @param string $errorCode
     * @param array $replace
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function displayErrorMessage($errorCode, $replace = [])
    {
        ucnotify(uctrans($errorCode, $this->module, $replace), 'error');
        return redirect()->back();
    }
}
