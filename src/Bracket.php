<?php

namespace FB;

class Bracket
{
    public $stat;
    public $rounds = [];
    public $roundNum = 0;
    public $winner = null;

    public function __construct(Stat $stat)
    {
        $this->stat = $stat;
    }

    public function processTournament($shuffleOnce = false)
    {
        while ($this->winner == null) {
            $this->roundNum++;

            if ($this->roundNum == 1) {
                $teams = array_keys($this->stat->teams);
                shuffle($teams);
            } else {
                $teams = $this->rounds[$this->roundNum - 1]['winners'];

                // Жеребьёвка в последующийх этапах после первого
                if (!$shuffleOnce) {
                    shuffle($teams);
                }
            }

            $this->processNextRound($teams);
        }
    }

    public function populateNextRound($teams)
    {
        foreach (array_chunk($teams, 2) as $teams) {
            $this->rounds[$this->roundNum]['matches'][] = new Match($teams[0], $teams[1]);
        }
    }

    public function processNextRound($teams)
    {
        $this->populateNextRound($teams);

        $statTeams = $this->stat->teams;
        $statOverall = $this->stat->overall;

        foreach ($this->rounds[$this->roundNum]['matches'] as $match) {
            $team1ExpectedGoals = $statTeams[$match->team1]['attackStr'] * $statTeams[$match->team2]['defenceStr'] * $statOverall['goalsScoredAvg'];
            $team2ExpectedGoals = $statTeams[$match->team2]['attackStr'] * $statTeams[$match->team1]['defenceStr'] * $statOverall['goalsScoredAvg'];

            // Распределение Пуассона в сумме может не давать 100%, погрешность округления вероятностей по каждому событию здесь не важна
            $poissonDistribution = [];

            for ($i = 1; $i <= 2; $i++) {
                for ($m = 0; $m <= 5; $m++) {
                    $poissonDistribution['team' . $i][$m] = $this->poissonProbability($m, ${'team' . $i . 'ExpectedGoals'});
                }
            }

            /*
             * На основании распределения Пуассона формируем массивы, значения которых - количество возможных забитых голов в этом матче
             * Количество определенных значений зависит от процентной вероятности исхода
             * К примеру, если команда №1 с вероятностью 20% забьет 1 гол в этом матче, то в массиве из 100 элементов 20 элементов будут иметь значение 1
             */
            $teamGoalsRandomized = [
                1 => 0,
                2 => 0,
            ];

            // цикл для исключения ничьих
            while ($teamGoalsRandomized[1] == $teamGoalsRandomized[2]) {
                for ($i = 1; $i <= 2; $i++) {
                    $goalsCountRandomizer = [];

                    foreach ($poissonDistribution['team' . $i] as $goalsCount => $percent) {
                        $goalsCountRandomizer = array_merge($goalsCountRandomizer, array_fill(1, $percent, $goalsCount));
                    }

                    shuffle($goalsCountRandomizer);

                    $teamGoalsRandomized[$i] = $goalsCountRandomizer[0];
                }
            }

            for ($i = 1; $i <= 2; $i++) {
                $match->{'team' . $i . 'Goals'} = $teamGoalsRandomized[$i];
            }

            $match->winner = $match->team1Goals > $match->team2Goals ? $match->team1 : $match->team2;

            $this->rounds[$this->roundNum]['winners'][] = $match->winner;
        }

        // Если в текущем раунде 1 победитель - значит этот раунд последний
        if (count($this->rounds[$this->roundNum]['winners']) == 1) {
            $this->winner = $this->rounds[$this->roundNum]['winners'][0];
        }
    }

    public function poissonProbability($k, $l)
    {
        $e = 2.71828;

        $res = pow($l, $k) * pow($e, -$l) / self::factorial($k);
        $res = round($res, 2) * 100;

        return $res;
    }

    public static function factorial($number)
    {
        if ($number < 2) {
            return 1;
        } else {
            return ($number * self::factorial($number - 1));
        }
    }
}
