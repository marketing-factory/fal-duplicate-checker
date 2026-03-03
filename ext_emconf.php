<?php

declare(strict_types=1);

/*
 * This file is part of the package mfd/fal-duplicate-checker.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

$EM_CONF['fal_duplicate_checker'] = [
    'title' => 'FAL Duplicate Checker',
    'description' => 'Detects duplicate files in FAL (sys_file) and displays a warning in the TYPO3 backend file info view.',
    'category' => 'be',
    'author' => 'Marketing Factory Digital GmbH',
    'author_email' => 'info@marketing-factory.de',
    'state' => 'stable',
    'version' => '1.0.2',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
