<?php
/**
 * Created by PhpStorm.
 * User: aritoadmin
 * Date: 28/05/2018
 * Time: 06:25
 */

namespace Bwts\Robo;

use Robo\Output;

class BwtsConfigFile extends \Robo\Tasks
{

  public function createFile() : String {
    return '';
  }

  public function getDataFromUser() {
    // Ask use?
    $name = $this->ask('What is your name?');
    $this->say('Hi \t' . get_current_user() . ' used by ' . $name . PHP_EOL);
  }
}