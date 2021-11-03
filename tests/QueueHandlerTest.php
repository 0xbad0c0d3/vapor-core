<?php

namespace Laravel\Vapor\Tests;

use Illuminate\Support\Str;
use Laravel\Vapor\Runtime\Handlers\QueueHandler;
use Laravel\Vapor\VaporServiceProvider;
use Mockery;
use Orchestra\Testbench\TestCase;

class QueueHandlerTest extends TestCase
{

    protected function getPackageProviders($app)
    {
        return [VaporServiceProvider::class];
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

    public function fatProvider()
    {
        $max = system('getconf ARG_MAX');

        return [
            'extra slim' => [''],
            '1k' => [Str::random(1024)],
            '8k' => [Str::random(8192)],
            'extra faat' => [Str::random($max)],
        ];
    }

    /**
     * @param string $fat
     * @dataProvider fatProvider
     */
    public function testEventHandling(string $fat)
    {
        FakeFatJob::$handled = false;
        $job = new FakeFatJob($fat);
        QueueHandler::$app = $this->app;
        $handler = new QueueHandler();
        $handler->handle([
            'Records' => [
                [
                    'messageId' => 'test-message-id',
                    'receiptHandle' => 'test-receipt-handle',
                    'body' => json_encode([
                        'displayName' => FakeFatJob::class,
                        'job' => 'Illuminate\Queue\CallQueuedHandler@call',
                        'maxTries' => null,
                        'timeout' => null,
                        'timeoutAt' => null,
                        'data' => [
                            'commandName' => FakeFatJob::class,
                            'command' => serialize($job),
                        ],
                        'attempts' => 0,
                    ]),
                    'attributes' => [
                        'ApproximateReceiveCount' => 1,
                    ],
                    'messageAttributes' => [],
                    'eventSourceARN' => 'arn:aws:sqs:us-east-1:959512994844:vapor-test-queue-2',
                    'awsRegion' => 'us-east-1',
                ]
            ]
        ]);
        $this->assertTrue(FakeFatJob::$handled);
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('queue.connections.vapor', [
            'driver' => 'sqs',
            'key' => env('SQS_KEY', 'your-public-key'),
            'secret' => env('SQS_SECRET', 'your-secret-key'),
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => env('SQS_QUEUE', 'your-queue-name'),
            'region' => env('SQS_REGION', 'us-east-1'),
            'delay' => env('SQS_DELAY', 0),
            'tries' => env('SQS_TRIES', 0),
            'force' => env('SQS_FORCE', false),
        ]);
    }
}
