<?php

use Mfd\Fal\DuplicateChecker\Controller\Backend\ElementInformationController;

return [
    'show_item' => [
        'target' => ElementInformationController::class . '::mainAction',
        'path' => '/record/info',
    ],
];
