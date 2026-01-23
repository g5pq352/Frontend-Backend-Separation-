<?php

$uploadFileSize = "每次上傳之檔案大小總計請勿超過" . ini_get("upload_max_filesize") . "。";
$maxFileSize = "<br />$uploadFileSize";

$imagesSize = [
    "imagesSize" => [
        'IW' => 0,
        'IH' => 0,
        'note' => "圖片請上傳寬 710pixel之圖檔。 $maxFileSize",
    ],
    "indexBannerCover" => [
        'IW' => 1920,
        'IH' => 984,
        'note' => "圖片請上傳寬不超過 1920pixel、高不超過 984pixel之圖檔。 $maxFileSize",
    ],
    "indexProductsCover" => [
        'IW' => 384,
        'IH' => 452,
        'note' => "圖片請上傳寬不超過 384pixel、高不超過 452pixel之圖檔。 $maxFileSize",
    ],
    "indexBusinessCover" => [
        'IW' => 814,
        'IH' => 634,
        'note' => "圖片請上傳寬不超過 814pixel、高不超過 634pixel之圖檔。 $maxFileSize",
    ],
    // "history" => [
    //     'IW' => 1000,
    //     'IH' => 1582,
    //     'note' => "圖片請上傳寬不超過 1000pixel、高不超過 1582pixel之圖檔。 $maxFileSize",
    // ],
    "history" => [
        'IW' => 0,
        'IH' => 0,
        'note' => "$maxFileSize",
    ],
    "promotion" => [
        'IW' => 862,
        'IH' => 434,
        'note' => "圖片請上傳寬 862pixel、高 434pixel之圖檔。 $maxFileSize",
    ],
    "blog" => [
        'IW' => 1030,
        'IH' => 570,
        'note' => "圖片請上傳寬 1030pixel、高 570pixel之圖檔。 $maxFileSize",
    ],
    "caseStudiesLogo" => [
        'IW' => 0,
        'IH' => 0,
        'note' => "$maxFileSize",
    ],
    "caseStudies" => [
        'IW' => 752,
        'IH' => 564,
        'note' => "圖片請上傳寬 752pixel、高 564pixel之圖檔。 $maxFileSize",
    ],
    "productCover" => [
        'IW' => 528,
        'IH' => 528,
        'note' => "圖片請上傳寬 528pixel、高 528pixel之圖檔。 $maxFileSize",
    ],
    "product" => [
        'IW' => 838,
        'IH' => 980,
        'note' => "圖片請上傳寬 838pixel、高 980pixel之圖檔。 $maxFileSize",
    ],
    "corporate" => [
        'IW' => 1506,
        'IH' => 704,
        'note' => "圖片請上傳寬 1506pixel、高 704pixel之圖檔。 $maxFileSize",
    ],
    "customization" => [
        'IW' => 348,
        'IH' => 348,
        'note' => "圖片請上傳寬 348pixel、高 348pixel之圖檔。 $maxFileSize",
    ],
    "highlights" => [
        'IW' => 798,
        'IH' => 636,
        'note' => "圖片請上傳寬 798pixel、高 636pixel之圖檔。 $maxFileSize",
    ],
    "ESGReport" => [
        'IW' => 916,
        'IH' => 562,
        'note' => "圖片請上傳寬 916pixel、高 562pixel之圖檔。 $maxFileSize",
    ],
    "sustainabilityNews" => [
        'IW' => 2032,
        'IH' => 1080,
        'note' => "圖片請上傳寬 2032pixel、高 1080pixel之圖檔。 $maxFileSize",
    ],
    "sustainabilityNewsCover" => [
        'IW' => 1764,
        'IH' => 1004,
        'note' => "圖片請上傳寬 1764pixel、高 1004pixel之圖檔。 $maxFileSize",
    ],
    "sustainabilityNewsPattern1" => [
        'IW' => 848,
        'IH' => 566,
        'note' => "圖片請上傳寬 848pixel、高 566pixel之圖檔。 $maxFileSize",
    ],
    "sustainabilityNewsPattern2" => [
        'IW' => 800,
        'IH' => 534,
        'note' => "圖片請上傳寬 800pixel、高 534pixel之圖檔。 $maxFileSize",
    ],
    "latestNews" => [
        'IW' => 2032,
        'IH' => 1080,
        'note' => "圖片請上傳寬 2032pixel、高 1080pixel之圖檔。 $maxFileSize",
    ],
    "latestNewsCover" => [
        'IW' => 1764,
        'IH' => 1004,
        'note' => "圖片請上傳寬 1764pixel、高 1004pixel之圖檔。 $maxFileSize",
    ],
    "latestNewsPattern1" => [
        'IW' => 848,
        'IH' => 566,
        'note' => "圖片請上傳寬 848pixel、高 566pixel之圖檔。 $maxFileSize",
    ],
    "latestNewsPattern2" => [
        'IW' => 800,
        'IH' => 534,
        'note' => "圖片請上傳寬 800pixel、高 534pixel之圖檔。 $maxFileSize",
    ],
    "location" => [
        'IW' => 860,
        'IH' => 500,
        'note' => "圖片請上傳寬 860pixel、高 500pixel之圖檔。 $maxFileSize",
    ],
    "benefits" => [
        'IW' => 420,
        'IH' => 280,
        'note' => "圖片請上傳寬 420pixel、高 280pixel之圖檔。 $maxFileSize",
    ],
    "classicsliderCover" => [
        'IW' => 1920,
        'IH' => 1080,
        'note' => "圖片請上傳寬 1920pixel、高 1080pixel之圖檔。 $maxFileSize",
    ],
    "classicCover" => [
        'IW' => 1920,
        'IH' => 980,
        'note' => "圖片請上傳寬不超過 1920pixel、高不超過 980pixel之圖檔。 $maxFileSize",
    ],
    "classicCoverMobile" => [
        'IW' => 1024,
        'IH' => 1278,
        'note' => "圖片請上傳寬不超過 1024pixel、高不超過 1821pixel之圖檔。 $maxFileSize",
    ],
    "classicCoverItem" => [
        'IW' => 0,
        'IH' => 0,
        'note' => "圖片請上傳寬不超過 300pixel、高不超過 470pixel之圖檔。 $maxFileSize",
    ],
    "classicCoverOne" => [
        'IW' => 1218,
        'IH' => 812,
        'note' => "圖片請上傳寬不超過 1218pixel、高不超過 812pixel之圖檔。 $maxFileSize",
    ],
    "classicCoverTwo" => [
        'IW' => 1072,
        'IH' => 714,
        'note' => "圖片請上傳寬不超過 1072pixel、高不超過 714pixel之圖檔。 $maxFileSize",
    ],
    "classic" => [
        'IW' => 1038,
        'IH' => 600,
        'note' => "圖片請上傳寬不超過 1038pixel、高不超過 600pixel之圖檔。 $maxFileSize",
    ],
    "classicCoverSlider" => [
        'IW' => 1810,
        'IH' => 570,
        'note' => "圖片請上傳寬不超過 1810pixel、高不超過 570pixel之圖檔。 $maxFileSize",
    ],
    "historyCover" => [
        'IW' => 754,
        'IH' => 566,
        'note' => "圖片請上傳寬不超過 754pixel、高不超過 566pixel之圖檔。 $maxFileSize",
    ],
    "newsCover" => [
        'IW' => 362,
        'IH' => 204,
        'note' => "圖片請上傳寬 362pixel、高 204pixel之圖檔。 $maxFileSize",
    ],
    "news" => [
        'IW' => 1030,
        'IH' => 580,
        'note' => "圖片請上傳寬 1030pixel、高 580pixel之圖檔。 $maxFileSize",
    ],
    "gamesCoverOne" => [ //封面圖（直式）
        'IW' => 357,
        'IH' => 441,
        'note' => "圖片請上傳寬 357pixel、高 441pixel之圖檔。 $maxFileSize",
    ],
    "gamesCover" => [   //封面圖（橫式）
        'IW' => 1227,
        'IH' => 542,
        'note' => "圖片請上傳寬 1227pixel、高 542pixel之圖檔。 $maxFileSize",
    ],
    "games" => [    //內容圖
        'IW' => 1227,
        'IH' => 542,
        'note' => "圖片請上傳寬 1227pixel、高 542pixel之圖檔。 $maxFileSize",
    ],
    "gamesTran" => [    //去背主圖
        'IW' => 564,
        'IH' => 526,
        'note' => "圖片請上傳寬 564pixel、高 526pixel之圖檔。 $maxFileSize",
    ],
    "gamesBg" => [  //背景（橫）
        'IW' => 850,
        'IH' => 240,
        'note' => "圖片請上傳寬 850pixel、高 240pixel之圖檔。 $maxFileSize",
    ],
    "csrCover" => [
        'IW' => 1260,
        'IH' => 1050,
        'note' => "圖片請上傳寬不超過 1260pixel、高不超過 1050pixel之圖檔。 $maxFileSize",
    ],
    "csrCoverOne" => [
        'IW' => 1260,
        'IH' => 1050,
        'note' => "圖片請上傳寬不超過 1260pixel、高不超過 1050pixel之圖檔。 $maxFileSize",
    ],
    "progress" => [
        'IW' => 920,
        'IH' => 516,
        'note' => "圖片請上傳寬不超過 920pixel、高不超過 516pixel之圖檔。 $maxFileSize",
    ],
    "progressCover" => [
        'IW' => 920,
        'IH' => 516,
        'note' => "圖片請上傳寬不超過 920pixel、高不超過 516pixel之圖檔。 $maxFileSize",
    ],
    "latestCover" => [
        'IW' => 682,
        'IH' => 384,
        'note' => "圖片請上傳寬不超過 682pixel、高不超過 384pixel之圖檔。 $maxFileSize",
    ],
    "latestCoverSlider" => [
        'IW' => 682,
        'IH' => 384,
        'note' => "圖片請上傳寬不超過 682pixel、高不超過 384pixel之圖檔。 $maxFileSize",
    ],
    "latestCoverBanner" => [
        'IW' => 1920,
        'IH' => 980,
        'note' => "圖片請上傳寬不超過 1920pixel、高不超過 980pixel之圖檔。 $maxFileSize",
    ],
    "latestCoverBannerMobile" => [
        'IW' => 1024,
        'IH' => 1821,
        'note' => "圖片請上傳寬不超過 1024pixel、高不超過 1821pixel之圖檔。 $maxFileSize",
    ],
    "latestCoverVideo" => [
        'IW' => 1720,
        'IH' => 860,
        'note' => "圖片請上傳寬不超過 1720pixel、高不超過 860pixel之圖檔。 $maxFileSize",
    ],
    "latestCoverIntro" => [
        'IW' => 1000,
        'IH' => 560,
        'note' => "圖片請上傳寬不超過 1000pixel、高不超過 560pixel之圖檔。 $maxFileSize",
    ],
    "latestCoverIntro_two" => [
        'IW' => 1000,
        'IH' => 560,
        'note' => "圖片請上傳寬不超過 1000pixel、高不超過 560pixel之圖檔。 $maxFileSize",
    ],
    "latestCoverIntro_three" => [
        'IW' => 1000,
        'IH' => 560,
        'note' => "圖片請上傳寬不超過 1000pixel、高不超過 560pixel之圖檔。 $maxFileSize",
    ],
    "latestSwitch" => [
        'IW' => 710,
        'IH' => 980,
        'note' => "圖片請上傳寬不超過 710pixel、高不超過 980pixel之圖檔。 $maxFileSize",
    ],
    "latestCoverReview" => [
        'IW' => 0,
        'IH' => 0,
        'note' => "圖片請上傳寬不超過 1920pixel之圖檔。 $maxFileSize",
    ],
    "latest" => [
        'IW' => 1030,
        'IH' => 580,
        'note' => "圖片請上傳寬不超過 1030pixel、高不超過 580pixel之圖檔。 $maxFileSize",
    ],
    "latestCoverBack" => [
        'IW' => 1920,
        'IH' => 980,
        'note' => "圖片請上傳寬不超過 1920pixel、高不超過 980pixel之圖檔。 $maxFileSize",
    ],
    "latestSliderIndex" => [
        'IW' => 1810,
        'IH' => 570,
        'note' => "圖片請上傳寬不超過 1810pixel、高不超過 570pixel之圖檔。 $maxFileSize",
    ],
    "latestSliderIndexMobile" => [
        'IW' => 1024,
        'IH' => 1821,
        'note' => "圖片請上傳寬不超過 1024pixel、高不超過 1821pixel之圖檔。 $maxFileSize",
    ],
    "asset" => [
        'IW' => 1156,
        'IH' => 650,
        'note' => "圖片請上傳寬不超過 1156pixel、高不超過 650pixel之圖檔。 $maxFileSize",
    ],
    "assetCover" => [
        'IW' => 1100,
        'IH' => 910,
        'note' => "圖片請上傳寬不超過 1100pixel、高不超過 910pixel之圖檔。 $maxFileSize",
    ],
    "assetCoverBanner" => [
        'IW' => 1920,
        'IH' => 1080,
        'note' => "圖片請上傳寬不超過 1920pixel、高不超過 1080pixel之圖檔。 $maxFileSize",
    ],
    "honorsCover" => [
        'IW' => 478,
        'IH' => 324,
        'note' => "圖片請上傳寬不超過 478pixel、高不超過 324pixel之圖檔。 $maxFileSize",
    ],
    "esgHappyCover" => [
        'IW' => 1252,
        'IH' => 484,
        'note' => "圖片請上傳寬不超過 1252pixel、高不超過 484pixel之圖檔。 $maxFileSize",
    ],
    "esgCultureCover" => [
        'IW' => 1252,
        'IH' => 484,
        'note' => "圖片請上傳寬不超過 1252pixel、高不超過 484pixel之圖檔。 $maxFileSize",
    ],
    "experienceSliderCover" => [
        'IW' => 1920,
        'IH' => 980,
        'note' => "圖片請上傳寬不超過 1920pixel、高不超過 980pixel之圖檔。 $maxFileSize",
    ],
    // "corporateSetCover" => [
    //     'IW' => 1506,
    //     'IH' => 704,
    //     'note' => "圖片請上傳寬不超過 1506pixel、高不超過 704pixel之圖檔。 $maxFileSize",
    // ],
    "corporateSet" => [
        'IW' => 1506,
        'IH' => 704,
        'note' => "圖片請上傳寬不超過 1506pixel、高不超過 704pixel之圖檔。 $maxFileSize",
    ],
    // "highlightsSetCover" => [
    //     'IW' => 798,
    //     'IH' => 636,
    //     'note' => "圖片請上傳寬不超過 798pixel、高不超過 636pixel之圖檔。 $maxFileSize",
    // ],
    "highlightsSet" => [
        'IW' => 798,
        'IH' => 636,
        'note' => "圖片請上傳寬不超過 798pixel、高不超過 636pixel之圖檔。 $maxFileSize",
    ],
    "popSetCover" => [
        'IW' => 0,
        'IH' => 0,
        'note' => "$maxFileSize",
    ],
    "indexBannerSetCover" => [
        'IW' => 1920,
        'IH' => 984,
        'note' => "圖片請上傳寬不超過 1920pixel、高不超過 984pixel之圖檔。 $maxFileSize",
    ],
    "cakeSliderCover" => [
        'IW' => 482,
        'IH' => 626,
        'note' => "圖片請上傳寬不超過 482pixel、高不超過 626pixel之圖檔。 $maxFileSize",
    ],
    "experienceHall" => [
        'IW' => 812,
        'IH' => 410,
        'note' => "圖片請上傳寬不超過 812pixel、高不超過 410pixel之圖檔。 $maxFileSize",
    ],
    "experienceHallCover" => [
        'IW' => 1144,
        'IH' => 880,
        'note' => "圖片請上傳寬不超過 1144pixel、高不超過 880pixel之圖檔。 $maxFileSize",
    ],
    "experienceHallCoverOne" => [
        'IW' => 380,
        'IH' => 380,
        'note' => "圖片請上傳寬不超過 380pixel、高不超過 380pixel之圖檔。 $maxFileSize",
    ],
    "experienceHallTag" => [
        'IW' => 48,
        'IH' => 48,
        'note' => "圖片請上傳寬不超過 48pixel、高不超過 48pixel之圖檔。 $maxFileSize",
    ],
    "experienceCover" => [
        'IW' => 486,
        'IH' => 446,
        'note' => "圖片請上傳寬不超過 486pixel、高不超過 446pixel之圖檔。 $maxFileSize",
    ],
    "experienceBanner" => [
        'IW' => 1920,
        'IH' => 980,
        'note' => "圖片請上傳寬不超過 1920pixel、高不超過 980pixel之圖檔。 $maxFileSize",
    ],
    "experienceMap" => [
        'IW' => 600,
        'IH' => 400,
        'note' => "圖片請上傳寬不超過 600pixel、高不超過 400pixel之圖檔。 $maxFileSize",
    ],
];
?>