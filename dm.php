<?php

if ($argv[1] == '-h' || $argv[1] === '--help') {
    echo <<<help
        生成 Markdown 格式的 数据字典
        --help 查看帮助 
        --table 指定表, table1[,table2]
help;
    echo PHP_EOL;
    die;
}

$tables = [];

foreach ($argv as $key => $val) {
    if ($val === '--table') {
        $tables = explode(',', $argv[$key + 1]);
    }
}
$dsn = 'mysql:dbname=information_schema;host=127.0.0.1;port=3308';
$username = 'root';
$password = '123456';
$options = [
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
];
try {
    $dbh = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    echo PHP_EOL;die;
}

$sql = "SELECT 
`TABLE_NAME`,`COLUMN_NAME`,`COLUMN_TYPE`,`IS_NULLABLE`,`COLUMN_KEY`,`COLUMN_DEFAULT`,`EXTRA`,`COLUMN_COMMENT`
FROM `information_schema`.`COLUMNS` ";

if (count($tables)) {
    $sql .= ' WHERE ';
    foreach ($tables as $table) {
        $sql .= "`table_name` = '" . $table . "' or ";
    }
    $sql =  rtrim($sql,' or ');
}

$sth = $dbh->prepare($sql);
$sth->execute();
$result = $sth->fetchAll(PDO::FETCH_ASSOC);

$items = [];
foreach($result as $val){
    $tableName  = $val['TABLE_NAME'];
    unset($val['TABLE_NAME']);
    $items[$tableName][] = $val;
}

$content = '';
foreach($items as $table => $tableItems){
    $content .= str_replace("[table]", $table, '`[table]` ' . PHP_EOL. PHP_EOL);

    $c = '|名称|类型|空|键|默认|补充|说明|' . PHP_EOL;
    $c .= '|:--:|:--:|:--:|:--:|:--:|:--:|:--:|' . PHP_EOL;

    $template = "|COLUMN_NAME|COLUMN_TYPE|IS_NULLABLE|COLUMN_KEY|COLUMN_DEFAULT|EXTRA|COLUMN_COMMENT|" . PHP_EOL;
    $search = ['COLUMN_NAME', 'COLUMN_TYPE',  'IS_NULLABLE', 'COLUMN_KEY', 'COLUMN_DEFAULT', 'EXTRA', 'COLUMN_COMMENT'];
    foreach($tableItems as $item){
        $c .= str_replace($search, $item, $template);
    }
    $c .= PHP_EOL;
    $content.= $c;
}
file_put_contents('./data_dictionary.md', $content);