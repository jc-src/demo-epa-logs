<?php

class Parser
{
    const REQUEST_METHODS = ['HEAD', 'GET', 'PUT', 'DELETE', 'POST', 'OPTIONS'];

    private $datePrefix = '';

    /**
     * @param string $datePrefix
     * @param string $fileName
     * @return array
     * @throws ExceptionHandler
     */
    public function parse(string $datePrefix, string $fileName): array
    {
        if (!file_exists($fileName)) {
            throw new ExceptionHandler('Log file not found', Response::HTTP_NOT_FOUND);
        }
        $this->datePrefix = $datePrefix;
        return $this->readFileData($fileName);
    }

    /**
     * @param string $fileName
     * @return array
     * @throws ExceptionHandler
     */
    private function readFileData(string $fileName): array
    {
        $rtn = [];
        $line = 0;
        if (($handle = fopen($fileName, 'r')) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ' ', '"')) !== FALSE) {
                $line++;
                // There should always be at least 5 cols
                if (count($data) < 5) {
                    continue;
                }
                list($host, $time, $request, $status, $size) = $data;

                // Time
                $timeString = $this->datePrefix . '-' . preg_replace("/[^0-9:]/", '', $time);
                try {
                    $dateString = (DateTime::createFromFormat('Y-m-d:H:i:s', $timeString))->getTimestamp();
                } catch (Exception $e) {
                    throw new ExceptionHandler(
                        sprintf('Could not parse date on for time: %s, line: %s', $time, $line),
                        Response::HTTP_BAD_PARAMS
                    );
                }
                // RequestMethod
                $method = trim(strtok($request, " "));
                if (!in_array($method, self::REQUEST_METHODS)) {
                    $method = ($status === '400') ? 'GET' : 'UNKNOWN';
                }

                $rtn[] = [$dateString, trim($host), $method, (int) $status, (int) $size];
            }
            fclose($handle);
        }
        return $rtn;
    }


}