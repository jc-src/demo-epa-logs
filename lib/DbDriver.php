<?php

/**
 * Class DbDriver
 * PDO based driver for MySQLi
 */
class DbDriver
{
    /** @var string */
    private $file;

    /** @var SQLite3 */
    private $handle;

    public function __construct(string $dbFileName)
    {
        $this->file = $dbFileName;
        $this->init();
    }

    private function init()
    {
        if (!file_exists($this->file)) {
            touch($this->file);
            $this->handle = new SQLite3($this->file);
            $this->createDbSchema();
        } else {
            $this->handle = new SQLite3($this->file);
        }
    }

    /**
     * @return array
     */
    public function fetchLogFiles()
    {
        $result = $this->handle->query('SELECT filename FROM epa_files');
        $rtn = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rtn[] = $row['filename'];
        }
        return $rtn;
    }

    /**
     * @param string $logFile
     * @return int
     * @throws Exception
     */
    public function insertLogfile(string $logFile)
    {
        $now = new DateTime();
        $sql = 'INSERT INTO epa_files(filename, created_at) VALUES(:file_name, :created_at)';
        $stmt = $this->handle->prepare($sql);
        $stmt->bindValue(':file_name', $logFile);
        $stmt->bindValue(':created_at', $now->getTimestamp());
        $stmt->execute();
        return $this->handle->lastInsertRowID();
    }

    /**
     * @param array $data
     */
    public function insertData(array $data)
    {
        $this->handle->exec('BEGIN TRANSACTION');
        foreach ($data as list($time, $host, $method, $status, $size)) {
            $sql = 'INSERT INTO epa_logs(hostname, method, status, size, created_at) 
                    VALUES(:host, :method, :status, :size, :time)';
            $stmt = $this->handle->prepare($sql);
            $stmt->bindValue(':host', substr($host, 0, 255));
            $stmt->bindValue(':method', substr($method, 0,6));
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':size', $size);
            $stmt->bindValue(':time', $time);
            $stmt->execute();
        }
        $this->handle->exec('COMMIT TRANSACTION');
    }

    /**
     * @return array
     */
    public function fetchRequestMethods()
    {
        $sql = 'SELECT method, count(id) as suma from epa_logs GROUP BY method';
        $result = $this->handle->query($sql);
        $rtn = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rtn[$row['method']] = $row['suma'];
        }
        ksort($rtn);
        return $rtn;
    }

    public function fetchRequestResponseCodes()
    {
        $sql = 'SELECT status, count(id) as suma from epa_logs GROUP BY status';
        $result = $this->handle->query($sql);
        $rtn = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rtn[$row['status']] = $row['suma'];
        }
        ksort($rtn);
        return $rtn;
    }

    /**
     * @param int $period
     * @return array
     */
    public function fetchRequestPerPeriod(int $period = 60)
    {
        $sql = sprintf(
            'SELECT method, (round(created_at/%d)*%d) as roundtime, count(id) as suma 
                    from epa_logs 
                    GROUP BY (round(created_at/%d)*%d)
                    ORDER BY created_at ASC',
            $period, $period, $period, $period
        );
        $result = $this->handle->query($sql);
        $rtn = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            if (!array_key_exists($row['method'], $rtn)) {
                $rtn[$row['method']] = [];
            }
            $rtn[$row['method']][$row['roundtime']] = $row['suma'];
        }
        return $rtn;
    }

    /**
     * @param int $period
     * @return array
     */
    public function fetchRequestAnswerSize(int $period = 600)
    {
        $sql = sprintf(
            'SELECT round(AVG(size), 2) as average, (round(created_at/%d)*%d) as roundtime, count(id) as suma 
                    from epa_logs 
                    GROUP BY (round(created_at/%d)*%d)
                    ORDER BY created_at ASC',
            $period, $period, $period, $period
        );
        $result = $this->handle->query($sql);
        $rtn = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rtn[$row['roundtime']] = [
                'count' => $row['suma'],
                'average' => $row['average']
            ];
        }
        return $rtn;
    }

    /**
     * A one time create tables and indexes
     */
    private function createDbSchema()
    {
        $commands = [
            'CREATE TABLE epa_files (                
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                filename    VARCHAR (255),
                created_at  INTEGER NOT NULL
            )',
            'CREATE TABLE epa_logs (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                hostname    VARCHAR (255),
                method      VARCHAR (6),
                status      SMALLINT,
                size        integer,
                created_at  INTEGER NOT NULL
            )',
            'CREATE INDEX idx_epa_file_filename   ON epa_files(filename);',
            'CREATE INDEX idx_epa_logs_hostname   ON epa_logs(hostname);',
            'CREATE INDEX idx_epa_logs_method     ON epa_logs(method);',
            'CREATE INDEX idx_epa_logs_status     ON epa_logs(status);',
            'CREATE INDEX idx_epa_logs_size       ON epa_logs(size);',
            'CREATE INDEX idx_epa_logs_created_at ON epa_logs(created_at);'
        ];

        foreach ($commands as $command) {
            $this->handle->exec($command);
        }
    }

    public function __destruct() {
        if ($this->handle) {
            $this->handle->close();
        }
    }
}