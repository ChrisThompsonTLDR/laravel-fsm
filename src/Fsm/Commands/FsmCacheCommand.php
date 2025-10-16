<?php

declare(strict_types=1);

namespace Fsm\Commands;

use Fsm\FsmRegistry;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class FsmCacheCommand extends Command
{
    protected $signature = 'fsm:cache {--clear : Clear cached FSM definitions} {--rebuild : Rebuild the cache}';

    protected $description = 'Manage cached FSM definitions';

    public function handle(FsmRegistry $registry, ConfigRepository $config): int
    {
        $path = $config->get('fsm.cache.path', storage_path('framework/cache/fsm.php'));

        if ($this->option('clear')) {
            if (is_file($path)) {
                @unlink($path);
                $this->info('FSM cache cleared.');
            } else {
                $this->info('No FSM cache file found.');
            }

            return self::SUCCESS;
        }

        if ($this->option('rebuild')) {
            $registry->clearCache();
        }

        $registry->discoverDefinitions();
        $this->info('FSM definitions cached successfully.');

        return self::SUCCESS;
    }
}
