<?php

return [
    'exports' => [
        /*
        |--------------------------------------------------------------------------
        | Chunk size
        |--------------------------------------------------------------------------
        |
        | When using FromQuery, the query is automatically chunked.
        | Here you can specify how big the chunk should be.
        |
        */
        'chunk_size' => 1000,

        /*
        |--------------------------------------------------------------------------
        | Pre-calculate formulas during export
        |--------------------------------------------------------------------------
        */
        'pre_calculate_formulas' => false,

        /*
        |--------------------------------------------------------------------------
        | Enable strict null comparison
        |--------------------------------------------------------------------------
        |
        | When enabling strict null comparison empty cells ('') will
        | be added to the sheet.
        */
        'strict_null_comparison' => false,

        /*
        |--------------------------------------------------------------------------
        | CSV Settings
        |--------------------------------------------------------------------------
        |
        | Configure e.g. delimiter, enclosure and line ending for CSV exports.
        |
        */
        'csv' => [
            'delimiter' => ',',
            'enclosure' => '"',
            'line_ending' => PHP_EOL,
            'use_bom' => false,
            'include_separator_line' => false,
            'excel_compatibility' => false,
            'output_encoding' => '',
        ],

        /*
        |--------------------------------------------------------------------------
        | Worksheet properties
        |--------------------------------------------------------------------------
        |
        | Configure e.g. default title, creator, subject,...
        |
        */
        'properties' => [
            'creator' => 'REEFMASTER-CIAKKII',
            'lastModifiedBy' => 'REEFMASTER-CIAKKII',
            'title' => 'Laporan Bulanan AKKII',
            'description' => 'Laporan Bulanan AKKII',
            'subject' => 'Laporan Bulanan AKKII',
            'keywords' => 'akkii,laporan,bulanan',
            'category' => 'Laporan',
            'manager' => '',
            'company' => 'REEFMASTER-CIAKKII',
        ],
    ],

    'imports' => [
        'read_only' => true,
        'ignore_empty' => false,
        'heading_row' => [
            'formatter' => 'slug',
        ],
        'csv' => [
            'delimiter' => ',',
            'enclosure' => '"',
            'escape_character' => '\\',
            'contiguous' => false,
            'input_encoding' => 'UTF-8',
        ],
        'properties' => [
            'creator' => 'REEFMASTER-CIAKKII',
            'lastModifiedBy' => 'REEFMASTER-CIAKKII',
            'title' => 'Laporan Bulanan AKKII',
            'description' => 'Laporan Bulanan AKKII',
            'subject' => 'Laporan Bulanan AKKII',
            'keywords' => 'akkii,laporan,bulanan',
            'category' => 'Laporan',
            'manager' => '',
            'company' => 'REEFMASTER-CIAKKII',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Extension detector
    |--------------------------------------------------------------------------
    |
    | Configure here which writer/reader type should be used when the package
    | needs to guess the correct type based on the extension alone.
    |
    */
    'extension_detector' => [
        'xlsx' => \Maatwebsite\Excel\Excel::XLSX,
        'xlsm' => \Maatwebsite\Excel\Excel::XLSX,
        'xltx' => \Maatwebsite\Excel\Excel::XLSX,
        'xltm' => \Maatwebsite\Excel\Excel::XLSX,
        'xls' => \Maatwebsite\Excel\Excel::XLS,
        'xlt' => \Maatwebsite\Excel\Excel::XLS,
        'ods' => \Maatwebsite\Excel\Excel::ODS,
        'ots' => \Maatwebsite\Excel\Excel::ODS,
        'slk' => \Maatwebsite\Excel\Excel::SLK,
        'xml' => \Maatwebsite\Excel\Excel::XML,
        'gnumeric' => \Maatwebsite\Excel\Excel::GNUMERIC,
        'htm' => \Maatwebsite\Excel\Excel::HTML,
        'html' => \Maatwebsite\Excel\Excel::HTML,
        'csv' => \Maatwebsite\Excel\Excel::CSV,
        'tsv' => \Maatwebsite\Excel\Excel::TSV,
        'pdf' => \Maatwebsite\Excel\Excel::DOMPDF,
    ],

    /*
    |--------------------------------------------------------------------------
    | Value Binder
    |--------------------------------------------------------------------------
    |
    | PhpSpreadsheet offers a way to hook into the process of a value being
    | written to a cell. In there some assumptions are made on how the
    | value should be formatted. If you want to change those defaults,
    | you can implement your own default value binder.
    |
    | Possible value binders:
    |
    | [x] Maatwebsite\Excel\DefaultValueBinder::class
    | [x] PhpOffice\PhpSpreadsheet\Cell\StringValueBinder::class
    | [x] PhpOffice\PhpSpreadsheet\Cell\AdvancedValueBinder::class
    |
    */
    'value_binder' => [
        'default' => \Maatwebsite\Excel\DefaultValueBinder::class,
    ],

    'cache' => [
        /*
        |--------------------------------------------------------------------------
        | Default cell caching driver
        |--------------------------------------------------------------------------
        |
        | By default PhpSpreadsheet keeps all cell values in memory, however when
        | dealing with large files, this might result into memory issues. If you
        | want to mitigate that, you can configure a cell caching driver here.
        | When using the illuminate driver, it will store each value in a the
        | cache store. This can slow down the process, because it needs to
        | store each value. You can use the "batch" store if you want to
        | only persist to the store when the memory limit is reached.
        |
        | Drivers: memory|illuminate|batch
        |
        */
        'driver' => 'memory',

        /*
        |--------------------------------------------------------------------------
        | Batch memory caching
        |--------------------------------------------------------------------------
        |
        | When dealing with the "batch" caching driver, it will only
        | persist to the store when the memory limit is reached.
        | Here you can tweak the memory limit to your liking.
        |
        */
        'batch' => [
            'memory_limit' => 60000,
        ],

        /*
        |--------------------------------------------------------------------------
        | Illuminate cache
        |--------------------------------------------------------------------------
        |
        | When using the "illuminate" caching driver, it will automatically use
        | your default cache store. However if you prefer to have the cell
        | cache on a separate store, you can configure the store name here.
        | You can use any store defined in your cache config. When leaving
        | at "null" it will use the default store.
        |
        */
        'illuminate' => [
            'store' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Transaction Handler
    |--------------------------------------------------------------------------
    |
    | By default the import is wrapped in a transaction. This is useful
    | for when an import may fail and you want to retry it. With the
    | transactions, the previous import gets rolled-back.
    |
    | You can disable the transaction handler by setting this to null.
    | Or you can choose a custom made transaction handler here.
    |
    | Supported handlers: null|db
    |
    */
    'transactions' => [
        'handler' => 'db',
    ],

    'temporary_files' => [
        /*
        |--------------------------------------------------------------------------
        | Local Temporary Path
        |--------------------------------------------------------------------------
        |
        | When exporting and importing files, we use a temporary file, before
        | storing reading or downloading. Here you can customize that path.
        |
        */
        'local_path' => storage_path('app/excel'),

        /*
        |--------------------------------------------------------------------------
        | Remote Temporary Disk
        |--------------------------------------------------------------------------
        |
        | When dealing with a multi server setup with queues in which you
        | cannot rely on having a shared local temporary path, you might
        | want to store the temporary file on a shared disk. During the
        | queue execution, we'll retrieve the temporary file from that
        | location instead. When left to null, it will always use
        | the local path. This setting only has effect when using
        | in conjunction with queued imports and exports.
        |
        */
        'remote_disk' => null,
        'remote_prefix' => null,

        /*
        |--------------------------------------------------------------------------
        | Force Resync
        |--------------------------------------------------------------------------
        |
        | When dealing with a multi server setup as above, it's possible
        | for the clean up that occurs after entire queue has been run to only
        | cleanup the server that the last AfterImportJob runs on. The rest of the server
        | would still have the local temporary file stored on it. In this case your
        | local storage limits can be exceeded and future imports won't be processed.
        | To mitigate this you can set this config value to be true, so that after every
        | queued chunk is processed the local temporary file is deleted on the server that
        | processed it.
        |
        */
        'force_resync_remote' => null,
    ],
];