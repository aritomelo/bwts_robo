<?php
/**
 * Created by PhpStorm.
 * User: aritoadmin
 * Date: 29/05/2018
 * Time: 00:22
 */
declare(strict_types=1);

namespace BwtsTaskRunner\Robo\Plugin\Commands;


class BwtsCommands extends \Robo\Tasks
{
  /**
   * The environment(env) base directory.
   *
   * The env can be where is the projects directory, for example:
   *  - Sites directory for local environment.
   *
   * @var string
   */
  private $envRoot = NULL;

  private $baseDir = NULL;

  /**
   * Runner type can be phing/robo, so the commands to build and install are diff.
   *
   * @var string
   */
  private $runnerType;

  /**
   * @return string
   */
  public function getRunnerType(): string
  {
    return $this->runnerType;
  }

  /**
   *
   */
  public function setRunnerType(): void
  {
    // temporarlly solution
    // check with type RUNNER it's needed.
    $projectDir = explode('/', $this->getBaseDir());
    $projectDir = end($projectDir);
    $roboType = in_array('oe', preg_split( "/ (_|-) /", $projectDir));
    // $roboType = in_array('oe', explode('_', $projectFolder));

    if ($roboType)
    {
      $this->runnerType = 'robo';
    }
    else {
      $this->runnerType ='phing';
    }
  }

  /**
   * @param null $baseDir
   */
  public function setBaseDir($baseDir): void
  {
    $this->baseDir = $baseDir;
  }

  /**
   * @return null
   */
  public function getBaseDir()
  {
    return $this->baseDir;
  }

  /**
   * @param string $envRoot
   */
  public function setEnvRoot(string $envRoot): void
  {
    $this->envRoot = $envRoot;
  }

  /**
   * @return string
   */
  public function getEnvRoot()
  {
    return $this->envRoot;
  }

  /**
   * Check whether the vendor directory exists or not.
   */
  public function vendorExist()
  {
    $baseDir = $this->getBaseDir();
    $vendorDir =$baseDir . '/vendor';

    return file_exists($vendorDir);
  }

  private function prepare()
  {
    $env = $this->ask('1- Local PC | 2- cloud9 | 3- From Project root');

    $envDirAvailable = [
      '~/Sites',
      '~/environment/bwtsDropbox/Projects/open_europa'
    ];

    if ($env == 1)
    {
      $this->setEnvRoot($envDirAvailable[0]);
    }
    else if ($env == 2)
    {
      $this->setEnvRoot($envDirAvailable[1]);
    }
    else if (3 == $env)
    {
      $this->setBaseDir(getcwd());
      $this->say($this->getBaseDir());
      $this->setRunnerType();
      return;
    }
    else
    {
      $this->BwtsErrorMsg('Not a registered environment!');
      return;
    }

    $rootDir = $this->getEnvRoot();
    $choose = $this->ask('1- Project root | 2- Project name');
    $projectsAvailable = [
      "oe_theme"      => "$rootDir/open_europa/oe_theme",
      "oe_webtools"   => "$rootDir/open_europa/oe_webtools",
      "platform-dev"  => "$rootDir/platform-dev",
      "oe_paragraphs" => "$rootDir/open_europa/oe_paragraphs",
    ];

    switch ($choose) {
      case 1:
        $this->setBaseDir($this->ask("Enter the project root?"));
        break;

      case 2:
        $this->say('==> Project Available');
        $this->displayMultiline($projectsAvailable);
        $projName = $this->ask("1- Enter the project name?");
        if ($choose == 2 && array_key_exists($projName, $projectsAvailable))
        {
          $this->setBaseDir($projectsAvailable[$projName]);
        }
        else {
          $this->BwtsErrorMsg('Project name invalid!');
        }
        break;

      default:
        break;
    }

   // set runner type: phing| robo
    $this->setRunnerType();
  }

  /**
   * Clear files and directory before new installation.
   *
   * @command bwts:clear-project
   */
  public function commandClearProject()
  {
    $projRoot = $this->getBaseDir();
    $this->taskExec("sudo rm -rf $projRoot/build")->run();
    $this->taskParallelExec()
      ->process("sudo rm -f  $projRoot/composer.lock")
      ->process("sudo rm -rf $projRoot/vendor")
      ->run();
  }

  /**
   * @param string[] $values
   */
  private function displayMultiline(array $values): void
  {
    $i = 0;
    foreach($values as $label => $value)
    {
      $this->say(  ++$i . '- ' . $label);
    }
  }
  /**
   * @command bwts:composerInstall
   */
  public function composerInstall(): void
  {
    if (! $this->taskExec('composer install')->run()->wasSuccessful())
    {
    $this->bwtsErrorMsg('Composer failed!');
      return;
    }

    //    if(null == $this->taskComposerInstall($this->getBaseDir() . '/composer.json')->run()) {
    //      $this->bwtsErrorMsg('composer install failed!');
    //      return;
    //    }
  }

  /**
   * @command bwts:createProject
   */
  public function createProject(): void
  {
    // Prepare the environment by setting the base directory.
    $this->prepare();

    $this->say("Clean the project!");
    $this->commandClearProject();

    $this->say("Composer install!");
    $this->composerInstall();

    if (!$this->vendorExist())
    {
      $this->bwtsErrorMsg('Vendor file missing!');
      return;
    }


    // install
    if ( 'robo' == $this->getRunnerType())
    {
      $this->buildOpenEuropaClone();
    }
    else {
      $this->buildPlatform();
    }

    $this->open_window('http://www.google.com');
  }

  /**
   * @param $url
   */
  function open_window($url) {
    echo '<script>window.open ("'.$url.'", "mywindow","status=0,toolbar=0")</script>;';
  }

  /**
   *
   */
  public function buildPlatform() {
    $projRoot = $this->getBaseDir();
    $this->say("Set up site!");
    $this->taskExec("$projRoot/bin/phing build-platform-dev")->run();

    $this->say("Install the site!");
    $this->taskExec("$projRoot/bin/phing install-platform")->run();
  }

  private function  buildOpenEuropaClone(): void {
    $projRoot = $this->getBaseDir();
    $this->say("Set up site!");
    $this->taskExec("$projRoot/vendor/bin/run drupal:site-setup")->run();

    $this->say("Install the site!");
    $this->taskExec("$projRoot/vendor/bin/run drupal:site-install")->run();
  }

  /**
   * @param string $errorMsg
   */
  private function bwtsErrorMsg($errorMsg)
  {
    $this->say("<error>$errorMsg</error>");
  }

  /**
   * @param $successMsg
   */
  private function bwtsSuccessMsg($successMsg)
  {
    $this->say("<info>$successMsg</info>");
  }
}