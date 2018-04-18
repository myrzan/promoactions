<?
require("DatabaseAPI.php");

/**
 * Класс для работы с акциями.
 */
class PromoActions extends DatabaseAPI
{
	/**
	 * В этой переменной хранится название таблицы, с которой будем работать.
	 * @var string
	 */
	private $table;

	/**
	 * Если при создании объекта передано название таблицы, присваиваем её.
	 * По-умолчанию присвоим значение promoactions
	 * @param string $table Название таблицы
	 */
	public function __construct($table = "promoactions")
	{
		$this->table = $table;
		parent::__construct();
	}

	/**
	 * Функция создания таблицы.
	 */
	public function create_table()
	{
		$query = "CREATE TABLE `".$this->table."` (
			id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			title VARCHAR(255) NOT NULL,
			start_date INT NOT NULL,
			end_date DATE,
			status VARCHAR(3),
			UNIQUE KEY(`id`)
		);"; # укажем id уникальным индексом, чтобы при импорте данных из csv избежать дублей, использовав IGNORE
		if($this->sql($query))
		{
			echo "Таблицы `$this->table` была успешно создана." . $this->eol;
		}
	}

	/**
	 * Функция импорта данных из csv-файла в созданную таблицу.
	 * @param string $filename название файла
	 */
	public function importCSV($filename)
	{
		if($file = fopen($filename, "r"))
		{
			$query = "INSERT IGNORE INTO `".$this->table."` (id, title, start_date, end_date, status) VALUES ";
			$values_arr = array();
			$i = 0;
			while(($row = fgetcsv($file, 10000, ";")) !== false)
			{
				if($i > 0) # пропускаем первую строку, ибо там название колонок
				{
					$id = $row[0];
					$title = $this->connection->real_escape_string($row[1]);
					$start_date = intval(strtotime($row[2]));
					$end_date = date("Y-m-d", strtotime($row[3]));
					$status = $this->connection->real_escape_string($row[4]);
					$values_arr[] = sprintf("(%d, '%s', %d, '%s', '%s')", intval($id), $title, $start_date, $end_date, $status);
				}
				$i++;
			}
			fclose($file);
			if($this->sql($query.implode(",", $values_arr)))
			{
				echo "CSV-данные были успешно импортированы." . $this->eol;
			}
		}
		else
		{
			echo "Не могу открыть файл $filename." . $this->eol;
		}
	}

	/**
	 * Функция меняет статус у рандомной взятой записи и выводит её в csv-виде.
	 * @return string  - Строка, сформированная по записи.
	 * @return boolean - Выводит false если возникла ошибка.
	 */
	public function change_status()
	{
		$result = $this->sql("SELECT * FROM `" . $this->table . "` ORDER BY RAND() LIMIT 1");
		if($row = $result->fetch_assoc())
		{
			$id = $row['id'];
			$title = $row['title'];
			$start_date = date("d-m-Y", $row['start_date']);
			$end_date = date("d-m-Y", strtotime($row['end_date']));
			$status = ($row['status'] == 'Off') ? 'On' : 'Off';
			$this->sql("UPDATE `".$this->table."` SET `status` = '$status' WHERE `id` = " . $id);
			return sprintf("%d;\"%s\";%s;%s;%s", $id, $title, $start_date, $end_date, $status) . $this->eol;
		}
		return false;
	}

	/**
	 * Функция построения url по записям таблицы.
	 * @return array - Массив url
	 */
	public function make_urls()
	{
		$urls = array();
		$result = $this->sql("SELECT * FROM " . $this->table);
		if($result)
		{
			while($row = $result->fetch_assoc())
			{
				$title = preg_replace('~[^-a-z0-9_]+~u', '-', $this->transliterate($row['title']));
				$title = preg_replace('/-+/', '-', $title);
				$urls[] = $row['id']."-".trim($title, "-");
			}
			$result->free();
		}
		return $urls;
	}

	/**
	 * Функция транслитерации.
	 * @param  string $string - Строка на кириллице.
	 * @return string - Вывод строки на латинице.
	 */
	public function transliterate($string)
	{
		$string = mb_strtolower($string);
		$abc = array(
			"а" => "a", "ый" => "iy", "ые" => "ie",
			"б" => "b", "в" => "v", "г" => "g",
			"д" => "d", "е" => "e", "ё" => "yo",
			"ж" => "zh", "з" => "z", "и" => "i",
			"й" => "y", "к" => "k", "л" => "l",
			"м" => "m", "н" => "n", "о" => "o",
			"п" => "p", "р" => "r", "с" => "s",
			"т" => "t", "у" => "u", "ф" => "f",
			"х" => "kh", "ц" => "ts", "ч" => "ch",
			"ш" => "sh", "щ" => "shch", "ь" => "",
			"ы" => "y", "ъ" => "", "э" => "e",
			"ю" => "yu", "я" => "ya", "йо" => "yo",
			"ї" => "yi", "і" => "i", "є" => "ye",
			"ґ" => "g"
		);
		return strtr($string, $abc);
	}
}

$promo = new PromoActions;

echo "1. Создание таблицы." . $promo->eol;
$promo->create_table();
echo $promo->eol;

echo "2. Импорт данных из файла." . $promo->eol;
$promo->importCSV("data.csv");
echo $promo->eol;

echo "3. Меняем статус у рандомной записи и выводим её: " . $promo->eol;
echo $promo->change_status();
echo $promo->eol;

echo "4. Выводим url-ки всех акций:" . $promo->eol;
$urls = $promo->make_urls();

foreach($urls as $url)
{
	echo $url . $promo->eol;
}