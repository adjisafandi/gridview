<?php

namespace DnaWeb\GridView;

class Utils{

    public static function unitList(){

        return ['B', 'KB', 'MB', 'GB', 'TB', 'EB', 'ZB', 'YB'];
    }

    public static function getSpace($bytes){

        $base = 1024;
        $class = min((int)log($bytes, $base), count(self::unitList()) - 1);

        return sprintf('%1.2f', $bytes / pow($base, $class)) . ' ' . self::unitList()[$class];
    }

    public static function getServerMemoryUsage($getPercentage = true){

        $memoryTotal = null;
        $memoryFree = null;

        if (stristr(PHP_OS, "win")) {
            // Get total physical memory (this is in bytes)
            $cmd = "wmic ComputerSystem get TotalPhysicalMemory";
            @exec($cmd, $outputTotalPhysicalMemory);

            // Get free physical memory (this is in kibibytes!)
            $cmd = "wmic OS get FreePhysicalMemory";
            @exec($cmd, $outputFreePhysicalMemory);

            if ($outputTotalPhysicalMemory && $outputFreePhysicalMemory) {
                // Find total value
                foreach ($outputTotalPhysicalMemory as $line) {
                    if ($line && preg_match("/^[0-9]+\$/", $line)) {
                        $memoryTotal = $line;
                        break;
                    }
                }

                // Find free value
                foreach ($outputFreePhysicalMemory as $line) {
                    if ($line && preg_match("/^[0-9]+\$/", $line)) {
                        $memoryFree = $line;
                        $memoryFree *= 1024;  // convert from kibibytes to bytes
                        break;
                    }
                }
            }
        } else {
            if (is_readable("/proc/meminfo")) {
                $stats = @file_get_contents("/proc/meminfo");

                if ($stats !== false) {
                    // Separate lines
                    $stats = str_replace(array("\r\n", "\n\r", "\r"), "\n", $stats);
                    $stats = explode("\n", $stats);

                    // Separate values and find correct lines for total and free mem
                    foreach ($stats as $statLine) {
                        $statLineData = explode(":", trim($statLine));

                        //
                        // Extract size (TODO: It seems that (at least) the two values for total and free memory have the unit "kB" always. Is this correct?
                        //

                        // Total memory
                        if (count($statLineData) == 2 && trim($statLineData[0]) == "MemTotal") {
                            $memoryTotal = trim($statLineData[1]);
                            $memoryTotal = explode(" ", $memoryTotal);
                            $memoryTotal = $memoryTotal[0];
                            $memoryTotal *= 1024;  // convert from kibibytes to bytes
                        }

                        // Free memory
                        if (count($statLineData) == 2 && trim($statLineData[0]) == "MemFree") {
                            $memoryFree = trim($statLineData[1]);
                            $memoryFree = explode(" ", $memoryFree);
                            $memoryFree = $memoryFree[0];
                            $memoryFree *= 1024;  // convert from kibibytes to bytes
                        }
                    }
                }
            }
        }

        if (is_null($memoryTotal) || is_null($memoryFree)) {
            return null;
        } else {
            if ($getPercentage) {
                return (100 - ($memoryFree * 100 / $memoryTotal));
            } else {
                return array(
                    "total" => $memoryTotal,
                    "free" => $memoryFree,
                );
            }
        }
    }

    public static function getNiceFileSize($bytes){

        $unit = self::unitList();
        if ($bytes == 0) return '0 ' . $unit[0];

        return @round($bytes / pow(1024, ($i = floor(log($bytes, 1024)))), 2) . ' ' . (isset($unit[$i]) ? $unit[$i] : 'B');
    }

    public static function getMemoryUsage(){

        $memUsage = self::getServerMemoryUsage(false);

        return [
            'memory_total' => self::getNiceFileSize($memUsage["total"]),
            'memory_usage' => self::getNiceFileSize($memUsage["total"] - $memUsage["free"]),
            'memory_free' => self::getNiceFileSize($memUsage["free"]),
            'total_percen' => intval(self::getServerMemoryUsage(true))
        ];
    }

    public static function getDiskUsage(){

        $total_disk = self::getSpace(disk_total_space('.'));
        $free_disk = self::getSpace(disk_free_space("."));
        $usage = floatval(explode(' ', $free_disk)[0]);
        $total = floatval(explode(' ', $total_disk)[0]);
        $disk_usage = self::getSpace(disk_total_space('.') - disk_free_space("."));
        $usage = $total - $usage;

        return [
            'disk_total' => $total_disk,
            'disk_usage' => $disk_usage,
            'disk_free' => $free_disk,
            'disk_percen' => intval(($usage/$total) * 100)
        ];
    }

    public static function prefixOperator(){

        return [
            [
                'operator_name' => '=',
                'condition_func' => 'where',
                'condition_sql' => '',
                'params_val' => ''
            ],
            [
                'operator_name' => '<',
                'condition_func' => 'where',
                'condition_sql' => '',
                'params_val' => ''
            ],
            [
                'operator_name' => '>',
                'condition_func' => 'where',
                'condition_sql' => '',
                'params_val' => ''
            ],
            [
                'operator_name' => '<=',
                'condition_func' => 'where',
                'condition_sql' => '',
                'params_val' => ''
            ],
            [
                'operator_name' => '=>',
                'condition_func' => 'where',
                'condition_sql' => '',
                'params_val' => ''
            ],
            [
                'operator_name' => '<>',
                'condition_func' => 'where',
                'condition_sql' => '',
                'params_val' => ''
            ],
            [
                'operator_name' => '!=',
                'condition_func' => 'where',
                'condition_sql' => '',
                'params_val' => ''
            ],
            [
                'operator_name' => 'LIKE',
                'condition_func' => 'where',
                'condition_sql' => '%@params%',
                'params_val' => ''
            ],
            [
                'operator_name' => 'ILIKE',
                'condition_func' => 'where',
                'condition_sql' => '%@params%',
                'params_val' => ''
            ],
            [
                'operator_name' => 'NOT LIKE',
                'condition_func' => 'where',
                'condition_sql' => '%@params%',
                'params_val' => ''
            ],
            [
                'operator_name' => 'NOT ILIKE',
                'condition_func' => 'where',
                'condition_sql' => '%@params%',
                'params_val' => ''
            ],
            [
                'operator_name' => 'IN',
                'condition_func' => 'whereIn',
                'condition_sql' => '',
                'params_val' => ''
            ],
            [
                'operator_name' => 'NOT IN',
                'condition_func' => 'whereNotIn',
                'condition_sql' => '',
                'params_val' => ''
            ]
        ];
    }
}
