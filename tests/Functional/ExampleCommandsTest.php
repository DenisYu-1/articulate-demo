<?php

namespace App\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Dotenv\Dotenv;

class ExampleCommandsTest extends TestCase
{
    private static bool $envLoaded = false;

    protected function setUp(): void
    {
        if (!self::$envLoaded) {
            (new Dotenv())->bootEnv(dirname(__DIR__, 2) . '/.env');
            self::$envLoaded = true;
        }
    }

    private function runCommand(string $commandName): array
    {
        $kernel = new \App\Kernel($_ENV['APP_ENV'] ?? 'test', (bool) ($_ENV['APP_DEBUG'] ?? false));
        $kernel->boot();
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput(['command' => $commandName]);
        $output = new BufferedOutput(BufferedOutput::VERBOSITY_VERBOSE);
        $exitCode = $application->run($input, $output);

        return [$exitCode, $output->fetch()];
    }

    public function testBasicCrudExampleRunsSuccessfully(): void
    {
        [$exitCode] = $this->runCommand('app:example:basic-crud');
        $this->assertSame(0, $exitCode);
    }

    public function testAdvancedQueryingExampleRunsSuccessfully(): void
    {
        [$exitCode] = $this->runCommand('app:example:advanced-querying');
        $this->assertSame(0, $exitCode);
    }

    public function testTransactionsLockingExampleRunsSuccessfully(): void
    {
        [$exitCode, $content] = $this->runCommand('app:example:transactions-locking');
        $this->assertSame(0, $exitCode, $content ?: 'Command produced no output');
    }

    public function testLifecycleCallbacksExampleRunsSuccessfully(): void
    {
        [$exitCode] = $this->runCommand('app:example:lifecycle-callbacks');
        $this->assertSame(0, $exitCode);
    }

    public function testCustomTypesExampleRunsSuccessfully(): void
    {
        [$exitCode] = $this->runCommand('app:example:custom-types');
        $this->assertSame(0, $exitCode);
    }

    public function testMultipleUnitOfWorkExampleRunsSuccessfully(): void
    {
        [$exitCode, $content] = $this->runCommand('app:example:multiple-unit-of-work');
        $this->assertSame(0, $exitCode, $content ?: 'Command produced no output');
    }

    public function testRelationsExampleRunsSuccessfully(): void
    {
        [$exitCode, $content] = $this->runCommand('app:example:relations');
        $this->assertSame(0, $exitCode, $content ?: 'Command produced no output');
    }

    public function testPaginationSortingSoftDeleteExampleRunsSuccessfully(): void
    {
        [$exitCode, $content] = $this->runCommand('app:example:pagination-sorting-soft-delete');
        $this->assertSame(0, $exitCode, $content ?: 'Command produced no output');
    }
}
