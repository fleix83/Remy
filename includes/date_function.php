<?php
function formatCustomDate($dateString) {
    $date = new DateTime($dateString);
    $monthNames = [
        1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
        5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember'
    ];
    
    $day   = $date->format('d');
    $month = $monthNames[(int)$date->format('n')];
    $year  = $date->format('Y');
    $time  = $date->format('H:i');
    
    return $day . '. ' . $month . ' ' . $year . ' ' . $time;
}
?>




