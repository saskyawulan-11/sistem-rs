<?php

return [
    /*
    |--------------------------------------------------------------------------
    | QR Code Backend
    |--------------------------------------------------------------------------
    |
    | This option controls the default QR code backend that will be used
    | by the framework. You may set this to any of the backends defined
    | in the "backends" array below.
    |
    | Supported: "gd", "imagick"
    |
    */

    'backend' => 'gd',

    /*
    |--------------------------------------------------------------------------
    | QR Code Backends
    |--------------------------------------------------------------------------
    |
    | Here you may configure the QR code backends for your application as
    | well as their drivers. You may also add additional backends as needed.
    |
    */

    'backends' => [
        'gd' => [
            'driver' => 'gd',
        ],
        'imagick' => [
            'driver' => 'imagick',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | QR Code Size
    |--------------------------------------------------------------------------
    |
    | This option controls the default size of the QR code.
    |
    */

    'size' => 100,

    /*
    |--------------------------------------------------------------------------
    | QR Code Format
    |--------------------------------------------------------------------------
    |
    | This option controls the default format of the QR code.
    |
    | Supported: "png", "svg", "eps"
    |
    */

    'format' => 'png',

    /*
    |--------------------------------------------------------------------------
    | QR Code Error Correction
    |--------------------------------------------------------------------------
    |
    | This option controls the default error correction level of the QR code.
    |
    | Supported: "L", "M", "Q", "H"
    |
    */

    'error_correction' => 'M',

    /*
    |--------------------------------------------------------------------------
    | QR Code Margin
    |--------------------------------------------------------------------------
    |
    | This option controls the default margin of the QR code.
    |
    */

    'margin' => 4,
];
