<?php
$php_values = [
    'Allen, Barry' => 13559.89,
    'Barnes, James' => 11126.00,
    'Barton, Clint' => 14721.00,
    'Curry, Arthur' => 13514.66,
    'Grimm, Ben' => 13368.13,
    'Hall, Carter' => 11993.89,
    'Hall, Shiera' => 10569.00,
    'Hammond, Jim' => 9601.00,
    'Jones, John' => 12273.53,
    'Jordan, Hal' => 11374.89,
    'Kent, Clark' => 10645.00,
    'Lang, Scott' => 11425.00,
    'Laurel, Dinah' => 13453.00,
    'Mason, Rex' => 13643.00,
    'Maximoff, Wanda' => 13499.00,
    'McCoy, Henry' => 14425.00,
    'Murdock, Matthew' => 10341.00,
    'Palmer, Ray' => 11049.89,
    'Parker, Peter' => 8493.35,
    'Prince, Diana' => 12827.89,
    'Queen, Oliver' => 13737.89,
    'Raymond, Ronnie' => 14059.00,
    'Rhodes, James' => 7505.00,
    'Richards, Reed' => 10742.50,
    'Richards, Sue' => 10126.50,
    'Rogers, Steve' => 11575.00,
    'Romanoff, Natasha' => 12517.00,
    'Stark, Toni' => 14714.38,
    'Stewart, John' => 14337.00,
    'Wayne, Bruce' => 12106.29
];

$excel_values = [
    'Kent, Clark' => 10625.00,
    'Wayne, Bruce' => 12126.29,
    'Prince, Diana' => 12307.89,
    'Allen, Barry' => 13539.89,
    'Jordan, Hal' => 11354.89,
    'Curry, Arthur' => 13514.16,
    'Jones, John' => 12253.53,
    'Queen, Oliver' => 13717.89,
    'Palmer, Ray' => 11029.89,
    'Hall, Carter' => 11993.89,
    'Mason, Rex' => 13623.00,
    'Laurel, Dinah' => 13433.00,
    'Stewart, John' => 14317.00,
    'Hall, Shiera' => 10039.00,
    'Raymond, Ronnie' => 14039.00,
    'Rogers, Steve' => 11555.00,
    'Barton, Clint' => 14701.00,
    'Maximoff, Wanda' => 13509.00,
    'Romanoff, Natasha' => 12497.00,
    'McCoy, Henry' => 14885.00,
    'Richards, Reed' => 11062.50,
    'Richards, Sue' => 9826.50,
    'Grimm, Ben' => 13283.13,
    'Hammond, Jim' => 11141.00,
    'Stark, Toni' => 13389.38,
    'Rhodes, James' => 8785.00,
    'Parker, Peter' => 8893.35,
    'Lang, Scott' => 11165.00,
    'Barnes, James' => 11106.00,
    'Murdock, Matthew' => 11416.00
];

echo "COMPARISON - EMPLOYEES WITH DIFFERENCES:\n";
echo str_repeat("=", 80) . "\n";
echo str_pad("Employee", 25) . str_pad("PHP", 15) . str_pad("Excel", 15) . "Difference\n";
echo str_repeat("-", 80) . "\n";

$correct = 0;
$incorrect = 0;

foreach($excel_values as $name => $excel_val) {
    $php_val = $php_values[$name] ?? 0;
    $diff = $php_val - $excel_val;
    
    if (abs($diff) < 0.01) {
        $correct++;
    } else {
        $incorrect++;
        echo str_pad($name, 25) . 
             str_pad(number_format($php_val, 2), 15) . 
             str_pad(number_format($excel_val, 2), 15) . 
             number_format($diff, 2) . "\n";
    }
}

echo str_repeat("=", 80) . "\n";
echo "SUMMARY: $correct correct, $incorrect incorrect out of " . count($excel_values) . " employees\n";
?>
