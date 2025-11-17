<?php
$expected = [
    'Allen, Barry' => 13539.89,
    'Barnes, James' => 11106.00,
    'Barton, Clint' => 14701.00,
    'Curry, Arthur' => 13514.16,
    'Grimm, Ben' => 13283.13,
    'Hall, Carter' => 11993.89,
    'Hall, Shiera' => 10039.00,
    'Hammond, Jim' => 11141.00,
    'Jones, John' => 12253.53,
    'Jordan, Hal' => 11584.89,
    'Kent, Clark' => 10625.00,
    'Lang, Scott' => 11165.00,
    'Laurel, Dinah' => 13433.00,
    'Mason, Rex' => 13623.00,
    'Maximoff, Wanda' => 13509.00,
    'McCoy, Henry' => 14885.00,
    'Murdock, Matthew' => 11416.00,
    'Palmer, Ray' => 13717.89,
    'Parker, Peter' => 8893.35,
    'Prince, Diana' => 12307.89,
    'Queen, Oliver' => 13717.89,
    'Raymond, Ronnie' => 14039.00,
    'Rhodes, James' => 8785.00,
    'Richards, Reed' => 11062.50,
    'Richards, Sue' => 9826.50,
    'Rogers, Steve' => 11555.00,
    'Romanoff, Natasha' => 12497.00,
    'Stark, Toni' => 13389.38,
    'Stewart, John' => 14317.00,
    'Wayne, Bruce' => 12126.25,
];

// Simulate POST
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['start_date'] = '2025-01-03';
$_POST['end_date'] = '2025-01-30';

ob_start();
include 'salary_summary.php';
$output = ob_get_clean();

preg_match_all('/<tr>\s*<td>([^<]+)<\/td>\s*<td>([\d,\.]+)\s+PHP<\/td>\s*<\/tr>/s', $output, $matches, PREG_SET_ORDER);

$current = [];
foreach($matches as $match) {
    $name = trim($match[1]);
    $amount = (float)str_replace(',', '', $match[2]);
    if($name !== 'TOTAL') {
        $current[$name] = $amount;
    }
}

echo "Comparison Report:\n";
echo str_repeat("=", 100) . "\n";
echo sprintf("%-30s %15s %15s %15s %10s\n", "Employee", "Expected", "Current", "Difference", "Status");
echo str_repeat("=", 100) . "\n";

$correct_count = 0;
$off_count = 0;

foreach($expected as $name => $exp) {
    $curr = isset($current[$name]) ? $current[$name] : 0;
    $diff = $curr - $exp;
    $status = (abs($diff) < 0.01) ? '✓' : '✗';
    
    if (abs($diff) < 0.01) {
        $correct_count++;
    } else {
        $off_count++;
    }
    
    echo sprintf("%-30s %15s %15s %15s %10s\n", 
        $name, 
        number_format($exp, 2), 
        number_format($curr, 2), 
        number_format($diff, 2),
        $status
    );
}

echo str_repeat("=", 100) . "\n";
echo "Correct: $correct_count / " . count($expected) . "\n";
echo "Off: $off_count / " . count($expected) . "\n";
?>
