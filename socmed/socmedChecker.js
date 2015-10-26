// ---------------------- dependency ----------------------
var Horseman    = require('node-horseman'),
    horseman    = new Horseman(),
    isUrl       = require('is-url'),
    fs          = require('fs'),
    jsonPretty  = require('json-pretty'),
    program     = require('commander');

// ------------------------ get URL ------------------------
program
    .version('0.0.1')
    .option('-url, --url [url]', 'input url')
    .option('-d, --destination [path]', 'input path to store the output')
    .parse(process.argv);

var url     = program.url;
var dest    = program.destination;

if ( !dest ){
    dest = '';
}

// validation url
if ( !isUrl(url) ){
    console.log('ERROR: this is not an url');
    horseman.close();
    process.exit(1);
}

// -------------------- initialization --------------------
var resultSocmed = {
    name    : 'Social Media',
    url     : url,
    checking: []
};

// ------------------- checking meta tag -------------------
var openPage = horseman.open( url );

// ----------------------- Open Graph -----------------------
resultSocmed.checking.push({
    socmedName  : 'Opengraph',
    message     : []
});

// cek og necessary
var ogNecessaryElemName = [
    'Open Graph title',
    'Open Graph type',
    'Open Graph site name',
    'Open Graph url',
    'Open Graph description',
    'Open Graph locale',
];

var ogNecessaryTag = [
    '<meta property="og:title" content="" />',
    '<meta property="og:type" content="" />',
    '<meta property="og:site_name" content="" />',
    '<meta property="og:url" content="" />',
    '<meta property="og:description" content="" />',
    '<meta property="og:locale" content="" />'
];

var ogNecessaryElem = ['meta[property="og:title"]',
    'meta[property="og:type"]',
    'meta[property="og:site_name"]',
    'meta[property="og:url"]',
    'meta[property="og:description"]',
    'meta[property="og:locale"]'
];

ogNecessaryElem.forEach(function (value, index) {
    var isExist = horseman.exists(value);
    var ogDesc;

    // console.log(isExist);
    if ( !isExist ){
        ogDesc = ogNecessaryElemName[index] + ' is not found. Please add this meta tag ' + 
                 ogNecessaryTag[index] + ' to kept the standarization';
      
        resultSocmed.checking[0].message.push({
            error      : 'Error',
            desc       : ogDesc,
        });
    }
});

// cek og:type
var ogTypeElem = 'meta[property="og:type"]';
var isExistOgType = horseman.exists( ogTypeElem );

if ( isExistOgType ){

    // check if og:type is one of these
    var ogType = ['website','books','video','music','books','profile'];

    var getOgType = horseman.attribute('meta[property="og:type"]','content');

    if (ogType.indexOf(getOgType) < 0) {
        ogDesc = 'Your Open Graph type ['+getOgType+'] not match with our standarization. [website, books, video, music, books, profile] Please use one of these type.';
          
            resultSocmed.checking[0].message.push({
                error      : 'Error',
                desc       : ogDesc,
            });
    };

    // if og:type = article
    if ( getOgType === 'article' ){
        var OgArticle = 'Open Graph Article ';
        var articleNecessaryName = [
            OgArticle + 'author',
            OgArticle + 'publisher',
            OgArticle + 'tag',
            OgArticle + 'published time',
            OgArticle + 'modified time',
        ];

        var articleNecessaryTag = [
            '<meta property="article:author" content="" />',
            '<meta property="article:publisher" content="" />',
            '<meta property="article:tag" content="" />',
            '<meta property="article:published_time" content="" />',
            '<meta property="article:modified" content="" />',
        ];
      
        var articleNecessaryElem = [
            'meta[property="article:author"]',
            'meta[property="article:publisher"]',
            'meta[property="article:tag"]',
            'meta[property="article:published_time"]',
            'meta[property="article:modified_time"]'
        ];

        articleNecessaryElem.forEach(function (value, index) {
            var isExist = horseman.exists(value);
            var articleDesc;

            if ( !isExist ) {
                articleDesc = articleNecessaryName[index] + ' is not found. Please add this meta tag ' + 
                              articleNecessaryTag[index] + ' to kept the standarization';
                resultSocmed.checking[0].message.push({
                    error       : 'Error',
                    desc        : articleDesc,
                });
            }
        });
    }
}

// cek og:video
var ogVideoElem = 'meta[property="og:video"]';
var isExistOgVideo = horseman.exists( ogVideoElem );

if ( isExistOgVideo ) {
    var OgVideo = 'Open Graph Video ';
    var videoNecessaryName = [
        OgVideo + 'type',
        OgVideo + 'width',
        OgVideo + 'height',
        'Opeh Graph Image'
    ];

    var videoNecessaryTag = [
        '<meta property="og:video:type" content="" />',
        '<meta property="og:video:width" content="" />',
        '<meta property="og:video:height" content="" />',
        '<meta property="og:image" content="" />',
    ];

    var videoNecessaryElem = [
        'meta[property="og:video:type"]',
        'meta[property="og:video:width"]',
        'meta[property="og:video:height"]',
        'meta[property="og:image"]',
    ];

    videoNecessaryElem.forEach(function (value, index) {
        var isExist = horseman.exists(value);
        var videoDesc;

        if ( !isExist ) {
            videoDesc = videoNecessaryName[index] + ' is not found. Please add this meta tag ' + 
                        videoNecessaryTag[index] + ' to kept the standarization';
            resultSocmed.checking[0].message.push({
                error       : 'Error',
                desc        : videoDesc,
            });
        };
    });
};

// cek og:image
var ogImageElem = 'meta[property="og:image"]';
var isExistOgImage = horseman.exists( ogImageElem );

if ( isExistOgImage ) {
    var OgImage = 'Open Graph Image ';
    var imageNecessaryName = [
        OgImage + 'type',
        OgImage + 'width',
        OgImage + 'height',
    ];

    var imageNecessaryTag = [
        '<meta property="og:image:type" content="" />',
        '<meta property="og:image:width" content="" />',
        '<meta property="og:image:height" content="" />',
    ];

    var imageNecessaryElem = [
        'meta[property="og:image:type"]',
        'meta[property="og:image:width"]',
        'meta[property="og:image:height"]',
    ];

    imageNecessaryElem.forEach(function (value, index) {
        var isExist = horseman.exists(value);
        var imageDesc;

        if ( !isExist ) {
            imageDesc = imageNecessaryName[index] + ' is not found. Please add this meta tag ' + 
                        imageNecessaryTag[index] + ' to kept the standarization';
            resultSocmed.checking[0].message.push({
                error       : 'Error',
                desc        : imageDesc,
            });
        };
    });
};


// ----------------------- Twitter Card -----------------------
resultSocmed.checking.push({
    socmedName  : 'Twitter Card',
    message     : []
});

// cek necessary twitter card
var twitterCardNecessaryName = [
    'Twitter Card Type',
    'Twitter Username of website',
];

var twitterCardNecessaryTag = [
    '<meta name="twitter:card" content="" />',
    '<meta name="twitter:site" content="" />',
];

var twitterCardNecessaryElem = [
    'meta[name="twitter:card"]',
    'meta[name="twitter:site"]',
];

twitterCardNecessaryElem.forEach(function (value, index) {
    var isExist = horseman.exists(value);
    var twitterCardDesc;

    if ( !isExist ) {
        twitterCardDesc = twitterCardNecessaryName[index] + ' is not found. Please add this meta tag ' + 
            twitterCardNecessaryTag[index] + ' to kept the standarization';
        resultSocmed.checking[1].message.push({
            error       : 'Error',
            desc        : twitterCardDesc,
        });
    };
});

// cek twitter card type
var twitterCardTypeElem = 'meta[name="twitter:card"]';
var isExistTwitterCardType = horseman.exists( twitterCardTypeElem );

if ( isExistTwitterCardType ) {
    // check if twitter:card is one of these
    var twitterCardType = ['summary','summary_large_image','app','player'];

    var getTwitterCardType = horseman.attribute('meta[name="twitter:card"]','content');

    if (twitterCardType.indexOf(getTwitterCardType) < 0) {
        twitterCardDesc = 'Your Twitter Card type ['+getTwitterCardType+'] not match with our standarization. [summary, summary_large_image, app, player] Please use one of these type.';
          
            resultSocmed.checking[1].message.push({
                error      : 'Error',
                desc       : twitterCardDesc,
            });
    };

    // cek when twitter card type is summary or summary_large_image
    if ( getTwitterCardType === 'summary' || getTwitterCardType === 'summary_large_image' ) {
        var twitterCardNecessaryTypeName = [
            'Twitter Card Title',
            'Twitter Card Description',
        ];

        var twitterCardNecessaryTypeTag = [
            '<meta name="twitter:title" content="" />',
            '<meta name="twitter:description" content="" />',
        ];

        var twitterCardNecessaryTypeElem = [
            'meta[name="twitter:title"]',
            'meta[name="twitter:description"]',
        ];
        twitterCardNecessaryTypeElem.forEach(function (value, index) {
            var isExist = horseman.exists(value);
            var twitterCardTypeDesc;

            if ( !isExist ) {
                twitterCardDesc = twitterCardNecessaryTypeName[index] + ' is not found. Please add this meta tag ' + 
                    twitterCardNecessaryTypeTag[index] + ' to kept the standarization';
                resultSocmed.checking[1].message.push({
                    error       : 'Error',
                    desc        : twitterCardDesc,
                });
            };
        });
    };

    // cek when twitter card type is app
    if ( getTwitterCardType === 'app' ) {
        var twitterCardNecessaryTypeName = [
            'Twitter Card App ID in App Store for iphone',
            'Twitter Card App ID in App Store for ipad',
            'Twitter Card App ID in Google Play',
        ];

        var twitterCardNecessaryTypeTag = [
            '<meta name="twitter:app:id:iphone" content="" />',
            '<meta name="twitter:app:id:ipad" content="" />',
            '<meta name="twitter:app:id:googleplay" content="" />',
        ];

        var twitterCardNecessaryTypeElem = [
            'meta[name="twitter:app:id:iphone"]',
            'meta[name="twitter:app:id:ipad"]',
            'meta[name="twitter:app:id:googleplay"]',
        ];
        twitterCardNecessaryTypeElem.forEach(function (value, index) {
            var isExist = horseman.exists(value);
            var twitterCardTypeDesc;

            if ( !isExist ) {
                twitterCardDesc = twitterCardNecessaryTypeName[index] + ' is not found. Please add this meta tag ' + 
                    twitterCardNecessaryTypeTag[index] + ' to kept the standarization';
                resultSocmed.checking[1].message.push({
                    error       : 'Error',
                    desc        : twitterCardDesc,
                });
            };
        });
    };

    // cek when twitter card type is player
    if ( getTwitterCardType === 'player' ) {
        var twitterCardNecessaryTypeName = [
            'Twitter Card Player',
            'Twitter Card Player width',
            'Twitter Card Player height',
            'Twitter Card Image for Player',
        ];

        var twitterCardNecessaryTypeTag = [
            '<meta name="twitter:player" content="" />',
            '<meta name="twitter:player:width" content="" />',
            '<meta name="twitter:player:height" content="" />',
            '<meta name="twitter:image" content="" />',
        ];

        var twitterCardNecessaryTypeElem = [
            'meta[name="twitter:player"]',
            'meta[name="twitter:player:width"]',
            'meta[name="twitter:player:height"]',
            'meta[name="twitter:image"]',
        ];
        twitterCardNecessaryTypeElem.forEach(function (value, index) {
            var isExist = horseman.exists(value);
            var twitterCardTypeDesc;

            if ( !isExist ) {
                twitterCardDesc = twitterCardNecessaryTypeName[index] + ' is not found. Please add this meta tag ' + 
                    twitterCardNecessaryTypeTag[index] + ' to kept the standarization';
                resultSocmed.checking[1].message.push({
                    error       : 'Error',
                    desc        : twitterCardDesc,
                });
            };
        });
    };

};

// ----------------------- Facebook Insight -----------------------
resultSocmed.checking.push({
    socmedName  : 'Facebook Insight',
    message     : []
});

// cek necessary facebook insight
var facebookInsightNecessaryName = [
    'Facebook Insight user ID',
    'Facebook Insight app ID',
];

var facebookInsightNecessaryTag = [
    '<meta property="fb:admins" content="" />',
    '<meta property="fb:app_id" content="" />',
];

var facebookInsightNecessaryElem = [
    'meta[name="fb:admins"]',
    'meta[name="fb:app_id"]',
];

facebookInsightNecessaryElem.forEach(function (value, index) {
    var isExist = horseman.exists(value);
    var facebookInsightDesc;

    if ( !isExist ) {
        facebookInsightDesc = facebookInsightNecessaryName[index] + ' is not found. Please add this meta tag ' + 
            facebookInsightNecessaryTag[index] + ' to kept the standarization';
        resultSocmed.checking[2].message.push({
            error       : 'Warning',
            desc        : facebookInsightDesc,
        });
    };
});

// ------------------------ save to json file ------------------------
var toJson = jsonPretty(resultSocmed);

function saveReport () {
    fs.writeFile(dest + 'resultSocmed.json', toJson, function (err) {
        if (err) throw err;
    }); 
}

saveReport();

horseman.close();
