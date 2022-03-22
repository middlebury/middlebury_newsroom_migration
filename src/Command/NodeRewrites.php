<?php

namespace Drupal\middlebury_newsroom_migration\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
// @codingStandardsIgnoreLine
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Core\Database\Connection;
use GuzzleHttp\ClientInterface;
use Drupal\path_alias\AliasManagerInterface;

/**
 * Provide a command to generate a rewrite rule for migrated node.
 *
 * @package Drupal\middlebury_newsroom_migration
 *
 * @DrupalCommand(
 *   extension = "middlebury_newsroom_migration"
 *   extensionType = "module"
 * )
 */
class NodeRewrites extends Command {

  use CommandTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * The client interface.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The alias manager interface.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    Connection $db,
    ClientInterface $http_client,
    AliasManagerInterface $alias_manager
  ) {
    $this->db = $db;
    $this->httpClient = $http_client;
    $this->aliasManager = $alias_manager;

    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('middlebury_newsroom_migration:node_rewrites')
      ->setDescription($this->trans('commands.middlebury_newsroom_migration.node_rewrites.description'))
      ->addArgument(
        'dest_base',
        InputArgument::REQUIRED,
        $this->trans('commands.middlebury_newsroom_migration.node_rewrites.arguments.dest_base'),
        NULL
      )
      ->addArgument(
        'sleep',
        InputArgument::REQUIRED,
        $this->trans('commands.middlebury_newsroom_migration.node_rewrites.arguments.sleep'),
        NULL
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output->getErrorOutput());

    $dest_base = $input->getArgument('dest_base');
    if (empty($dest_base)) {
      $dest_base = $io->ask(
        $this->trans('commands.middlebury_newsroom_migration.node_rewrites.arguments.dest_base'),
        'https://www.middlebury.edu/announcements',
        function ($url) {
          if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
          }
          else {
            throw new \Exception($this->trans('commands.middlebury_newsrooom_migration.node_rewrites.errors.invalid_dest_base'));
          }
        }
      );
    }
    $input->setArgument('dest_base', $dest_base);

    $sleep = $input->getArgument('sleep');
    if (empty($sleep)) {
      $sleep = $io->ask(
        $this->trans('commands.middlebury_newsroom_migration.node_rewrites.arguements.sleep'),
        1,
        function ($sleep) {
          if (filter_var($sleep, FILTER_VALIDATE_FLOAT)) {
            return $sleep;
          }
          else {
            throw new \Exception($this->trans('commands.middlebury_newsroom_migration.node_rewrites.errors.invalid_sleep'));
          }
        }
      );
    }
    $input->setArgument('sleep', $sleep);

  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $errOutput = $output->getErrorOutput();
    $io = new DrupalStyle($input, $errOutput);

    // Validate our migration tables exist.
    $result = $this->db->query('SELECT COUNT(*) FROM {migrate_map_newsroom_d7_node_story}');
    $num = $result->fetchColumn();
    if (!$num) {
      throw new \RuntimeException($this->trans('commands.middlebury_newsroom_migration.node_rewrites.errors.no_migrated_stories'));
    }

    $d8_base_url = $input->getArgument('dest_base');
    $sleep = floatval($input->getArgument('sleep'));

    // Go through each of our migrated nodes and look up the
    // original URL in the old site.
    $io->progressStart($num);
    $rows = $this->db
      ->query('SELECT sourceid1, destid1 FROM migrate_map_newsroom_d7_node_story')
      ->fetchAll(\PDO::FETCH_OBJ);
    foreach ($rows as $row) {
      $source_node_path = '/node/' . $row->sourceid1;
      $source_path = $source_node_path;
      $source_url = 'https://www.middlebury.edu' . $source_node_path;
      $response = $this->httpClient->request(
        'GET',
        $source_url,
        [
          'allow_redirects' => FALSE,
          'http_errors' => FALSE,
        ]
      );
      if ($response->getStatusCode() == '302' || $response->getStatusCode() == '301') {
        $location = $response->getHeader('Location');
        $source_path = str_replace('https://www.middlebury.edu', '', $location[0]);
      }
      else {
        $io->warning(sprintf($this->trans('commands.middlebury_newsroom_migration.node_rewrites.errors.no_redirect'), $response->getStatusCode(), $source_url));
      }
      $dest_path = $this->aliasManager->getAliasByPath('/node/', $row->destid1);
      $output->writeln(sprintf('RewriteRule %s %s%s [NC,L,R=301]', $source_path, $d8_base_url, $dest_path));
      $io->progressAdvance();
      sleep($sleep);
    }
    $io->progresFinish();
    $io->successLite($this->trans('commands.middlebury_newsroom_migration.node_rewrites.messages.success'), TRUE);
  }

}
