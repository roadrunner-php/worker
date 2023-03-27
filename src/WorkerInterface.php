<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner;

use Spiral\RoadRunner\Exception\RoadRunnerException;

interface WorkerInterface
{
    /**
     * Wait for incoming payload from the server.
     * Must return {@see null} when worker stopped.
     *
     * @return Payload|null
     * @throws RoadRunnerException
     */
    public function waitPayload(): ?Payload;

    /**
     * Respond to the server with the processing result.
     *
     * @param Payload $payload
     * @return void
     * @throws RoadRunnerException
     */
    public function respond(Payload $payload): void;

    /**
     * Respond to the server with an error.
     *
     * Error must be treated as TaskError and might not cause worker destruction.
     *
     * <code>
     *  $worker->error('Something Went Wrong');
     * </code>
     *
     * @param string $error
     * @return void
     * @throws RoadRunnerException
     */
    public function error(string $error): void;

    /**
     * Terminate the process. Server must automatically pass task to the next
     * available process. Worker will receive stop command after calling this
     * method.
     *
     * Attention, you MUST use continue; after invoking this method to let
     * RoadRunner to properly stop worker.
     *
     * @return void
     */
    public function stop(): void;

    /**
     * @param class-string<Payload>|null $class
     *
     * @return bool Returns {@see true} if worker is ready to accept new payload.
     */
    public function hasPayload(string $class = null): bool;

    /**
     * @param class-string<Payload>|null $class
     *
     * @return Payload|null Returns {@see null} if worker is not ready to accept new payload and has no cached payload
     *         of the given type.
     */
    public function getPayload(string $class = null): ?Payload;
}
