<?php
// More detailed analysis
$kent_dates = [
    '2025-01-06' => 'Mon', '2025-01-07' => 'Tue', '2025-01-09' => 'Thu',
    '2025-01-10' => 'Fri', '2025-01-11' => 'Sat', '2025-01-12' => 'Sun',
    '2025-01-13' => 'Mon', '2025-01-14' => 'Tue', '2025-01-15' => 'Wed',
    '2025-01-16' => 'Thu', '2025-01-19' => 'Sun', '2025-01-20' => 'Mon',
    '2025-01-21' => 'Tue', '2025-01-22' => 'Wed', '2025-01-23' => 'Thu',
    '2025-01-24' => 'Fri', '2025-01-25' => 'Sat', '2025-01-29' => 'Wed', '2025-01-30' => 'Thu'
];

$all_period_dates = [];
$start = new DateTime('2025-01-03');
$end = new DateTime('2025-01-30');
$interval = new DateInterval('P1D');
$period = new DatePeriod($start, $interval, $end->modify('+1 day'));

foreach ($period as $date) {
    $all_period_dates[$date->format('Y-m-d')] = $date->format('D');
}

echo "KENT CLARK DETAILED ANALYSIS\n";
echo "=" . str_repeat("=", 50) . "\n\n";

echo "Pay Period: 2025-01-03 to 2025-01-30\n";
echo "Total Calendar Days: " . count($all_period_dates) . "\n\n";

echo "Kent's Present Dates: " . count($kent_dates) . "\n";
foreach ($kent_dates as $date => $day) {
    echo "  $date ($day)\n";
}

echo "\n\nExcel shows: 27 days\n";
echo "Difference: 27 - 19 = 8 missing days\n\n";

echo "Theory: Maybe Excel uses 28 - (weekends absent) or some other formula?\n";
echo "Let me check all employees to find the pattern...\n";
?>
