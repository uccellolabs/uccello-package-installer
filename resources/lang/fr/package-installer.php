<?php

return [
    "package-installer" => 'Installer plugin',
    'field' => [
        'zip_file' => 'Fichier du plugin  (.zip)',
    ],
    'button' => [
        'install' => 'Installer',
    ],
    'error' => [
        'zip_mime_type' => 'Le fichier n\‘est pas une archive zip valide.',
        'zip_not_openable' => 'Impossible d\'ouvrir l\'archive zip.',
        'composer_json_not_exists' => 'Le fichier composer.json n\'est pas présent à la racine de l\'archive zip.',
        'package_directory_exists' => 'Le répertoire :path existe déjà.',
    ],
];
