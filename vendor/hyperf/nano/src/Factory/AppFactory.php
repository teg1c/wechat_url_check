<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf Nano.
 *
 * @link     https://www.hyperf.io
 * @document https://nano.hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/nano/blob/master/LICENSE
 */
namespace Hyperf\Nano\Factory;

use Hyperf\Config\Config;
use Hyperf\Config\ProviderConfig;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ContainerInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\Container;
use Hyperf\Di\Definition\DefinitionSource;
use Hyperf\HttpServer\Exception\Handler\HttpExceptionHandler;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Hyperf\Nano\App;
use Hyperf\Nano\BoundInterface;
use Hyperf\Nano\ContainerProxy;
use Hyperf\Nano\Preset\Preset;
use Hyperf\Utils\ApplicationContext;
use Psr\Log\LogLevel;

class AppFactory
{
    /**
     * Create an application.
     */
    public static function create(string $host = '0.0.0.0', int $port = 9501, array $dependencies = []): App
    {
        $app = self::createApp($dependencies);
        $app->config([
            'server' => Preset::default(),
            'server.servers.0.host' => $host,
            'server.servers.0.port' => $port,
        ]);
        $app->addExceptionHandler(HttpExceptionHandler::class);
        return $app;
    }

    /**
     * Create a single worker application in base mode, with max_requests = 0.
     */
    public static function createBase(string $host = '0.0.0.0', int $port = 9501, array $dependencies = []): App
    {
        $app = self::createApp($dependencies);
        $app->config([
            'server' => Preset::base(),
            'server.servers.0.host' => $host,
            'server.servers.0.port' => $port,
        ]);
        $app->addExceptionHandler(HttpExceptionHandler::class);
        return $app;
    }

    /**
     * Create an application with a chosen preset.
     */
    public static function createApp(array $dependencies = []): App
    {
        // Setting ini and flags
        self::prepareFlags();

        // Prepare container
        $container = self::prepareContainer($dependencies);

        return new App($container);
    }

    protected static function prepareContainer(array $dependencies = []): ContainerInterface
    {
        $config = new Config(ProviderConfig::load());
        $config->set(StdoutLoggerInterface::class, [
            'log_level' => [
                LogLevel::ALERT,
                LogLevel::CRITICAL,
                LogLevel::DEBUG,
                LogLevel::EMERGENCY,
                LogLevel::ERROR,
                LogLevel::INFO,
                LogLevel::NOTICE,
                LogLevel::WARNING,
            ],
        ]);
        $dependencies = array_merge($config->get('dependencies', []), $dependencies);
        $container = new Container(new DefinitionSource($dependencies));
        $container->set(ConfigInterface::class, $config);
        $container->define(DispatcherFactory::class, DispatcherFactory::class);
        $container->define(BoundInterface::class, ContainerProxy::class);

        ApplicationContext::setContainer($container);
        return $container;
    }

    /**
     * Setup flags, ini settings and constants.
     */
    protected static function prepareFlags(int $hookFlags = SWOOLE_HOOK_ALL)
    {
        ini_set('display_errors', 'on');
        ini_set('display_startup_errors', 'on');
        error_reporting(E_ALL);
        $reflection = new \ReflectionClass(\Composer\Autoload\ClassLoader::class);
        $projectRootPath = dirname($reflection->getFileName(), 3);
        ! defined('BASE_PATH') && define('BASE_PATH', $projectRootPath);
        ! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', $hookFlags);
    }
}
