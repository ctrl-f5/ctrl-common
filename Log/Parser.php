<?php
namespace Ctrl\Common\Log;

class Parser
{
    /**
     * @var string
     */
    protected $file;

    /**
     * @var \DateTime
     */
    protected $date;

    /**
     * @var string
     */
    protected $logger;

    /**
     * @var string
     */
    protected $level;

    /**
     * @var string
     */
    protected $linePattern = '/\[(?P<date>.*)\] (?P<logger>\w+).(?P<level>\w+): (?P<message>[^\[\{]+) (?P<context>[\[\{].*[\]\}]) (?P<extra>[\[\{].*[\]\}])/';

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param string $file
     * @return $this
     */
    public function setFile($file)
    {
        $this->file = $file;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param \DateTime $date
     * @return $this
     */
    public function setDate($date)
    {
        $this->date = $date;
        return $this;
    }

    /**
     * @return string
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param string $logger
     * @return $this
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @return string
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @param string $level
     * @return $this
     */
    public function setLevel($level)
    {
        $this->level = $level;
        return $this;
    }

    /**
     * @return string
     */
    public function getLinePattern()
    {
        return $this->linePattern;
    }

    /**
     * @param string $linePattern
     * @return $this
     */
    public function setLinePattern($linePattern)
    {
        $this->linePattern = $linePattern;
        return $this;
    }

    /**
     * @return string
     */
    public function getFilterRegex()
    {
        $filter['date'] = [
            'date'          => '',
            'logger.level'  => '',
            'logger'        => '',
            'level'         => '',
        ];
        if ($this->date) {
            $filter['date'] = '^\[' . $this->date->format('Y-m-d') . ' [0-9]{2}:[0-9]{2}:[0-9]{2}\]';
        }
        if ($this->logger &&  $this->level) {
            $filter['logger.level'] = " {$this->logger}.{$this->level}";
        } else {
            if ($this->logger) {
                $filter['logger'] = ' ' . $this->logger;
            }
            if ($this->level) {
                $filter['level'] = ' (\w+).' . $this->level;
            }
        }

        return implode('', $filter);
    }

    /**
     * @return array
     */
    public function getLines()
    {
        $command = sprintf(
            "grep -E '%s' %s",
            $this->getFilterRegex(),
            escapeshellarg($this->file)
        );
        $output = null;
        exec($command, $output);

        return $this->parseLines($output);
    }

    /**
     * @param array $lines
     * @return array
     */
    protected function parseLines(array $lines = array())
    {
        $parsed = [];
        foreach ($lines as $line) {
            $data = [];
            preg_match($this->linePattern, $line, $data);
            if (isset($data['date'])) {
                $parsed[] = [
                    'line'      => $line,
                    'date'      => new \DateTime($data['date']),
                    'logger'    => $data['logger'],
                    'level'     => $data['level'],
                    'message'   => $data['message'],
                    'context'   => json_decode($data['context'], true),
                    'extra'     => json_decode($data['extra'], true)
                ];
            } else {
                $matches = [];
                preg_match('/\[([^\]]*)\]\ (\w+)\.(\w+): (.*?)$/', $line, $matches);
                if ($matches) {
                    $parsed[] = [
                        'line'      => $line,
                        'date'      => new \DateTime($matches[1]),
                        'logger'    => $matches[2],
                        'level'     => $matches[3],
                        'message'   => $matches[4],
                    ];
                }
            }
        }

        return $parsed;
    }
}
