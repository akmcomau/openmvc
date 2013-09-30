<?php
$_FILE_MANAGER = [
		"_comment" => "IMPORTANT : go to the wiki page to know about options configuration https =>//github.com/simogeo/Filemanager/wiki/Filemanager-configuration-file",
    "options" => [
        "culture" => "en",
        "lang" => "php",
        "defaultViewMode" => "grid",
        "autoload" => TRUE,
        "showFullPath" => FALSE,
        "browseOnly" => FALSE,
        "showConfirmation" => TRUE,
        "showThumbs" => TRUE,
        "generateThumbnails" => TRUE,
        "searchBox" => TRUE,
        "listFiles" => TRUE,
        "fileSorting" => "default",
        "chars_only_latin" => TRUE,
        "dateFormat" => "d M Y H =>i",
        "serverRoot" => TRUE,
        "fileRoot" => '/',
        "relPath" => FALSE,
        "logger" => FALSE,
        "plugins" => []
    ],
    "security" => [
        "uploadPolicy" => "DISALLOW_ALL",
        "uploadRestrictions" => [
            "jpg",
            "jpeg",
            "gif",
            "png",
            "svg",
            "txt",
            "pdf",
            "odp",
            "ods",
            "odt",
            "rtf",
            "doc",
            "docx",
            "xls",
            "xlsx",
            "ppt",
            "pptx",
            "ogv",
            "mp4",
            "webm",
            "ogg",
            "mp3",
            "wav"
        ]
    ],
    "upload" => [
        "overwrite" => FALSE,
        "imagesOnly" => FALSE,
        "fileSizeLimit" => 16
    ],
    "exclude" => [
        "unallowed_files" => [
            ".htaccess"
        ],
        "unallowed_dirs" => [
            "_thumbs",
            ".CDN_ACCESS_LOGS",
            "cloudservers"
        ],
        "unallowed_files_REGEXP" => "/^\\./uis",
        "unallowed_dirs_REGEXP" => "/^\\./uis"
    ],
    "images" => [
        "imagesExt" => [
            "jpg",
            "jpeg",
            "gif",
            "png",
            "svg"
        ]
    ],
    "videos" => [
        "showVideoPlayer" => TRUE,
        "videosExt" => [
            "ogv",
            "mp4",
            "webm"
        ],
        "videosPlayerWidth" => 400,
        "videosPlayerHeight" => 222
    ],
    "audios" => [
        "showAudioPlayer" => TRUE,
        "audiosExt" => [
            "ogg",
            "mp3",
            "wav"
        ]
    ],
    "extras" => [
        "extra_js" => [],
        "extra_js_async" => TRUE
    ],
    "icons" => [
        "path" => "/core/themes/default/images/file_manager/fileicons/",
        "directory" => "_Open.png",
        "default" => "default.png"
    ]
 ];
