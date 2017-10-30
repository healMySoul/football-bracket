<?php

namespace FB;

class Match
{
    public $team1;
    public $team2;
    public $team1Goals = 0;
    public $team2Goals = 0;
    public $winner;

    public function __construct($team1, $team2)
    {
        $this->team1 = $team1;
        $this->team2 = $team2;
    }
}
