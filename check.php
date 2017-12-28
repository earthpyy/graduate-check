<?php

function cutStr($str, $txt) {
    $pos = strpos($str, $txt);
    return substr($str, $pos + strlen($txt));
}

function toInt($gradeStr) {
    switch ($gradeStr) {
        case 'A':
            return 4.0;
        case 'B+':
            return 3.5;
        case 'B':
            return 3.0;
        case 'C+':
            return 2.5;
        case 'C':
            return 2;
        case 'D+':
            return 1.5;
        case 'D':
            return 1.0;
        case 'F':
            return 0.0;
        default:
            return -1.0;
    }
}

function isRegistered($gradeStr) {
    if ($gradeStr == 'F' || $gradeStr == 'W' || $gradeStr == 'U') {
        return false;
    }
    return true;
}

function isPassed($gradeStr) {
    if ($gradeStr == 'F' || $gradeStr == 'W' || $gradeStr == 'U' || $gradeStr == 'X') {
        return false;
    }
    return true;
}

$txt = $_POST['txt'];
$txt = cutStr($txt, 'COURSE TITLE		CREDIT		GRADE		COURSE TITLE		CREDIT		GRADE');

// preg_match_all('/([0-9]{8})\s+(.*?)\s+([0-9])\s+([ABCDFS]\+*)(?:\s+\n)*((?:(?!GPS)(?![0-9]{8}).*?)*)\s+\n/', $txt, $matches);
preg_match_all('/([0-9]{8})\s+(.*?)\s+([0-9])\s+([ABCDFSX]\+*)/', $txt, $matches);

for ($i = 0; $i < count($matches[0]); $i++) {
    $subjects[$i]['id'] = $matches[1][$i];
    // $subjects[$i]['name'] = $matches[2][$i] . ($matches[5][$i] != null ? ' ' : '') . $matches[5][$i];
    $subjects[$i]['credit'] = (int) $matches[3][$i];
    $subjects[$i]['grade'] = $matches[4][$i];
    $subjects[$i]['matched'] = false;
}

// echo "<pre>";
// print_r($subjects);
// echo "</pre>";

$courses = json_decode(file_get_contents('courses/ce_54.json'), true);
$selectives = json_decode(file_get_contents('courses/selective.json'), true);

// required subjects
echo '=== REQUIRED SUBJECTS ===<br>';
foreach ($courses['required'] as $required) {
    $found = false;
    foreach ($subjects as $i => $subject) {
        if ($required == $subject['id']) {
            if (isRegistered($subject['grade'])) {
                $found = true;
                $subjects[$i]['matched'] = true;

                if ($subject['grade'] == 'X') {
                    echo $required . ' (Registered)<br>';
                }
                break;
            }
        }
    }
    if (!$found) {
        echo $required . '<br>';
    }
}

echo '<br>';

// selective subjects
echo '=== SELECTIVE SUBJECTS ===<br>';
foreach ($subjects as $i => $subject) {
    if ($subject['matched']) {
        continue;
    }

    $found = false;
    // find in common selective subjects
    foreach ($selectives as $group => $selective) {
        foreach ($selective['subjects'] as $sj) {
            if ($subject['id'] == $sj || (strpos($sj, 'x') !== false && substr($subject['id'], 0, strpos($sj, 'x')) == substr($sj, 0, strpos($sj, 'x')))) {
                if (isRegistered($subject['grade'])) {
                    $subjects[$i]['matched'] = true;
                    $found = true;

                    if (isPassed($subject['grade'])) {
                        $credits[$group]['passed'] += $subject['credit'];
                    } else {
                        $credits[$group]['registered'] += $subject['credit'];
                    }
                    break;
                }
            }
        }
        if ($found) {
            break;
        }
    }

    // find in another selective subjects
    if (!$found) {
        foreach ($courses['selective'] as $selective) {
            if (array_key_exists('name', $selective)) {
                foreach ($selective['subjects'] as $sj) {
                    if ($subject['id'] == $sj || (strpos($sj, 'x') !== false && substr($subject['id'], 0, strpos($sj, 'x')) == substr($sj, 0, strpos($sj, 'x')))) {
                        if (isRegistered($subject['grade'])) {
                            $subjects[$i]['matched'] = true;
                            $found = true;

                            if (isPassed($subject['grade'])) {
                                $credits[$selective['group']]['passed'] += $subject['credit'];
                            } else {
                                $credits[$selective['group']]['registered'] += $subject['credit'];
                            }
                            break;
                        }
                    }
                }
                if ($found) {
                    break;
                }
            }
        }
    }
}

// free subjects
echo '=== FREE SUBJECTS ===<br>';
foreach ($subjects as $i => $subject) {
    if (!$subject['matched'] && isRegistered($subject['grade'])) {
        $subjects[$i]['matched'] = true;

        if (isPassed($subject['grade'])) {
            $credits['free']['passed'] += $subject['credit'];
        } else {
            $credits['free']['registered'] += $subject['credit'];
        }
    }
}

echo "<pre>";
print_r($credits);
echo "</pre>";