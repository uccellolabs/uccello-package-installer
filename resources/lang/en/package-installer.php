<?php

return [
    "package-installer" => 'Install packages',
    'field' => [
        'zip_file' => 'Package file (.zip)',
    ],
    'button' => [
        'install' => 'Install',
    ],
    'error' => [
        'zip_mime_type' => 'The file is not a valid zip archive.',
        'zip_not_openable' => 'Impossible to open zip archive.',
        'composer_json_not_exists' => 'There is no composer.json file in the zip archive.',
        'package_directory_exists' => 'The directory ":path" already exists.',
    ],
    'success' => [
        'installed' => 'The package was correctly installed!',
    ],
];
