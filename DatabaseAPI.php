<?
/**
 * Простой класс для работы с MySQL-базой.
 */
class DatabaseAPI
{
	/**
	 * $host - Имя хоста или IP-адрес сервера базы данных
	 * @var string
	 */
	private $host = "";

	/**
	 * $user - Имя пользователя базы данных
	 * @var string
	 */
	private $user = "";

	/**
	 * $password - Пароль пользователя
	 * @var string
	 */
	private $password = "";

	/**
	 * $database - Имя базы данных. В данном случае `choco`
	 * @var string
	 */
	private $database = "choco";

	/**
	 * $eol - В зависимости от того, где выполнять скрипт script.php значение меняется: \n или <br>
	 * Я запускал в браузере, поэтому для удобочитаемости - тэг
	 * @var string
	 */
	public $eol = "<br>";

	/**
	 * $connection - объект mysqli
	 * @var [type]
	 */
	protected $connection;

	/**
	 * Конструктор класса. При создании объекта DatabaseAPI создается подключение к базе.
	 */
	public function __construct()
	{
		if(!isset($connection))
		{
			$this->connection = new mysqli($this->host, $this->user, $this->password, $this->database);
			if($this->connection->connect_error)
			{
				exit("Не смог подключиться к серверу базы данных. " . $this->connection->connect_error);
			}
			$this->connection->set_charset('utf8');
			return $this->connection;
		}
		return $this->connection;
	}

	/**
	 * Выполняет sql-запрос в базу.
	 * @param  string $query  - Запрос
	 * @return object|boolean - Если неудачный запрос возвращает false.
	 * Если это запросы select, show, describe или explain вернет mysqli-объект.
	 * Остальные успешные запросы возвращают true.
	 * http://php.net/manual/ru/mysqli.query.php#refsect1-mysqli.query-returnvalues
	 */
	public function sql($query)
	{
		$result = $this->connection->query($query);
		if($result)
		{
			return $result;
		}
		else
		{
			echo $this->connection->error . $this->eol;
			return false;
		}
	}
}