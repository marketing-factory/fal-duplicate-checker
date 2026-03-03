<?php

$EM_CONF['fal_duplicate_checker'] = [
    'title' => 'FAL Duplicate Checker',
    'description' => 'Detects duplicate files in FAL (sys_file) and displays a warning in the TYPO3 backend file info view.',
    'category' => 'be',
    'author' => 'Marketing Factory Digital GmbH',
    'author_email' => 'info@marketing-factory.de',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
