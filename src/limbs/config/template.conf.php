<?php
return [
    'path' => 'template/', //模板路径
    'tag' => [
        "if %%" => '<?php if(\1):?>',
        "else" => '<?php else:?>',
        "else if %%" => '<?php elseif(\1):?>',
        "elseif %%" => '<?php elseif(\1):?>',
        "/if" => '<?php endif;?>',
        "foreach %%" => '<?php foreach(\1):?>',
        "/foreach" => '<?php endforeach;?>',
        "for %%" => '<?php for(\1):?>',
        "/for" => '<?php endfor;?>',
        "while %%" => '<?php while(\1):?>',
        "/while" => '<?php endwhile;?>',
        "continue" => '<?php continue;?>',
        "break" => '<?php break;?>',

        " $%% " => '<?php $\1;?>',
        "$%%" => '<?php echo $\1;?>',

        " %% = %% " => '<?php \1 = \2;?>', //$1 = $2
        "var %%=%%" => '<?php \1 = \2;?>', //$1 = $2

        " $%% = $%% " => '<?php $\1 = $\2;?>',
        "var $%%=$%%" => '<?php $\1 = $\2;?>',

        " $%%++ " => '<?php $\1++;?>', //X++
        "int $%%++" => '<?php $\1++;?>', //X++

        " $%%-- " => '<?php $\1--;?>', //X--
        "int $%%--" => '<?php $\1--;?>', //X--

        "die" => '<?php die;?>', //die
        "exit" => '<?php exit;?>', //exit

        "/*" => '<?php /** NaPHP: ' . PHP_EOL, //php代码内注释
        "*/" => '*/ ?>', //注释关闭

        "!" => PHP_EOL . '<!-- NaPHP: ', //Html代码内注释
        "/!" => ' -->' . PHP_EOL, //注释关闭

        "php" => '<?php ', //整段php代码
        "/php" => '?>', //代码半闭

        "js" => '<!-- NaPHP: Js Tag Start --> ' . PHP_EOL . '<script>', //整段js代码
        "/js" => '</script>' . PHP_EOL . '<!-- NaPHP: Js Tag End -->', //代码半闭

        "css" => '<!-- NaPHP: Style Tag Start -->' . PHP_EOL . '<style>', //整段CSS样式代码
        "/css" => '</style>' . PHP_EOL . '<!-- NaPHP: Style Tag End -->', //代码半闭

        "js %%" => '<!-- NaPHP: JS Include --><script src=\1></script>', //js标签
        "css %%" => '<!-- NaPHP: CSS Link --><link href=\1 rel="stylesheet">', //css标签
        "img %%" => '<!-- NaPHP: img Include--><img src=\1>', //图片标签
        "!%%!" => '<!-- NaPHP: $1 -->', //html单行注释

        "{%%(%%)}" => '<?php \1(\2);?>', //PHP函数标签 {var_dump($a)}等 本标签不能放在其它前面执行
        "%%(%%)" => '<?php echo \1(\2);?>', //PHP函数标签 {var_dump($a)}等 本标签不能放在其它前面执行

        "include_file %%" => '<?php include "\1";?>', //引入文件
        "get_file %%" => '<?php file_get_contents(\1);?>', //读取文件内容
    ],
];
