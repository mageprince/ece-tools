<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Stage\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Environment\Reader as EnvironmentReader;
use Magento\MagentoCloud\Config\Schema;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\SystemConfigInterface;

/**
 * Returns all merged configuration for deploy phase
 */
class MergedConfig
{
    /**
     * @var EnvironmentReader
     */
    private $environmentReader;

    /**
     * @var EnvironmentConfig
     */
    private $environmentConfig;

    /**
     * @var array
     */
    private $mergedConfig;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var Schema
     */
    private $schema;

    /**
     * @param Environment $environment
     * @param EnvironmentReader $environmentReader
     * @param EnvironmentConfig $environmentConfig
     * @param Schema $schema
     */
    public function __construct(
        Environment $environment,
        EnvironmentReader $environmentReader,
        EnvironmentConfig $environmentConfig,
        Schema $schema
    ) {
        $this->environment = $environment;
        $this->environmentReader = $environmentReader;
        $this->environmentConfig = $environmentConfig;
        $this->schema = $schema;
    }

    /**
     * Returns all merged configuration for deploy stage.
     *
     * @return mixed
     * @throws \RuntimeException If the configuration file can't be read or can't be parsed
     */
    public function get(): array
    {
        try {
            if (null === $this->mergedConfig) {
                $envConfig = $this->environmentReader->read()[DeployInterface::SECTION_STAGE] ?? [];

                $this->mergedConfig = array_replace(
                    $this->schema->getDefaults(StageConfigInterface::STAGE_DEPLOY),
                    $this->getDeployConfiguration(),
                    $envConfig[DeployInterface::STAGE_GLOBAL] ?? [],
                    $envConfig[DeployInterface::STAGE_DEPLOY] ?? [],
                    $this->environmentConfig->getAll()
                );
            }

            return $this->mergedConfig;
        } catch (\Exception $exception) {
            throw new \RuntimeException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * Resolves default configuration value for deploy stage.
     *
     * SCD_THREADS = 3 for production environment.
     *
     * @return array
     * @deprecated Threads count shouldn't depend on ENV_MODE variable as this variable doesn't set anymore.
     *             Threads environment variables or .magento.env.yaml must be used instead.
     */
    private function getDeployConfiguration(): array
    {
        $config = [];

        $envVar = $this->environment->getEnvVarName(SystemConfigInterface::VAR_ENV_MODE);

        if ($this->environment->getEnv($envVar) === Environment::CLOUD_MODE_ENTERPRISE) {
            $config[DeployInterface::VAR_SCD_THREADS] = 3;
        }

        return $config;
    }
}
