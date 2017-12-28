<?php

function cutStr($str, $txt) {
    $pos = strpos($str, $txt);
    return substr($str, $pos + strlen($txt));
}

$txt = $_POST['txt'];
$txt = cutStr($txt, 'COURSE TITLE		CREDIT		GRADE		COURSE TITLE		CREDIT		GRADE');

preg_match_all('/([0-9]{8})\s+(.*?)\s+([0-9])\s+([ABCDFS]\+*)(?:\s+\n)*((?:(?!GPS)(?![0-9]{8}).*?)*)\s+\n/', $txt, $matches);

for ($i = 0; $i < count($matches[0]); $i++) {
    $subjects[$i]['id'] = $matches[1][$i];
    $subjects[$i]['name'] = $matches[2][$i] . ($matches[5][$i] != null ? ' ' : '') . $matches[5][$i];
    $subjects[$i]['credit'] = (int) $matches[3][$i];
    $subjects[$i]['grade'] = $matches[4][$i];
}

echo "<pre>";
print_r($subjects);
echo "</pre>";