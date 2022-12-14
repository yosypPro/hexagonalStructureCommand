<?php

namespace YosypPro\HexagonalStructureCommand\Trait;

use YosypPro\HexagonalStructureCommand\CommandInputContainer;
use YosypPro\HexagonalStructureCommand\Templates\BasePhpClassTemplate;
use RuntimeException;

trait CommandBodyStepsTrait {

    /** @var $commandInputContainer CommandInputContainer  */
    protected $commandInputContainer;

    protected $isDebuggingMode = false;

    /**
     * @throws RuntimeException
     */
    private function loadYamlCommandConfigurationStep(): void
    {
        $title = '> Loading command configuration file...';

        $this->writeLine($this->repeatChar(strlen($title), '-'));
        $this->writeLine($title);
        $this->writeLine();

        // Check if project has self "command_configuration.yaml" in  src/Command/HexagonalStructureCommand/Configuration path
        $customConfiguration = [];
        $selfConfigurationFilePath =
            $this->projectPath . 'src' . DIRECTORY_SEPARATOR . 'Command' . DIRECTORY_SEPARATOR . 'HexagonalStructureCommand' .
            DIRECTORY_SEPARATOR . 'Configuration' . DIRECTORY_SEPARATOR;

        $selfConfigurationFile = $selfConfigurationFilePath . $this->configurationFile;
        
        // if not exist file on theese path, show advertistment to user
        if (!file_exists($selfConfigurationFile)) {
            $this->writeLine();
            $this->writeComment($this->repeatChar(64, '-'));
            $this->writeComment('Self application command configuration not found (looking for ' . $selfConfigurationFile . ')');
            $this->writeComment('Please create this file to store the ideal configuration for your project to avoid possible future errors.');
            $this->writeComment('Default configuration may vary throughout the development of the command');
            $this->writeLine();
            $this->writeComment('Tip: you can copy the default command-config file into ' . $selfConfigurationFilePath . ' path');
            $this->writeComment($this->repeatChar(64, '-'));
            $this->writeLine();

        } else {
            $customConfiguration = $this->loadYaml($selfConfigurationFile);
        }
        
        // if exist, compare two existing files and join/merge config (self-project config file will be first)

        // default configuration file
        $configuration = $this->loadYaml($this->commandPath . 'Configuration' . DIRECTORY_SEPARATOR . $this->configurationFile);

        if (!$configuration) {
            throw new RuntimeException('Invalid YAML configuration');
        }

        // merge config files into single config array
        foreach ($configuration as $key => $configItem) {
            if (isset($customConfiguration[$key])) {
                $configuration[$key] = $customConfiguration[$key];
                $this->writeSuccess('Import ' . $key . ' configuration from custom file done');
            }
        }

        $namespaceConfig = $configuration['namespaceConfig'] ?? null;
        if (!$namespaceConfig) {
            throw new RuntimeException('Missing namespace configuration');
        }

        $templatesConfig = $configuration['templates'] ?? null;
        if (!$templatesConfig) {
            throw new RuntimeException('Missing templates configuration');
        }

        $this->configurationData = $configuration;
        $this->writeLine();
        $this->writeLine();
        $this->writeSuccess('> Command configuration successfully loaded...');
        $this->writeLine($this->repeatChar(strlen($title), '-'));
        $this->writeLine();
        $this->writeLine();
        $this->writeLine();
    }

    /**
     * @throws RuntimeException
     */
    private function packageNameInputStep(): void
    {
        try {
            $packageNameUserResponse = trim($this->getUserInputRequest('Define context name, for example: Product'));
            $packageNameUserResponse = $this->formatToLowerCamelCase($packageNameUserResponse);

            if ($packageNameUserResponse === self::DEBUG_MAGIC_WORD) {
                $this->isDebuggingMode = true;
                $packageNameUserResponse .= time();
            }

            $this->ensureUserInputResponseIsValid($packageNameUserResponse);

            $this->commandInputContainer->addInput(
                'packageName',
                $packageNameUserResponse
            );

            $this->commandInputContainer->addInput(
                'packageRootPath',
                $this->projectPath . $this->configurationData['rootPath']
                . ucfirst($this->formatToLowerCamelCase($this->commandInputContainer->getInput('packageName')))
                . DIRECTORY_SEPARATOR
            );

            // Store package paths to create it after:
            $modelPath = $this->commandInputContainer->getInput('packageRootPath');
            $applicationPath = $modelPath . 'Application' . DIRECTORY_SEPARATOR;
            $domainPath = $modelPath . 'Domain' . DIRECTORY_SEPARATOR;

            $this->commandInputContainer->addInput('packageModelPath', $modelPath);
            $this->commandInputContainer->addInput('packageApplicationPath', $applicationPath);
            $this->commandInputContainer->addInput('packageDomainPath', $domainPath);

        } catch (RuntimeException $exception) {
            throw new RuntimeException('INVALID PACKAGE NAME ON STEP 1: "' . $exception->getMessage() . '"');
        }
    }

    private function packageEntityNameInputStep(): void
    {
        try {
            if ($this->isDebuggingMode === true) {
                $packageEntityNameUserResponse = self::DEBUG_PACKAGE_ENTITY_NAME;
            } else {
                $packageEntityNameUserResponse = trim($this->getUserInputRequest('Type your Entity name, for example: Laptop'));
            }

            $packageEntityNameUserResponse = ucfirst($this->formatToLowerCamelCase($packageEntityNameUserResponse));
            $this->ensureUserInputResponseIsValid($packageEntityNameUserResponse);

            $this->commandInputContainer->addInput(
                'packageEntityName',
                $packageEntityNameUserResponse
            );

            $this->commandInputContainer->addInput(
                'packageApplicationEntityPath',
                $this->commandInputContainer->getInput('packageApplicationPath') . $packageEntityNameUserResponse . DIRECTORY_SEPARATOR
            );

            $this->commandInputContainer->addInput(
                'packageDomainEntityPath',
                $this->commandInputContainer->getInput('packageDomainPath') . $packageEntityNameUserResponse . DIRECTORY_SEPARATOR
            );

            $this->commandInputContainer->addInput(
                'packageDomainEntityModelPath',
                $this->commandInputContainer->getInput('packageDomainEntityPath') . 'Entity' . DIRECTORY_SEPARATOR
            );

            $this->commandInputContainer->addInput(
                'packageDomainEntityServicePath',
                $this->commandInputContainer->getInput('packageDomainEntityPath') . 'Service' . DIRECTORY_SEPARATOR
            );

            $this->commandInputContainer->addInput(
                'packageDomainEntityRepositoryPath',
                $this->commandInputContainer->getInput('packageDomainEntityPath') . 'Repository' . DIRECTORY_SEPARATOR
            );

            $this->commandInputContainer->addInput(
                'packageDomainEntityExceptionPath',
                $this->commandInputContainer->getInput('packageDomainEntityPath') . 'Exception' . DIRECTORY_SEPARATOR
            );

        } catch (RuntimeException) {

        }
    }

    private function packageApplicationActionInputStep(): void
    {
        if ($this->isDebuggingMode === true) {
            $packageActionUserResponse = self::DEBUG_PACKAGE_ACTION_NAME;
        } else {
            $packageActionUserResponse = $this->getUserInputRequest('Define your package use case, for example: "get cheap products"');
        }

        $packageActionUserResponse = $this->formatToLowerCamelCase($packageActionUserResponse);

        $this->ensureUserInputResponseIsValid($packageActionUserResponse);

        $this->commandInputContainer->addInput(
            'packageAction',
            $packageActionUserResponse
        );
    }

    private function packageApplicationActionTypeInputStep(): void
    {
        $useCaseName = ucfirst($this->commandInputContainer->getInput('packageAction'));
        $this->writeTitle('Define use-case type for "' . $useCaseName . '":');
        $this->writeLine('1: Query');
        $this->writeLine('2: Command');

        if ($this->isDebuggingMode === true) {
            $packageApplicationActionTypeUserResponse = self::PACKAGE_ACTION_TYPE_READ_AND_COMMAND;
        } else {
            $packageApplicationActionTypeUserResponse = (int) $this->getUserInputRequest('');
        }

        // Define and get it from yaml config
        $allowedResponse = [
            self::PACKAGE_ACTION_TYPE_READ,
            self::PACKAGE_ACTION_TYPE_COMMAND,
            self::PACKAGE_ACTION_TYPE_READ_AND_COMMAND
        ];

        if (!in_array($packageApplicationActionTypeUserResponse, $allowedResponse)) {
            throw new RuntimeException('Invalid action type response');
        }

        $this->commandInputContainer->addInput('packageApplicationActionType', $packageApplicationActionTypeUserResponse);

        /* Create Application Action/{type} path */
        if (in_array($packageApplicationActionTypeUserResponse, [
            self::PACKAGE_ACTION_TYPE_READ, self::PACKAGE_ACTION_TYPE_READ_AND_COMMAND
        ], true)) {
            $this->commandInputContainer->addInput(
                'packageApplicationEntityQueryPath',
                $this->commandInputContainer->getInput('packageApplicationEntityPath') . 'Query' .
                DIRECTORY_SEPARATOR . $useCaseName . DIRECTORY_SEPARATOR
            );
        }

        if (in_array($packageApplicationActionTypeUserResponse, [
            self::PACKAGE_ACTION_TYPE_COMMAND, self::PACKAGE_ACTION_TYPE_READ_AND_COMMAND
        ], true)) {
            $this->commandInputContainer->addInput(
                'packageApplicationEntityCommandPath',
                $this->commandInputContainer->getInput('packageApplicationEntityPath') . 'Command' .
                DIRECTORY_SEPARATOR . $useCaseName . DIRECTORY_SEPARATOR
            );
        }
    }

    private function packageApplicationActionReadArgsStep():void
    {
        // Define entity attributes
        $entityName = $this->commandInputContainer->getInput('packageEntityName');
        $this->writeTitle('Define "' . $entityName . '" entity attributes');
        $this->writeLine('Type one by one your expected response keys (for example: id, name, phoneNumber)');
        $this->writeComment('Please use camelCase or separated by space format: "phoneNumber" or "phone number"');

        if ($this->isDebuggingMode === true) {
            $params = self::DEBUG_DATA_PARAMETERS;
        } else {
            $params = $this->getUserPackageApplicationParameters();
        }

        $this->commandInputContainer->addInput('packageDomainEntityAttributes', $params);

        // Define response params types
        // check first if not empty
        if (count($params) > 0 && !$this->isDebuggingMode) {
            $this->getUserPackageApplicationParametersTypes($params);
            $this->commandInputContainer->addInput('packageDomainEntityAttributes', $params);
        }

        // Define application query params
        $entityParams = $params;

        $this->writeTitle('Query filter params definition...');

        if ($this->isDebuggingMode === true) {
            $readQueryParams = $entityParams;
        } else {
            $readQueryParams = $this->getBoolUserResponseByYesOrNotStep($entityParams);
        }

        $this->commandInputContainer->addInput('packageApplicationActionReadQueryParams', $readQueryParams);

        // Check if response is an item collection (array)
        $this->writeTitle('Response collection');
        $this->writeLine('Is the response of the application query a data collection (array)?');
        $this->writeComment('Select option: "y" or "n"');

        // if (1): create EntityList class as list of Entity+ items
        if ($this->isDebuggingMode === true) {
            $applicationReadResponseIsList = self::APPLICATION_RESPONSE_IS_DATA_COLLECTION;
        } else {
            $applicationReadResponseIsList = $this->getBoolUserResponseByYesOrNot();
        }

        $this->commandInputContainer->addInput('packageApplicationActionReadResponseIsCollection', $applicationReadResponseIsList);
    }

    private function getBoolUserResponseByYesOrNotStep($entityProperties): array
    {
        $queryParams = [];
        foreach ($entityProperties as $entityProperty) {
            $this->writeLine('Do you want to use "' . $entityProperty['name'] . '" as application query param?');
            $this->writeComment('Select option: "y" or "n"');

            $userResponse = $this->getBoolUserResponseByYesOrNot();
            if ($userResponse === 1) {
                $queryParams[] = $entityProperty;
            }
        }

        return $queryParams;
    }

    private function getBoolUserResponseByYesOrNot(): int
    {
        try {
            $userResponse = strtolower(trim($this->getUserInputRequest()));

            if (!in_array($userResponse, ['y', 'n'])) {
                throw new RuntimeException('Invalid response');
            }

            return $userResponse === 'y' ? 1 : 0;

        } catch (RuntimeException) {
            $this->writeError('Invalid answer, type one: "y" or "n"');
            return $this->getBoolUserResponseByYesOrNot();
        }
    }

    private function packageApplicationActionCommandArgsStep():void
    {
        // Define command params (POST, PUT, PATCH)
        $this->writeTitle('Define your command keys (post variables)');
        $this->writeLine('Type one by one your command keys (for example: name, sur_name, birthdate, country)');
        $this->writeComment('Please use camelCase or separated by space format: "phoneNumber" or "phone number"');

        if ($this->isDebuggingMode === true) {
            $params = self::DEBUG_DATA_PARAMETERS;
        } else {
            $params = $this->getUserPackageApplicationParameters();
        }

        $this->commandInputContainer->addInput('packageApplicationActionCommandParams', $params);

        if (count($params) > 0 && !$this->isDebuggingMode) {
            // define each command-key type
            $this->getUserPackageApplicationParametersTypes($params);
            $this->commandInputContainer->addInput('packageApplicationActionCommandParams', $params);

            $this->writeTitle('Command data as collection');
            $this->writeLine('Is the command parameters a data collection (array)? (Post multiple data...)');
            $this->writeComment('Select one: "y" or "n"');

            $applicationCommandParametersIsList = $this->getBoolUserResponseByYesOrNot();
            $this->commandInputContainer->addInput('packageApplicationActionCommandParametersIsCollection', $applicationCommandParametersIsList);
        }

        /*
        $this->writeTitle('Define your expected command response attributes');
        $this->writeLine('Type one by one your expected command response keys (for example: id, name, phoneNumber)');
        $this->writeComment('Please use camelCase or separated by space format: "phoneNumber" or "phone number"');

        if ($this->isDebuggingMode === true) {
            $params = self::DEBUG_DATA_PARAMETERS;
        } else {
            $params = $this->getUserPackageApplicationParameters();
        } */

        $this->commandInputContainer->addInput('packageApplicationActionCommandResponseParams',
            $this->commandInputContainer->getInput('packageDomainEntityAttributes')
        );

        // Define command response params types
        if (count($params) > 0 && !$this->isDebuggingMode) {

            $this->getUserPackageApplicationParametersTypes($params);
            $this->commandInputContainer->addInput('packageApplicationActionCommandResponseParams', $params);
        }

        $this->writeTitle('Command Response type');
        $this->writeLine('Is the response of the application-command a data collection (array)?');
        $this->writeComment('Select: "y" or "n"');

        if ($this->isDebuggingMode === true) {
            $applicationCommandResponseIsList = self::APPLICATION_RESPONSE_IS_DATA_COLLECTION;
        } else {
            $applicationCommandResponseIsList = $this->getBoolUserResponseByYesOrNot();
        }

        $this->commandInputContainer->addInput('packageApplicationActionCommandResponseIsCollection', $applicationCommandResponseIsList);
    }

    private function createPackagePathsStep(): void
    {
        try {

            // Create PackageRoot/ path
            $toCreateDirs[] = $this->commandInputContainer->getInput('packageRootPath');

            // Create PackageRoot/Model/ path
            $toCreateDirs[] = $this->commandInputContainer->getInput('packageModelPath');

            // Create PackageRoot/Model/Application path
            $toCreateDirs[] = $this->commandInputContainer->getInput('packageApplicationPath');

            // Create PackageRoot/Model/Application/PackageAction path
            $toCreateDirs[] = $this->commandInputContainer->getInput('packageApplicationEntityPath');

            // Package application entity  -> read & command

            // Create PackageRoot/Model/Domain path
            $toCreateDirs[] = $this->commandInputContainer->getInput('packageDomainPath');

            // Create PackageRoot/Model/Domain/PackageAction path
            $toCreateDirs[] = $this->commandInputContainer->getInput('packageDomainEntityPath');

            // Create PackageRoot/Model/Domain/Model path
            $toCreateDirs[] = $this->commandInputContainer->getInput('packageDomainEntityModelPath');

            // Create PackageRoot/Model/Domain/Service path
            $toCreateDirs[] = $this->commandInputContainer->getInput('packageDomainEntityServicePath');

            // Create PackageRoot/Model/Domain/Repository path
            $toCreateDirs[] = $this->commandInputContainer->getInput('packageDomainEntityRepositoryPath');

            // Create PackageRoot/Model/Domain/PackageAction/Exceptions path
            $toCreateDirs[] = $this->commandInputContainer->getInput('packageDomainEntityExceptionPath');
            // $packageDomainActionExceptionsPath = $this->commandInputContainer->getInput('packageDomainEntityExceptionsPath');
            // $this->createDir($packageDomainActionExceptionsPath);

            // Create PackageRoot/Model/Application/PackageAction/Read and Command oath
            if ($this->commandInputContainer->existsInput('packageApplicationEntityQueryPath')) {
                $toCreateDirs[] = ($this->commandInputContainer->getInput('packageApplicationEntityQueryPath'));
            }

            if ($this->commandInputContainer->existsInput('packageApplicationEntityCommandPath')) {
                $toCreateDirs[] = ($this->commandInputContainer->getInput('packageApplicationEntityCommandPath'));
            }

            $title = 'Creating package dirs:';
            $this->writeLine();
            $this->writeSuccess($this->repeatChar(64, '-'));
            $this->writeSuccess($title);
            $this->writeSuccess($this->repeatChar(64, '-'));

            foreach ($toCreateDirs as $dir) {
                $this->createDir($dir);
            }

        } catch (RuntimeException $exception) {
            throw new RuntimeException('Can not create package path: ' . $exception->getMessage());
        }
    }

    private function createPackageFilesStep(): void
    {
        $templatesConfig = $this->configurationData['templates'];
        $packageActionType = $this->commandInputContainer->getInput('packageApplicationActionType');
        $classFilesToParse = [];

        // QUERY/READ FILES
        if ($packageActionType === self::PACKAGE_ACTION_TYPE_READ_AND_COMMAND || $packageActionType === self::PACKAGE_ACTION_TYPE_READ) {

            $readConfig = $templatesConfig['read'];
            $applicationQueryPath = $this->commandInputContainer->getInput('packageApplicationEntityQueryPath');

            foreach ($readConfig as $key => $value) {
                $className = ucfirst($this->commandInputContainer->getInput('packageAction')) . $value['suffix'];
                $fileName = $className . '.php';
                $filePath = $applicationQueryPath . $fileName;

                $classFilesToParse[] = array_merge($value, [
                    'filePath' => $filePath,
                    'className' => $className
                ]);
            }
        }

        // COMMAND FILES
        if ($packageActionType === self::PACKAGE_ACTION_TYPE_READ_AND_COMMAND || $packageActionType === self::PACKAGE_ACTION_TYPE_COMMAND) {
            $commandConfig = $templatesConfig['command'];
            $applicationCommandPath = $this->commandInputContainer->getInput('packageApplicationEntityCommandPath');

            foreach ($commandConfig as $key => $value) {
                $className = ucfirst($this->commandInputContainer->getInput('packageAction')) . $value['suffix'];
                $fileName = $className . '.php';
                $filePath = $applicationCommandPath . $fileName;

                $classFilesToParse[] = array_merge($value, [
                    'filePath' => $filePath,
                    'className' => $className
                ]);
            }
        }

        // DOMAIN & ENTITY FILES
        $domainConfig = $templatesConfig['domain'];

        foreach ($domainConfig as $key => $value) {
            $className = match ($key) {
                'repositoryInterface', 'entity', 'entityCollection' => $this->commandInputContainer->getInput('packageEntityName'),
                'service' => $this->commandInputContainer->getInput('packageAction')
            };

            $filePath = match ($key) {
                'repositoryInterface' => $this->commandInputContainer->getInput('packageDomainEntityRepositoryPath'),
                'service' => $this->commandInputContainer->getInput('packageDomainEntityServicePath'),
                'entity', 'entityCollection' => $this->commandInputContainer->getInput('packageDomainEntityModelPath')
            };

            $className = ucfirst($className) . $value['suffix'];
            $fileName = $className . '.php';
            $filePath = $filePath . $fileName;

            $classFilesToParse[] = array_merge($value, [
                'filePath' => $filePath,
                'className' => $className
            ]);
        }

        $title = 'Creating package files:';
        $this->writeLine();
        $this->writeSuccess($this->repeatChar(64, '-'));
        $this->writeSuccess($title);
        $this->writeSuccess($this->repeatChar(64, '-'));

        $classFilesToParse = BasePhpClassTemplate::sortByDependencies($classFilesToParse);
        $createdInstances = [];

        foreach ($classFilesToParse as $item) {

            $templateDispatcher = BasePhpClassTemplate::getNamespace() . '\\' . $item['templateClassDispatcher'];
            if (!class_exists($templateDispatcher)) {
                $this->writeComment('Template parser for class template ' . $item['templateClassDispatcher'] . ' not found, please create this file! [continue...]');
                continue;
            }

            try {
                /** @var BasePhpClassTemplate $templateDispatcher */
                $instance = new $templateDispatcher(
                    $this->configurationData,
                    $this->commandInputContainer,
                    $this->getTemplateContent($item['template']),
                    $item,
                    $createdInstances
                );

                $createdInstances[$item['templateClassDispatcher']] = $instance;
                $parsedTemplate = $instance->generateParsedTemplateOutput();

                $successClassFileCreated = $this->createFile($item['filePath'], $parsedTemplate);
            } catch (RuntimeException $exception) {

            }
        }
    }
}