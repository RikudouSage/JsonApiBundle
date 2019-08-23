<?php

namespace Rikudou\JsonApiBundle\Listener;

use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class CacheClearHookListener implements EventSubscriberInterface
{
    /**
     * @var bool
     */
    private $enabled;

    /**
     * @var AdapterInterface
     */
    private $cache;

    public function __construct(bool $enabled, AdapterInterface $cache)
    {
        $this->enabled = $enabled;
        $this->cache = $cache;
    }

    public static function getSubscribedEvents()
    {
        return [
            ConsoleEvents::COMMAND => 'handleCacheClearCommand',
        ];
    }

    public function handleCacheClearCommand(ConsoleCommandEvent $event)
    {
        if (!$this->enabled) {
            return;
        }

        if ($event->getCommand() !== null && $event->getCommand()->getName() === 'cache:clear') {
            $output = $event->getOutput();

            $output->writeln('<info>Clearing the api property cache</info>');
            $output->writeln(
                '(you can disable the <options=bold>cache:clear</> hook by setting <options=bold>clear_cache_hook</> to false)'
            );

            $this->cache->clear();
        }
    }
}
