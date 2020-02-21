<?php

namespace skywars\provider;

use pocketmine\Server;
use skywars\provider\target\TargetOffline;

class MysqlProvider implements Provider {

    /** @var array */
    private $data;

    /**
     * MysqlProvider constructor.
     * @param array $data
     */
    public function __construct(array $data) {
        $this->data = $data;
    }

    /**
     * @param string $name
     * @return TargetOffline|null
     */
    public function getTargetOffline(string $name): ?TargetOffline {
        $connection = mysqli_connect($this->data['address'], $this->data['username'], $this->data['password'], $this->data['database']);

        if(mysqli_connect_errno()) {
            Server::getInstance()->getLogger()->error(mysqli_connect_error());
            die();
        } else {
            $query = mysqli_query($connection, "SELECT * FROM players WHERE username = '{$name}'");

            mysqli_close($connection);

            if(mysqli_num_rows($query) > 0) {
                return new TargetOffline(mysqli_fetch_assoc($query));
            }
        }

        return null;
    }

    public function setTargetOffline(TargetOffline $target) {
        $connection = mysqli_connect($this->data['address'], $this->data['username'], $this->data['password'], $this->data['database']);

        if(mysqli_connect_errno()) {
            Server::getInstance()->getLogger()->error(mysqli_connect_error());
            die();
        } else {
            $query = mysqli_query($connection, "SELECT * FROM players WHERE username = '{$target->getName()}'");

            $data = $target->data;

            if(mysqli_num_rows($query) <= 0) {
                $query = "INSERT INTO players (";

                $i = 0;

                foreach(array_keys($data) as $k) {

                    if($i != (count($data) - 1)) {
                        $query .= "{$k}, ";
                    } else {
                        $query .= "{$k}";
                    }

                    $i++;
                }

                $query .= ') VALUES (';

                $i = 0;

                foreach(array_values($data) as $v) {

                    if($i != (count($data) - 1)) {
                        $query .= "'{$v}', ";
                    } else {
                        $query .= "'{$v}'";
                    }

                    $i++;
                }

                mysqli_query($connection, $query);
            } else {
                $query = 'UPDATE players';

                $i = 0;

                foreach($data as $k => $v) {

                    if(count($data) == 1) {
                        $query .= " SET {$k} = '{$v}'";
                    } else {
                        if($i == 0) {
                            $query .= " SET {$k} = '{$v}', ";
                        } else if($i != (count($data) - 1)) {
                            $query .= "{$k} = '{$v}', ";
                        } else {
                            $query .= "{$k} = '{$v}'";
                        }
                    }

                    $i++;
                }

                $query .= " WHERE username = '{$target->getName()}'";

                mysqli_query($connection, $query);
            }
        }

        mysqli_close($connection);

        if(mysqli_errno($connection) > 0) {
            Server::getInstance()->getLogger()->error(mysqli_error($connection));
        }
    }

    public function getName(): string {
        return 'Mysql';
    }
}