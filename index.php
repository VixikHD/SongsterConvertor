<?php

declare(strict_types=1);

if(empty($_POST)) {
    require "pages/form.html";
    return;
}

$url = $_POST["link"] ?? "";
if(!validateURL($url)) {
    require "pages/invalid-page.html";
    return;
}


$html = file_get_contents($url);

$cssPath = findCSSPath($html);
$css = cleanCSS(file_get_contents("https://www.songsterr.com$cssPath"));

$html = cleanHTML($html);
$html = replaceStylesheetWithCSS($html, $css);
$html = str_replace("<head>", "<head><script>print()</script>", $html);

echo $html;

/**
 * @param string $url
 * @return bool
 */
function validateURL(string $url): bool {
    $isValidURL = (bool)filter_var($url, FILTER_VALIDATE_URL);
    if(!$isValidURL) {
        return false;
    }

    $compare = "https://www.songsterr.com";
    if(strlen($url) < strlen($compare)) {
        return false;
    }

    if(strpos($url, $compare) !== 0) {
        return false;
    }

    return true;
}

/**
 * @param string $html
 * @return string
 */
function findCSSPath(string $html): string {
    $pos = strpos($html, "<link href=\"/");

    $cssPath = "";
    for($i = $pos + 12; $i < strlen($html); $i++) {
        if($html[$i] == "\"") {
            break;
        }

        $cssPath .= $html[$i];
    }

    return $cssPath;
}

/**
 * @param string $html
 * @param string $css
 * @return string
 */
function replaceStylesheetWithCSS(string $html, string $css): string {
    $pos = strpos($html, "<link href=\"");

    $toReplace = "";
    for($i = $pos; $i < strlen($html); $i++) {
        $toReplace .= $html[$i];

        if($html[$i] == ">") {
            break;
        }
    }

    return str_replace($toReplace, "<style>$css</style>", $html);
}

/**
 * @param string $css
 * @return string
 */
function cleanCSS(string $css): string {
    while (($pos = strpos($css, "@media print")) !== false) {
        $css[$pos] = "."; // too much ezz to remove this protection :3333
    }

    return $css;
}


/**
 * @param string $html
 * @return string
 */
function cleanHTML(string $html): string {
    $document = new DOMDocument();
    @$document->loadHTML($html);

    $xpath = new DOMXPath($document);

    foreach (["tablist", "revisions", "fullscreen", "controls", "showroom", "favorite"] as $toRemoveById) {
        $element = $document->getElementById($toRemoveById);
        if($element && $element->nodeType === XML_ELEMENT_NODE) {
            $element->parentNode->removeChild($element);
        }
    }

    foreach($xpath->query('//div[contains(attribute::class, "Cmlfi")]') as $element) {
        $element->parentNode->removeChild($element);
    }
    foreach($xpath->query('//section[contains(attribute::class, "Cyocr")]') as $element) {
        $element->parentNode->removeChild($element);
    }
    foreach($xpath->query('//a[contains(attribute::class, "Clly")]') as $element) {
        $element->parentNode->removeChild($element);
    }
    foreach ($xpath->query("//footer") as $element) {
        $element->parentNode->removeChild($element);
    }

    return $document->saveHTML();
}