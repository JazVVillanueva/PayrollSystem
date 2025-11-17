<?php
// Check what days Kent is absent and if they're weekends/holidays
$start = new DateTime('2025-01-03');
$end = new DateTime('2025-01-30');
$interval = new DateInterval('P1D');
$period = new DatePeriod($start, $interval, $end->modify('+1 day'));

$kent_present = ['2025-01-06', '2025-01-07', '2025-01-09', '2025-01-10', '2025-01-11', '2025-01-12', 
                 '2025-01-13', '2025-01-14', '2025-01-15', '2025-01-16', '2025-01-19', '2025-01-20',
                 '2025-01-21', '2025-01-22', '2025-01-23', '2025-01-24', '2025-01-25', '2025-01-29', '2025-01-30'];

echo "Kent Clark - Absence Analysis\n\n";
echo "Pay Period: Jan 3-30, 2025 (28 days)\n";
echo "Kent Present: " . count($kent_present) . " days\n\n";

echo "Absent Days:\n";
foreach ($period as $date) {
    $dateStr = $date->format('Y-m-d');
    if (!in_array($dateStr, $kent_present)) {
        $dayName = $date->format('l');  // Day name
        $isWeekend = ($dayName == 'Saturday' || $dayName == 'Sunday');
        $isHoliday = in_array($dateStr, ['2025-01-01', '2025-01-05']);
        
        echo "$dateStr ($dayName)";
        if ($isWeekend) echo " - WEEKEND";
        if ($isHoliday) echo " - HOLIDAY";
        echo "\n";
    }
}

echo "\n\nHypothesis: Maybe Excel 'Days of Work' = Weekdays only?\n";
echo "Let me count weekdays in period:\n\n";

$weekdays = 0;
$weekends = 0;
foreach ($period as $date) {
    $dayName = $date->format('l');
    if ($dayName == 'Saturday' || $dayName == 'Sunday') {
        $weekends++;
    } else {
        $weekdays++;
    }
}

echo "Total Weekdays (Mon-Fri): $weekdays\n";
echo "Total Weekends (Sat-Sun): $weekends\n";
echo "Total Days: 28\n\n";

echo "If Excel Days = Weekdays present + SIL days...\n";
echo "Or Excel Days = All calendar weekdays (regardless of work)...\n";
?>
