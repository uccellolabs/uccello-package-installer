<?php

Route::middleware('web', 'auth')
->namespace('Uccello\PackageInstaller\Http\Controllers')
->name('package-installer.')
->group(function () {

    // This makes it possible to adapt the parameters according to the use or not of the multi domains
    if (!uccello()->useMultiDomains()) {
        $domainParam = '';
    } else {
        $domainParam = '{domain}';
    }

    Route::post($domainParam.'/package-installer/install/zip', 'InstallController@installPackageFromZip')
        ->defaults('module', 'package-installer')
        ->name('install.zip');
});
