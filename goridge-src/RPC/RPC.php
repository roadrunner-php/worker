<?php

/**
 * Dead simple, high performance, drop-in bridge to Golang RPC with zero dependencies
 *
 * @author Wolfy-J
 */

declare(strict_types=1);

namespace Spiral\Goridge\RPC;

class RPC implements RPCInterface
{
    private RelayInterface  $relay;
    private CodecInterface  $codec;

    /** @var positive-int */
    private static int $seq = 0;

    /**
     * @param RelayInterface      $relay
     * @param CodecInterface|null $codec
     */
    public function __construct(RelayInterface $relay, CodecInterface $codec = null)
    {
        $this->relay = $relay;
        $this->codec = $codec ?? new \RawCodec();
    }

    public function call(string $method, $payload)
    {
        $header = $method . pack('P', self::$seq);
        if (!$this->relay instanceof SendPackageRelayInterface) {
            $this->relay->send($header, Relay::PAYLOAD_CONTROL | Relay::PAYLOAD_RAW);
        }

        if ($flags & Relay::PAYLOAD_RAW && is_scalar($payload)) {
            if (!$this->relay instanceof SendPackageRelayInterface) {
                $this->relay->send((string) $payload, $flags);
            } else {
                $this->relay->sendPackage(
                    $header,
                    Relay::PAYLOAD_CONTROL | Relay::PAYLOAD_RAW,
                    (string) $payload,
                    $flags
                );
            }
        } else {
            $body = json_encode($payload);
            if ($body === false) {
                throw new Exceptions\ServiceException(
                    sprintf(
                        'json encode: %s',
                        json_last_error_msg()
                    )
                );
            }

            if (!$this->relay instanceof SendPackageRelayInterface) {
                $this->relay->send($body);
            } else {
                $this->relay->sendPackage($header, Relay::PAYLOAD_CONTROL | Relay::PAYLOAD_RAW, $body);
            }
        }

        $body = (string) $this->relay->receiveSync($flags);

        if (!($flags & Relay::PAYLOAD_CONTROL)) {
            throw new Exceptions\TransportException('rpc response header is missing');
        }

        $rpc = unpack('Ps', substr($body, -8));
        $rpc['m'] = substr($body, 0, -8);

        if ($rpc['m'] !== $method || $rpc['s'] !== self::$seq) {
            throw new Exceptions\TransportException(
                sprintf(
                    'rpc method call, expected %s:%d, got %s%d',
                    $method,
                    self::$seq,
                    $rpc['m'],
                    $rpc['s']
                )
            );
        }

        // request id++
        self::$seq++;

        // wait for the response
        $body = (string) $this->relay->receiveSync($flags);

        return $this->handleBody($body, $flags);
    }

    /**
     * Handle response body.
     *
     * @param string $body
     * @param int    $flags
     *
     * @return mixed
     *
     * @throws Exceptions\ServiceException
     */
    protected function handleBody(string $body, int $flags)
    {
        if ($flags & Relay::PAYLOAD_ERROR && $flags & Relay::PAYLOAD_RAW) {
            throw new Exceptions\ServiceException(
                sprintf(
                    "error '$body' on '%s'",
                    $this->relay instanceof StringableRelayInterface ? (string) $this->relay : get_class($this->relay)
                )
            );
        }

        if ($flags & Relay::PAYLOAD_RAW) {
            return $body;
        }

        return json_decode($body, true);
    }
}
