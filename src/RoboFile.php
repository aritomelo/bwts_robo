<?php

declare(strict_types=1);

/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */

use Bwts\Robo\BwtsConfigFile;

class RoboFile extends \Robo\Tasks
{
  const BWTS_DATA = 'bwtsData.txt';
  /**
   * @description saying Hi.
   *
   * @return string
   */
    public function BwtsHi() {
      $name = $this->ask('What is your name [Melito/Arito/Lima/Melo]?');
      switch ( $name ) {
        case 'Melito':
        case 'Arito':
        case 'Melo':
        case 'Lima':
          $this->say('Hi ' . get_current_user() . ' by ' . $name);
          $this->BwtsSuccessMsg('A valid choice!');
          break;

        default:
          $this->BwtsErrorMsg('Not a valid choice!');
          break;
      }
    }

    private function BwtsErrorMsg( $errorMsg ) {
      $this->say("<error>$errorMsg</error>");
    }

    private function BwtsSuccessMsg( $successMsg ) {
      $this->say("<info>$successMsg</info>");
    }
  /**
   * @description Write config file.
   */
    function BwtsConfigFile() {
      $file = self::BWTS_DATA;
      $this->taskExec("rm -rf " . $file);
      $this->taskWriteToFile($file)->text('text write and create file')
        ->line('Melito')
        ->run();
      $this->say('<info> Finished Success!</info>');
      $this->say('<error> Finished Failed!</error>');
    }

  /**
   * This method will run and advice that the file has been changed!
   */
    public function bwtsWatchFiles() {
      $this->taskWatch()
        ->monitor(self::BWTS_DATA, function() {
          $this->say('File modified!');
        })->run();
    }

    /**
     * @description Run composer update when compose file has been update.
     */
    public function bwtsWatchComposer() {
      $this->taskComposerRequire('henrikbjorn/lurker');
      $this->taskWatch()
        ->monitor('../composer.json', function() {
          $this->taskComposerUpdate()->run();
        })->run();
    }

}