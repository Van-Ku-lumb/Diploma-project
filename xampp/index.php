<?php
    date_default_timezone_set('Europe/Sofia');

    $host='localhost';
    $user='root';
    $pass='';
    $db='measurementsdb';
    $GLOBALS['link']=mysqli_connect($host,$user,$pass);

    
    $GLOBALS['day']=$_GET['day']??date("d");
    $GLOBALS['month']=$_GET['month']??date("m");
    $GLOBALS['year']=$_GET['year']??date("Y");
    $GLOBALS['hour']=$_GET['hour']??-1;
    $GLOBALS['orderBy']=$_GET['orderBy']??'desc';
    
    mysqli_select_db($link,$db);

    $OrderByDesc='Order by year desc ';
    $OrderByDesc.=', month desc ';
    $OrderByDesc.=', day desc ';
    $OrderByDesc.=', hour desc ';
    $OrderByDesc.=', minute desc';


    function show_table_data($table)
    {
        global $link;
        global $day; global $month;global $year;global $hour;global $orderBy;
        $current=0;

        if($hour==-1)
        {
            $params='SELECT hour,minute,ROUND(AVG(temperature),2),ROUND(AVG(humidity),2),ROUND(AVG(pressure),2) FROM'." ${table} "
            ."WHERE day={$day} AND month={$month} AND year={$year} GROUP BY hour ORDER BY HOUR ";
        }
        else 
        {
            $params='SELECT hour,minute,temperature,humidity,pressure FROM'." ${table} "
            ."WHERE day={$day} AND month={$month} AND year={$year} AND hour={$hour} ORDER BY MINUTE ";
        }

        if(strtolower($orderBy)=='asc') $params.=' ASC ';
        else $params.= ' DESC ';
        $query=mysqli_query($link,$params);
        
        while($arr=mysqli_fetch_array($query))
        {
            $timeStamp='';
            if($arr[0]<10)$timeStamp.='0';
            $timeStamp.=$arr[0].':';
            if($hour==-1)
            {
                $timeStamp.='00';
            }
            else
            {
                if($arr[1]<10)$timeStamp.='0';
                $timeStamp.=$arr[1];
            }
            echo "<tr>";
            echo "<td>{$timeStamp}ч.</td>";
            echo "<td>{$arr['2']}°C</td>";
            echo "<td>{$arr['3']}%</td>";
            echo "<td>{$arr['4']} hPa</td>";
            echo"</tr>";
        }
    }
    
    $months=['нулий','януари','февруари','март','април','май','юни','юли','август','септември','октомври','ноември','декември'];
    $navigation='<form method="get">';
    $navigation.='<label for="day"> Ден: </label>';
    $navigation.='<select id="day" name="day">';
    for($i=1;$i<=31;$i++)
    {
        if($day==$i)
        {
            $navigation.="<option value='{$i}' selected='selected'>{$i}</option>";
        }
        else
        {
            $navigation.="<option value='{$i}'>{$i}</option>";
        }
    }
    $navigation.='</select>';

    $navigation.='<label for="month"> Месец: </label>';
    $navigation.='<select id="month" name="month">';
    for($i=1;$i<=12;$i++)
    {
        if($month==$i)
        {
            $navigation.="<option value='{$i}' selected='selected'>{$months[$i]}</option>";
        }
        else
        {
            $navigation.="<option value='{$i}'>{$months[$i]}</option>";
        }
    }
    $navigation.='</select>';

    $navigation.='<label for="year"> Година: </label>';
    $navigation.='<select id="year" name="year">';
    for($i=date("Y");$i>=2022;$i--)
    {
        if($year==$i)
        {
            $navigation.="<option value='{$i}' selected='selected'>{$i}</option>";
        }
        else
        {
            $navigation.="<option value='{$i}'>{$i}</option>";
        }
    }
    $navigation.='</select>';

    
    $navigation.='<label for="hour"> Час: </label>';
    $navigation.='<select id="hour" name="hour">';
    $navigation.="<option value=-1>--</option>";
    for($i=0;$i<24;$i++)
    {
        if($hour==$i)
        {
            $navigation.="<option value='{$i}' selected='selected'>{$i} </option>";
        }
        else
        {
            $navigation.="<option value='{$i}'>{$i}</option>";
        }
    }

    $navigation.='</select>';
    $navigation.='<select id="orderBy" name="orderBy">';
    if($orderBy=='desc')
    {
        $navigation.="<option value='desc' selected='selected'>▼</option>";
        $navigation.="<option value='asc'>▲</option>'";
    }
    else
    {
        $navigation.="<option value='desc' >▼</option>";
        $navigation.="<option value='asc' selected='selected'>▲</option>'";
    }
    $navigation.='</select>';
    $navigation.=' <input type="submit" value="Ок"></form>';

    $tempOut=mysqli_fetch_array(mysqli_query($link,'SELECT temperature FROM outside '
                                                    .$OrderByDesc.' LIMIT 1'))[0]??"??";
    $tempIn=mysqli_fetch_array(mysqli_query($link,'SELECT temperature FROM inside '
                                                    .$OrderByDesc.' LIMIT 1'))[0]??"??";
                                                    
?>

<!DOCTYPE html>
<html lang="bg">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Станция Пловдив</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <h1>
            Метеорологична станция
        </h1>

        <?=$navigation?>
        <div>
            <h3>
                Температура навън: <?php echo $tempOut?>°C
            </h3>
            <table>
                <tr>
                    <th>Час </th>
                    <th>Температура</th>
                    <th>Влажност</th>
                    <th>Атм. налягане</th>
                </tr>
                <?php show_table_data("outside"); ?>
            </table>
        </div>

        <div>
            <h3>
                Температура вътре: <?php echo $tempIn;?>°C
            </h3>
            <table>
                <tr>
                    <th>Час</th>
                    <th>Температура</th>
                    <th>Влажност</th>
                    <th>Атм. налягане</th>
                </tr>
                <?php show_table_data("inside"); ?>
            </table>
        </div>
    </body>
    
</html>