<?php

require dirname(__DIR__) . '/vendor/autoload.php';

return new class {
    private \App\Kernel $kernel;
    private \Symfony\Bundle\FrameworkBundle\Console\Application $application;

    public function __construct()
    {
        $this->kernel = new \App\Kernel($_SERVER['APP_ENV'], (bool)($_SERVER['APP_DEBUG'] ?? false));
        $this->application = new \Symfony\Bundle\FrameworkBundle\Console\Application($this->kernel);
        $this->application->setAutoExit(false); // disable exit statement that normally runs after a command
    }

    /**
     * @param array $request
     * @see https://docs.aws.amazon.com/AWSCloudFormation/latest/UserGuide/crpg-ref-requests.html
     */
    public function __invoke(array $request): bool
    {
        try {
            echo "REQUEST RECEIVED:\n", json_encode($request, JSON_THROW_ON_ERROR);
            $version = $request['ResourceProperties']['Version'] ?? null;
            if (!$version) {
                throw new RuntimeException("You need to define the Version property.");
            }

            if ($request['RequestType'] === 'Delete') {
                $this->cmd('doctrine:schema:drop', '--force', '--full-database');
                return $this->respond($request, true);
            }

            $this->cmd('doctrine:migrations:migrate', '--no-interaction', $version);
            return $this->respond($request, true);
        } catch (\Throwable $e) {
            if (strpos($e->getMessage(), 'Communications link failure') !== false) {
                throw $e; // let lambda reattempt this action if aurora is paused
            }
            return $this->respond($request, false, $e->getMessage());
        }
    }

    private function cmd(string ...$args): void
    {
        $input = new \Symfony\Component\Console\Input\ArgvInput(['', ...$args]);
        $output = new \Symfony\Component\Console\Output\BufferedOutput();
        $status = $this->application->run($input, $output);
        $message = $output->fetch(); // fetch wipes the output so store it
        echo $message; // ensure the output appears in the log
        if ($status !== 0) {
            throw new \RuntimeException($message, $status);
        }
    }

    /**
     * This is a php implementation of the cfn-response module.
     *
     * @see https://docs.aws.amazon.com/AWSCloudFormation/latest/UserGuide/cfn-lambda-function-code-cfnresponsemodule.html#w2ab1c19c24c14b9c15
     * @see https://docs.aws.amazon.com/AWSCloudFormation/latest/UserGuide/crpg-ref-responses.html
     * @param array $request
     * @param bool $success
     * @param string $reason
     * @noinspection NullPointerExceptionInspection
     */
    private function respond(array $request, bool $success, string $reason = ''): bool
    {
        $connection = $this->kernel->getContainer()->get('doctrine.dbal.default_connection');
        $body = json_encode([
            'Status' => $success ? 'SUCCESS' : 'FAILED',
            'Reason' => $reason,
            'PhysicalResourceId' => $connection->getDatabase(), // what you get using !Ref
            'StackId' => $request['StackId'],
            'RequestId' => $request['RequestId'],
            'LogicalResourceId' => $request['LogicalResourceId'],
            //'NoEcho' => false, // set to true if you ever have sensitive data in Data
            //'Data' => [],
        ], JSON_THROW_ON_ERROR);

        $client = \Symfony\Component\HttpClient\HttpClient::create(); // http_client service is private so just create one
        $client->request('PUT', $request['ResponseURL'], [
            'body' => $body,
            'headers' => [
                'content-type' => '',
                'content-length' => strlen($body),
            ],
        ]);

        return $success;
    }
};
