<?php
// CSV helper for LifeLine Blood Bank Analytics Engine

function getAnalyticsData() {
    $csvFile = __DIR__ . '/../data/lifeline_blood_inventory.csv';
    
    // Initialize outputs
    $totalBags = 0;
    $usedCount = 0;
    $expiredCount = 0;
    $availableCount = 0;
    
    $availableStock = [
        'O+' => 0, 'A+' => 0, 'B+' => 0, 'AB+' => 0,
        'O-' => 0, 'A-' => 0, 'B-' => 0, 'AB-' => 0
    ];

    $bloodTypes = array_keys($availableStock);

    // Dynamic 24-month list leading up to current date (2026-06-23)
    // We will generate the 24 months relative to the current local time.
    // e.g. from 23 months ago up to today's month.
    $monthsList = [];
    $currentDate = new DateTime('2026-06-23'); // Fixed local time to match user prompt's date
    
    // Let's create an array of the last 24 months in 'Y-m' format, sorted ascending
    for ($i = 23; $i >= 0; $i--) {
        $d = clone $currentDate;
        $d->modify("-$i month");
        $monthsList[] = $d->format('Y-m');
    }

    // usageHistory[bloodType][month] = count
    $usageHistory = [];
    foreach ($bloodTypes as $bt) {
        $usageHistory[$bt] = array_fill_keys($monthsList, 0);
    }

    if (!file_exists($csvFile)) {
        return [
            'total_bags' => 0,
            'used_count' => 0,
            'expired_count' => 0,
            'available_count' => 0,
            'available_stock' => $availableStock,
            'monthly_usage' => $usageHistory,
            'forecast' => [],
            'trends' => [],
            'alerts' => []
        ];
    }

    if (($handle = fopen($csvFile, 'r')) !== false) {
        $headers = fgetcsv($handle); // skip header row
        
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 8) continue;
            
            $bagId           = $row[0];
            $donorName       = $row[1];
            $bloodGroup      = trim($row[2]);
            $volume          = (int)$row[3];
            $storeDate       = $row[4];
            $expiryDate      = $row[5];
            $storageLocation = $row[6];
            $status          = trim($row[7]);

            if (!in_array($bloodGroup, $bloodTypes)) {
                continue;
            }

            $totalBags++;

            if ($status === 'Used') {
                $usedCount++;
            } elseif ($status === 'Expired') {
                $expiredCount++;
            } elseif ($status === 'Available') {
                $availableCount++;
                $availableStock[$bloodGroup]++;
            }

            // Extract month of Store_Date for usage history if status is Used
            if ($status === 'Used' && !empty($storeDate)) {
                $storeMonth = substr($storeDate, 0, 7); // 'Y-m'
                if (isset($usageHistory[$bloodGroup][$storeMonth])) {
                    $usageHistory[$bloodGroup][$storeMonth]++;
                }
            }
        }
        fclose($handle);
    }

    // Compute Weighted Linear Regression Forecast & Trends for each blood type
    $forecast = [];
    $trends = [];
    $alerts = [];

    foreach ($bloodTypes as $bt) {
        // Prepare data points for regression
        // x values: 1 to 24 (months index)
        // y values: usage count for each month
        $yValues = array_values($usageHistory[$bt]);
        $n = 24;

        // Weights: last 6 months (indices 18 to 23, i.e., months 19-24) weighted 2x, others 1x
        $w = [];
        for ($i = 0; $i < $n; $i++) {
            $w[$i] = ($i >= 18) ? 2 : 1;
        }

        // Weighted sums
        $sumW   = 0;
        $sumWX  = 0;
        $sumWY  = 0;
        $sumWXX = 0;
        $sumWXY = 0;

        for ($i = 0; $i < $n; $i++) {
            $x = $i + 1; // 1-indexed
            $y = $yValues[$i];
            $weight = $w[$i];

            $sumW   += $weight;
            $sumWX  += $weight * $x;
            $sumWY  += $weight * $y;
            $sumWXX += $weight * $x * $x;
            $sumWXY += $weight * $x * $y;
        }

        // Calculate slope (m) and intercept (c)
        // Formula:
        // m = (sumW * sumWXY - sumWX * sumWY) / (sumW * sumWXX - sumWX * sumWX)
        $denominator = ($sumW * $sumWXX) - ($sumWX * $sumWX);
        if ($denominator != 0) {
            $m = (($sumW * $sumWXY) - ($sumWX * $sumWY)) / $denominator;
            $c = ($sumWY - ($m * $sumWX)) / $sumW;
        } else {
            $m = 0;
            $c = array_sum($yValues) / $n; // average if denominator is 0
        }

        // Forecast for next 3 months: months 25, 26, 27
        $f25 = max(0, round($m * 25 + $c, 1));
        $f26 = max(0, round($m * 26 + $c, 1));
        $f27 = max(0, round($m * 27 + $c, 1));

        $forecast[$bt] = [
            'month1' => $f25, // next month
            'month2' => $f26,
            'month3' => $f27,
            'total' => $f25 + $f26 + $f27
        ];

        // Trend comparison: average monthly usage in the last 6 months (indices 18-23)
        // vs the previous 6 months (indices 12-17)
        $last6Sum = array_sum(array_slice($yValues, 18, 6));
        $prev6Sum = array_slice($yValues, 12, 6);
        $prev6SumVal = array_sum($prev6Sum);

        $avgLast6 = $last6Sum / 6;
        $avgPrev6 = $prev6SumVal / 6;

        if ($avgPrev6 > 0) {
            $pctChange = (($avgLast6 - $avgPrev6) / $avgPrev6) * 100;
        } else {
            $pctChange = ($avgLast6 > 0) ? 100.0 : 0.0;
        }

        $trends[$bt] = [
            'avg_last_6' => round($avgLast6, 1),
            'avg_prev_6' => round($avgPrev6, 1),
            'pct_change' => round($pctChange, 1)
        ];

        // Critical Alerts: current available stock is below next month's predicted demand (f25)
        $currentStock = $availableStock[$bt];
        $nextMonthDemand = $f25;
        if ($currentStock < $nextMonthDemand) {
            $deficit = $nextMonthDemand - $currentStock;
            $severity = ($nextMonthDemand > 0) ? ($deficit / $nextMonthDemand) : 0;
            $alerts[] = [
                'blood_type' => $bt,
                'available' => $currentStock,
                'predicted_demand' => $nextMonthDemand,
                'deficit' => round($deficit, 1),
                'severity' => $severity
            ];
        }
    }

    // Sort alerts by severity descending
    usort($alerts, function($a, $b) {
        return $b['severity'] <=> $a['severity'];
    });

    return [
        'total_bags' => $totalBags,
        'used_count' => $usedCount,
        'expired_count' => $expiredCount,
        'available_count' => $availableCount,
        'available_stock' => $availableStock,
        'monthly_usage' => $usageHistory,
        'forecast' => $forecast,
        'trends' => $trends,
        'alerts' => $alerts
    ];
}

// Helper to append a new donation to the CSV file
function appendDonationToCSV($donorName, $bloodGroup, $volumeMl, $location, $storeDate) {
    $csvFile = __DIR__ . '/../data/lifeline_blood_inventory.csv';
    
    // Generate Bag ID: BAG-YYYYMM-XXXX
    $datePart = date('Ym', strtotime($storeDate));
    
    // Find next suffix sequence
    $suffix = 1;
    if (file_exists($csvFile)) {
        // Read file backwards or count lines to find suffix
        // Let's count existing rows to generate a unique suffix
        $lines = 0;
        $handle = fopen($csvFile, 'r');
        while (!feof($handle)) {
            $line = fgets($handle);
            if (!empty(trim($line))) {
                $lines++;
            }
        }
        fclose($handle);
        $suffix = $lines; // Safe sequential fallback
    }
    
    $bagId = 'BAG-' . $datePart . '-' . str_pad($suffix, 4, '0', STR_PAD_LEFT);
    $expiryDate = date('Y-m-d', strtotime($storeDate . ' + 35 days'));
    
    $row = [
        $bagId,
        $donorName,
        $bloodGroup,
        $volumeMl,
        $storeDate,
        $expiryDate,
        $location,
        'Available' // Newly added donations are marked Available
    ];
    
    $fp = fopen($csvFile, 'a');
    fputcsv($fp, $row);
    fclose($fp);
    
    return $bagId;
}
