<?php

require '../bootstrap.php';

use FB\Bracket;
use FB\Stat;

$stat = new Stat('data' . DS . 'stat.csv');

$bracket = new Bracket($stat);

if (isset($_GET['processTournament'])) {
    $bracket->processTournament(isset($_GET['shuffleOnce']));
}

include 'index.phtml';
