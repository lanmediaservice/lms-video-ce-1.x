<?php

class Lms_Pid {

    private $file;
    private $running = false;
    private $pids = array();

    function __construct($file, $maxWorkers = 1) 
    {
       
        $this->file = $file;
        if (is_writable(dirname($this->file))) {
            if (file_exists($this->file)) {
                $lines = file($this->file);
                $workers = 0;
                foreach ($lines as $line) {
                    $pid = (int) trim($line);
                    if (posix_kill($pid, 0)) {
                        $workers++;
                        $this->pids[] = $pid;
                    }
                }
                if ($workers>=$maxWorkers) {
                    $this->running = true;
                }
            }
        } else {
            die("Cannot write to pid file '$this->file'. Program execution halted.\n");
        }
       
        if (!$this->running) {
            $this->pids[] = getmypid();
            file_put_contents($this->file, implode("\n", $this->pids));
        }
    }

    public function __destruct() 
    {
        if (!$this->running && file_exists($this->file) && is_writeable($this->file)) {
            $lines = file($this->file);
            $newLines = array();
            foreach ($lines as $line) {
                $pid = (int) trim($line);
                if ($pid!=getmypid() && posix_kill($pid, 0)) {
                    $newLines[] = $line;
                }
            }
            if (count($newLines)) {
                file_put_contents($this->file, implode("", $newLines));
            } else {
                unlink($this->file);
            }
        }
    }

    public function isRunning()
    {
        return $this->running;
    }

    public static function getPid($file, $maxWorkers = 1)
    {
        return new self($file, $maxWorkers);
    }
}