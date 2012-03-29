<footer>
    <p>&copy; &#1054;&#1054;&#1054; &laquo;&#1051;&#1072;&#1085;&#1052;&#1077;&#1076;&#1080;&#1072;&#1057;&#1077;&#1088;&#1074;&#1080;&#1089;&raquo;, 2006 &ndash; <?php echo date('Y')?></p>
    <?php if (isset($config['support_links']) && is_array($config['support_links']) && count($config['support_links'])): ?>
        <p>Техническая поддержка:
        <?php foreach ($config['support_links'] as $key=>$menuItem):?>
            <?php if ($key>0) echo " | ";?>
            <a href="<?php echo htmlspecialchars($menuItem['url']);?>" target="_blank"><?php echo $menuItem['text'];?></a>
        <?php endforeach;?>
        </p>
    <?php endif; ?>
    <p>
        <?php
        $result = mysql_query("SELECT count(*) FROM movies WHERE hidden=0");
        $field = mysql_fetch_row($result);
        $result2 = mysql_query("SELECT sum(`size`) FROM movies INNER JOIN movies_files USING(movie_id) INNER JOIN files USING(file_id) WHERE hidden=0");
        $field2 = mysql_fetch_row($result2);
        echo "Статистика: кол-во: ".$field[0]." (".round($field2[0]/1024/1024/1024)." ГБ, <a target='_blank' href='film_list.php'>полный список</a>) ";
        $result = mysql_query("SELECT count(*) FROM comments INNER JOIN movies_comments USING(comment_id) INNER JOIN movies USING(movie_id) WHERE hidden=0");
        $field = mysql_fetch_row($result);
        echo " | отзывов: ".$field[0]. " <a target='_blank' href='rss_comments.php'><img style='border:0;position:relative;top:2px;' src='templates/{$config['template']}/img/rss-orange.gif'></a>";
        $result = mysql_query("SELECT count(*) FROM movies_users_ratings INNER JOIN movies USING(movie_id) WHERE hidden=0");
        $field = mysql_fetch_row($result);
        echo " | оценок: ".$field[0];
        ?>
   </p>
</footer>
