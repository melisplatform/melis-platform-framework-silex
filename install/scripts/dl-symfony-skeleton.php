<?php

$isCliReqs = php_sapi_name() == 'cli' ? true : false;
//third party Lumen
$thirdPartyFolder = !$isCliReqs ? $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'thirdparty/Silex' : 'thirdparty/Silex';

if (!is_dir($thirdPartyFolder)) {
    return MelisPlatformFrameworks\Support\MelisPlatformFrameworks::downloadFrameworkSkeleton('silex');
}else{
    return [
        'success' => true,
        'message' => 'Silex skeleton downloaded successfully'
    ];
}