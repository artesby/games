<?php
/**
 *
 * @author malinink
 */
namespace App\Sockets;

use ZMQ;
use ZMQContext;
use Exception;
use SplObjectStorage;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class PushServerSocket implements MessageComponentInterface
{
    protected $clients;
    
    /**
     * Contain identified clients.
     * Set up a correspondence between userId and clientId.
     *
     * @var array
     */
    protected $clientToUserIds;
    
    /**
     * Contain arrays of a subscribed clients of every game.
     *
     * @var array
     */
    protected $clientsInGameId;
    
    /**
     *
     * @var SplObjectStorage
     */
    protected $gameOfClients;
    
    public function __construct()
    {
        $this->clients = new SplObjectStorage();
        $this->clientToUserIds = [];
        $this->clientsInGameId = [];
        $this->gameOfClients = new SplObjectStorage();
    }
    
    public static function setDataToServer($data)
    {
        $context = new ZMQContext();
        $socket = $context->getSocket(ZMQ::SOCKET_PUSH);
        $socket->connect('tcp://127.0.0.1:5555');
        $socket->send(json_encode($data));
    }
    
    public function __invoke($data)
    {
        $data = json_decode($data, true);
        echo sprintf('invoked with data' . PHP_EOL);
        foreach ($this->clients as $client) {
            $client->send($data);
        }
    }
    
    public function onOpen(ConnectionInterface $conn)
    {
        echo sprintf('client %s connected' . PHP_EOL, $conn->resourceId);
        $this->clients->attach($conn);
    }

    public function onClose(ConnectionInterface $conn)
    {
        echo sprintf('client %s disconnected' . PHP_EOL, $conn->resourceId);
        $this->unlinkClientFromGameId($conn, $this->gameOfClients[$conn]);
        $this->unlinkUserIdFromClient($conn);
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, Exception $e)
    {
        echo sprintf('client %s error message: %' . PHP_EOL, $e->getMessage());
        $this->clients->detach($conn);
        $conn->close();
    }
    
    public function onMessage(ConnectionInterface $client, $msg)
    {
        try {
            $dataJs = json_decode($msg, true);
            if ($dataJs === null) {
                throw new Exception("It is not json!");
            }
            $type = $dataJs['name'];
            $data = $dataJs['data'];

            $class = "\App\Socket\Protocol\\". ucfirst($type). "Protocol";
            $interfaces = class_implements($class);

            if (class_exists($class) && isset($interfaces["App\Sockets\Protocol\ProtocolInterface"])) {
                $obj = new $class($data, $client, $this);
                $obj->compile();
            }
        } catch (Exception $e) {
            echo sprintf('something wrong!', $e->getMessage());
        }
    }
    
    public function linkUserIdToClient(ConnectionInterface $client, $userId)
    {
        $this->clientToUserIds[$client->resourceId] = $userId;
    }
    
    /**
     * Get array of the game's subscribers.
     * @param int $gameId
     *
     * @return SplObjectStorage
     */
    public function getGameSubscribedClients($gameId)
    {
        if (isset($this->clientsInGameId[$gameId])) {
            return $this->clientsInGameId[$gameId];
        } else {
            return new SplObjectStorage();
        }
    }
    
    /**
     *
     * @param ConnectionInterface $client
     */
    public function unlinkUserIdFromClient(ConnectionInterface $client) {
        if (isset($this->clientToUserIds[$client->resourceId])) {
            unset($this->clientToUserIds[$client->resourceId]);
        }
    }
    
    /**
     * Set up a correspondence between gameId and clientId.
     * @param ConnectionInterface $client
     * @param int $gameId
     *
     * @return boolean
     */
    public function linkClientIdToGame(ConnectionInterface $client, $gameId)
    {
        if (isset($this->clientToUserIds[$client->resourceId])) {
            if (isset($this->clientsInGameId[$gameId])) {
                $this->clientsInGameId[$gameId]->attach($client);
                $this->gameOfClients[$client]->attach($gameId);
                return true;
            } else {
                $this->clientsInGameId[$gameId] = new SplObjectStorage();
                $this->cleintToGameIds[$gameId]->attach($client);
                $this->gameOfClients[$client]->attach($gameId);
                return true;
            }
        } else {
            return false;
        }
    }
    
    /**
     * Detach between gameId and clientId.
     * @param ConnectionInterface $client
     * @param int $gameId
     *
     * @return boolean
     */
    public function unlinkClientFromGameId(ConnectionInterface $client, $gameId)
    {
        if (isset($this->clientsInGameId[$gameId])) {
            $this->clientsInGameId[$gameId]->detach($client);
            $this->gameOfClients->detach($client);
        }
    }
    
    /**
     * Delete all connections of game
     * @param int $gameId
     */
    public function unlinkGameClients($gameId) {
        if (isset($this->clientsInGameId[$gameId])) {
            foreach ($this->clientsInGameId[$gameId] as $client) {
                $this->gameOfClients->detach($client);
            }
            unset($this->clientsInGameId[$gameId]);
            
        }
    }
}
