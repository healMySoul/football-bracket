<?php

namespace FB;

class Stat
{
    public $teams = [];
    public $overall = [
        'gamesTotal' => 0,
        'goalsScored' => 0,
        'goalsMissed' => 0,
    ];

    public function __construct($filepath)
    {
        $statPath = ROOT . $filepath;

        if (!is_file($statPath)) {
            throw new \Exception('Stat file is not found');
        }

        $statRaw = file_get_contents($statPath);
        $statRawEncoding = mb_detect_encoding($statRaw, ['Windows-1251']);

        if ($statRawEncoding == 'Windows-1251') {
            $statRaw = mb_convert_encoding($statRaw, 'UTF-8', $statRawEncoding);
        }

        $statRaw = explode("\r", $statRaw);
        $statRaw = array_filter($statRaw);

        if (empty($statRaw)) {
            throw new \Exception('No data is found in stat file');
        }

        unset($statRaw[0]);

        $this->generateStatFromFileData($statRaw);

        ksort($this->teams);
    }

    public function generateStatFromFileData($data)
    {
        // Формирование статистики по каждой команде
        foreach ($data as $statRow) {
            $teamStatArray = explode(';', $statRow);
            $goals = explode(' - ', $teamStatArray[5]);

            $teamStat = [
                'teamName' => $teamStatArray[0],
                'gamesTotal' => (int) $teamStatArray[1],
                'win' => (int) $teamStatArray[2],
                'draw' => (int) $teamStatArray[3],
                'lose' => (int) $teamStatArray[4],
                'goalsScored' => (int) $goals[0],
                'goalsMissed' => (int) $goals[1],
            ];

            $this->teams[$teamStat['teamName']] = $teamStat;
        }

        // Формирование общей статистики
        foreach ($this->teams as $team) {
            $this->overall['gamesTotal'] += $team['gamesTotal'];
            $this->overall['goalsScored'] += $team['goalsScored'];
            $this->overall['goalsMissed'] += $team['goalsMissed'];
        }

        $this->overall['goalsScoredAvg'] = round($this->overall['goalsScored'] / $this->overall['gamesTotal'], 3);
        $this->overall['goalsMissedAvg'] = round($this->overall['goalsMissed'] / $this->overall['gamesTotal'], 3);

        // Рассчет силы атаки/защиты каждой команды
        foreach ($this->teams as $k => $teamStat) {
            $this->teams[$k]['attackStr'] = round(($teamStat['goalsScored'] / $teamStat['gamesTotal']) / $this->overall['goalsScoredAvg'], 3);
            $this->teams[$k]['defenceStr'] = round(($teamStat['goalsMissed'] / $teamStat['gamesTotal']) / $this->overall['goalsMissedAvg'], 3);
        }
    }
}
