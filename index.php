<?php
	# задачи
	# 1. Фильтр входных данных (убрать пустые урлы и строки с текстом)
    # 2. Допилить режим - Показать не закрытые
	# 3, Сделать кнопку - Проверить незакрытые (рядом с кнопкой - Показать не закрытые)
    # 4. Сделать кнопку обновить для конкретной странички
    # 5. В alt ссылки дописать номер строки где она была найдена
    # 6. Сделать возможность редактирования автоматически определенного адреса сайта

	//в сессию будем сохранять результат
	session_start();

	$site;                         //адрес сайта
	$ar_result_check = array();    //массив результата проверки
	$html_result_check;            //html результат проверки страниц

	$_SESSION['input_data'];       //переменная для хранения входных данных в сессии
	$_SESSION['site'];             //переменная для хранения адреса сайта в сессии
	$_SESSION['ar_result_check'];  //массив для хранения результатов проверки в сессии

    //если пользователь нажал отправить
	if(isset($_POST['start'])) {
		//если введены урлы
		if(!empty($_POST['pages-outer-links'])) {
			try {
				//сохраняем входные данные
				$input_data = $_SESSION['input_data'] = $_POST['pages-outer-links'];

				//удаляем пустые строки
				//$input_data = preg_replace("%\n\s*\n%siU", "\n", $input_data);
				// очищаем строки с урлами от текста
				//$input_data = preg_replace("%\n.*(http(s)?://\S{5,})[^\n]*%siU", "\n$1", $input_data);
				//echo $input_data;
				//exit; 

				//получаем массив урл-ов
				$arPagesOutLinks = explode("\r\n", $input_data);
				if(!is_array($arPagesOutLinks))
					throw new Exception("Нет массива урлов");
                
                // очищаем строки с урлами от текста
				//foreach ($arPagesOutLinks as $key => $p_link) {
					//if(preg_match('%http(s)?://\S{5,}%', $p_link, $match))
					//	$arPagesOutLinks[$key] = $match[0];

					//$p_link = preg_replace('%.*(http(s)?://\S{5,})(\s)?[^$]*$%siU', 'ok - $1', $p_link);
					//$p_link = preg_replace('%.*(http(s)?://\S{5,})%siU', '$1', $p_link);
					//$arPagesOutLinks[$key] = $p_link;

					// чистим адреса от боковых пробелов если есть
					//$arPagesOutLinks[$key] = trim($p_link);
				
					//echo $arPagesOutLinks[$key]."<br>";
				//}
				//exit;

				//очищаем массив от дублей
				$arPagesOutLinks = array_unique($arPagesOutLinks);

				//получем адрес нашего сайта по первому урлу
				if(preg_match('%https?://([^/$]*)(/.*)?$%siU', $arPagesOutLinks[0], $match))
				//if(preg_match('%https?://([^/$]*)/?$%siU', $arPagesOutLinks[0], $match))
					$site = $match[1];
					//echo $match[1];
				else
					throw new Exception("Не могу определить сайт");

				//проверяем урлы на наличие внешних ссылок не закрытых в rel="nofollow"
				//$ar_result_check   - глобальный массив результата проверки
				//$html_result_check - глобальная переменная - результат проверки страниц в html
				foreach ($arPagesOutLinks as $key => $url_page) {
					//загружаем страничку $url_page
					$page = file_get_contents($url_page);
					if(!$page) { //если страника не загрузилась или пуста, то переходим к следующей
						$ar_result_check[$url_page] = 'error_loading';
						continue;
					}
					if(empty($page)) { //если страника пуста, то переходим к следующей
						$ar_result_check[$url_page] = 'clear_page';
						continue;
					}

					//чистим страничку от html комментариев
					$page = preg_replace('%<!--.*-->%siU', '', $page);

					//ищем не закрытые в rel="nofollow" ссылки, кроме ссылок c http на сам сайт
					//if(preg_match_all('%<a[^>]*href\s*=\s*[\'"](https?://(?!'.$site.')[^>]*)[\'"][^>]*>%siU', $page, $matches, PREG_PATTERN_ORDER)) {
					if(preg_match_all('%<a[^>]*href\s*=\s*[\'"]\s*(https?://(?!'.$site.')[^\'"]*)[\'"][^>]*>%siU', $page, $matches, PREG_PATTERN_ORDER)) {
						// $matches[0][$i] - весь html код ссылки
						// $matches[1][$i] - только анкор ссылки
						for($i=0; $i<count($matches[0]); $i++) {
							//если нет rel="nofollow" у ссылки
							if(!preg_match('%\s+rel\s*=\s*[\'"]?\s*nofollow\s*[\'"]?%siU',$matches[0][$i]))
								$link_color = 'red';
							else //если есть
								$link_color = 'blue';

							//добавляем в массив результата ссылку и её цвет
							$ar_result_check[$url_page][$matches[1][$i]] = $link_color;
						}
					}
					else
						$ar_result_check[$url_page] = 'not_outlinks';
				}

				//печать результата
				//echo '<!--'; print_r($ar_result_check); echo '-->';
				print_result_cheking();

				//результаты проверки сохраняем в сессию
				$_SESSION['ar_result_check'] = $ar_result_check;
				$_SESSION['site'] = $site;

			} catch (Exception $e) {
				echo $e->getMessage();
				exit;
			}
		}
	}

	//если пользователь нажал - показать всё
	if(isset($_POST['show_all'])) {
		$ar_result_check = $_SESSION['ar_result_check'];
		print_result_cheking();
	}

	//если пользователь нажал - показать не закрытые
	if(isset($_POST['show_no_rel'])) {
		$ar_result_check = $_SESSION['ar_result_check'];
		print_result_cheking('no_nofollow');
	}

	// функция печати результата проверки
	function print_result_cheking($mode) {
		global $ar_result_check;    //глобальный массив результата проверки
		global $html_result_check;  //глобальная переменная - результат проверки страниц в html

		foreach($ar_result_check as $url_page => $value) {
			$html_result_check.= '<table cellspacing="0" class="page-links">';

			if($ar_result_check[$url_page] == 'error_loading') {      // если произошла ошибка при загрузке
				$html_result_check .= '<tr class="header">
								    <td class="link-page" colspan="2"><span style="font-weight:bold;">! Не удалось загрузить</span> - <a target="_blank" href="'.$url_page.'">'.$url_page.'</a></td>
							    </tr>';
			} 
			else if ($ar_result_check[$url_page] == 'clear_page') { // если загруженная страничка оказалась пуста
				$html_result_check .= '<tr class="header">
								    <td class="link-page" colspan="2"><span style="font-weight:bold;">! Страница пуста - </span> - <a target="_blank" href="'.$url_page.'">'.$url_page.'</a></td>
							    </tr>';
			} 
			else {
				$html_result_check .= '<tr class="header">
							        <td class="link-page" colspan="2">На странице - <a target="_blank" href="'.$url_page.'">'.$url_page.'</a></td>
						         </tr>';

				if($ar_result_check[$url_page] == 'not_outlinks') {   // если на страничке ненайдено внешних ссылок
					$html_result_check .= '<tr>
								       <td class="not-found">&nbsp;&nbsp;&nbsp;&nbsp;- Не найдено внешних ссылок</td>
							        </tr>';
				} 
				else { // если всё хорошо и внешние ссылки найдены
					foreach ($ar_result_check[$url_page] as $out_link => $link_color) {
						if($mode == 'no_nofollow' and $link_color == 'blue') // режим без показа закрытых ссылок
							continue;

						$html_result_check .= '<tr>
								           <td class="out-link">&nbsp;&nbsp;&nbsp;&nbsp;<a class="'.$link_color.'" target="_blank" href="'.$out_link.'">'.$out_link.'</a></td>
							            </tr>';
					}
				}
			}

			$html_result_check .= "</table>";
		}
	}

	//если есть че показать (результат)
	if(!empty($html_result_check)) {
		$site = $_SESSION['site']; 

		$html_output = 
			'<div id="response">
				<h2>Результаты проверки</h2>
				<div id="site-url">Сайт - <a href="http://'.$site.'">'.$site.'</a></div>
				<div id="response-data">'.$html_result_check.'</div>
				<div id="note">
					<span class="red">&nbsp;</span> - не закрытые <span class="blue">&nbsp;</span> - закрытые
				</div>
				<div id="show_buttons">
					<form name="form2" action="" method="POST">
						<input name="show_all" type="submit" value="Показать все"/>
						<input name="show_no_rel" type="submit" value="Показать не закрытые"/>
					</form>
				</div>
			</div>';
	}

	/*------------------------------------------------- HTML ----------------------------------------------------*/
	/*-----------------------------------------------------------------------------------------------------------*/
	$tmp1 = $_SESSION['input_data'];

	$html = <<<HTML
		<html>
			<head>
				<title>Проверка внешних ссылок</title>
				<meta http-equiv="content-type" content="text/html; charset=windows-1251">
				<style>
					#page {
						width: 100%;
						}

						#page #request #pages-outer-links {
							width: 100%;
							}

						#page #response {
							margin-bottom: 80px;
							}
						#page #response #response-data {
							width: 100%;
							border: 1px solid #000;
							min-height: 25em;
							}
						#page #site-url {
							margin-top: -0.5em;
							margin-bottom: 1em;
							}
						#page #note {
							margin: 0.5em 0 0.7em 0.21em;
							}
						#page #note .red {
							background-color: red;
							}
						#page #note .blue {
							background-color: blue;
							margin-left: 10px;
							}

					a.blue {
						color: blue;
						}
					a.red {
						color: red;
						}

					table.page-links {
						margin-bottom: 20px;
						font-size: 1em;
						}
					/*table.page-links td.header a {
						color: #000;
						}*/
				</style>
			</head>
			<body>
				<div id="page">
					<h1 style="text-align:center;">Проверка внешних ссылок</h1>
					<div id="request">
						<form name="form1" action="" method="POST">
							<label for="pages-outer-links"><h2>Страницы где найдены внешние ссылки:</h2></label>
							<textarea id="pages-outer-links" name="pages-outer-links" rows="25">$tmp1</textarea>
							<input name="start" type="submit" value="Проверить"/>
						</form>
					</div>
					$html_output
				</div>
			</body>
		</html>
HTML;

	echo $html;

?>
