<?php
$_POST['start_date'] = '2025-01-03';
$_POST['end_date'] = '2025-01-30';
$_SERVER['REQUEST_METHOD'] = 'POST';

ob_start();
include 'salary_summary.php';
$output = ob_get_clean();

preg_match_all('/<td class="employee-name">(.*?)<\/td>.*?<td class="income-amount">₱ ([\d,\.]+)<\/td>/s', $output, $matches);

echo "PHP CALCULATED NET INCOMES:\n";
echo str_repeat("=", 60) . "\n";
for($i=0; $i<count($matches[1]); $i++) {
    echo str_pad($matches[1][$i], 25) . " : ₱ " . str_pad($matches[2][$i], 15, " ", STR_PAD_LEFT) . "\n";
}

// Excel values for comparison
$excel = [
    'Kent, Clark' => '10,625.00',
    'Wayne, Bruce' => '12,126.29',
    'Prince, Diana' => '12,307.89',
    'Allen, Barry' => '13,539.89',
    'Jordan, Hal' => '11,354.89',
    'Curry, Arthur' => '13,514.16',
    'Jones, John' => '12,253.53',
    'Queen, Oliver' => '13,717.89',
    'Palmer, Ray' => '11,029.89',
    'Hall, Carter' => '11,993.89',
    'Mason, Rex' => '13,623.00',
    'Laurel, Dinah' => '13,433.00',
    'Stewart, John' => '14,317.00',
    'Hall, Shiera' => '10,039.00',
    'Raymond, Ronnie' => '14,039.00',
    'Rogers, Steve' => '11,555.00',
    'Barton, Clint' => '14,701.00',
    'Maximoff, Wanda' => '13,509.00',
    'Romanoff, Natasha' => '12,497.00',
    'McCoy, Henry' => '14,885.00',
    'Richards, Reed' => '11,062.50',
    'Richards, Sue' => '9,826.50',
    'Grimm, Ben' => '13,283.13',
    'Hammond, Jim' => '11,141.00',
    'Stark, Toni' => '13,389.38',
    'Rhodes, James' => '8,785.00',
    'Parker, Peter' => '8,893.35',
    'Lang, Scott' => '11,165.00',
    'Barnes, James' => '11,106.00',
    'Murdock, Matthew' => '11,416.00'
];

echo "\n\nEXCEL EXPECTED NET INCOMES:\n";
echo str_repeat("=", 60) . "\n";
foreach($excel as $name => $value) {
    echo str_pad($name, 25) . " : ₱ " . str_pad($value, 15, " ", STR_PAD_LEFT) . "\n";
}
?>
