<?php

namespace Efabrica\HttpClient\Retry;

use Symfony\Component\HttpClient\Retry\GenericRetryStrategy;
use Symfony\Component\HttpClient\Retry\RetryStrategyInterface;

class RetryStrategy extends CustomRetryStrategy
{
    public const STATUS_CODES = GenericRetryStrategy::DEFAULT_RETRY_STATUS_CODES;

    /**
     * @param int $maxRetries The maximum number of times to retry
     * @param array $statusCodes List of HTTP status codes that trigger a retry
     * @param int $delayMs Amount of time to delay (or the initial value when multiplier is used)
     * @param float $multiplier Multiplier to apply to the delay each time a retry occurs
     * @param int $maxDelayMs Maximum delay to allow (0 means no maximum)
     * @param float $jitter Probability of randomness int delay (0 = none, 1 = 100% random)
     */
    public function __construct(
        int $maxRetries = 3,
        array $statusCodes = self::STATUS_CODES,
        int $delayMs = 1000,
        float $multiplier = 2.0,
        int $maxDelayMs = 0,
        float $jitter = 0.1
    ) {
        parent::__construct(new GenericRetryStrategy(
            $statusCodes,
            $delayMs,
            $multiplier,
            $maxDelayMs,
            $jitter
        ), $maxRetries);
    }

    /**
     * This is useful when you want different behavior for different status codes, or other custom conditions.
     */
    public static function multi(ClientRetryStrategy ...$strategies): ClientRetryStrategy
    {
        return new MultiRetryStrategy(...$strategies);
    }

    /**
     * This is necessary if you want to use symfony's retry strategies.
     */
    public static function custom(RetryStrategyInterface $strategy, int $maxRetries = 3): ClientRetryStrategy
    {
        return new CustomRetryStrategy($strategy, $maxRetries);
    }
}
