<?php
final class Connection{

    private static ?self $instance = null;
    private  static string $name;

    /**
     * @return string
     */
    public static function getName(): string
    {
        return self::$name;
    }

    /**
     * @param string $name
     */
    public static function setName(string $name): void
    {
        self::$name = $name;
    }

    public  static function getInstancee(): self
    {
        if(self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public  function __clone(): void{

    }

    public  function __wakeup(): void{

    }

}



$connection = Connection::getInstancee();
$connection::setName('Lara');

$connection2 =Connection::getInstancee();

var_dump($connection2::getName());
