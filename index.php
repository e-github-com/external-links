<?php
        # çàäà÷è
	# http://remontokon-yes.ru/
	# 1. Ôèëüòð âõîäíûõ äàííûõ (óáðàòü ïóñòûå óðëû è ñòðîêè ñ òåêñòîì)
        # 2. Äîïèëèòü ðåæèì - Ïîêàçàòü íå çàêðûòûå
	# 3, Ñäåëàòü êíîïêó - Ïðîâåðèòü íåçàêðûòûå (ðÿäîì ñ êíîïêîé - Ïîêàçàòü íå çàêðûòûå)
    # 4. Ñäåëàòü êíîïêó îáíîâèòü äëÿ êîíêðåòíîé ñòðàíè÷êè
    # 5. Â alt ññûëêè äîïèñàòü íîìåð ñòðîêè ãäå îíà áûëà íàéäåíà
    # 6. Ñäåëàòü âîçìîæíîñòü ðåäàêòèðîâàíèÿ àâòîìàòè÷åñêè îïðåäåëåííîãî àäðåñà ñàéòà

	//â ñåññèþ áóäåì ñîõðàíÿòü ðåçóëüòàò
	session_start();

	$site;                         //àäðåñ ñàéòà
	$ar_result_check = array();    //ìàññèâ ðåçóëüòàòà ïðîâåðêè
	$html_result_check;            //html ðåçóëüòàò ïðîâåðêè ñòðàíèö

	$_SESSION['input_data'];       //ïåðåìåííàÿ äëÿ õðàíåíèÿ âõîäíûõ äàííûõ â ñåññèè
	$_SESSION['site'];             //ïåðåìåííàÿ äëÿ õðàíåíèÿ àäðåñà ñàéòà â ñåññèè
	$_SESSION['ar_result_check'];  //ìàññèâ äëÿ õðàíåíèÿ ðåçóëüòàòîâ ïðîâåðêè â ñåññèè

    //åñëè ïîëüçîâàòåëü íàæàë îòïðàâèòü
	if(isset($_POST['start'])) {
		//åñëè ââåäåíû óðëû
		if(!empty($_POST['pages-outer-links'])) {
			try {
				//ñîõðàíÿåì âõîäíûå äàííûå
				$input_data = $_SESSION['input_data'] = $_POST['pages-outer-links'];

				//óäàëÿåì ïóñòûå ñòðîêè
				//$input_data = preg_replace("%\n\s*\n%siU", "\n", $input_data);
				// î÷èùàåì ñòðîêè ñ óðëàìè îò òåêñòà
				//$input_data = preg_replace("%\n.*(http(s)?://\S{5,})[^\n]*%siU", "\n$1", $input_data);
				//echo $input_data;
				//exit; 

				//ïîëó÷àåì ìàññèâ óðë-îâ
				$arPagesOutLinks = explode("\r\n", $input_data);
				if(!is_array($arPagesOutLinks))
					throw new Exception("Íåò ìàññèâà óðëîâ");
                
                // î÷èùàåì ñòðîêè ñ óðëàìè îò òåêñòà
				//foreach ($arPagesOutLinks as $key => $p_link) {
					//if(preg_match('%http(s)?://\S{5,}%', $p_link, $match))
					//	$arPagesOutLinks[$key] = $match[0];

					//$p_link = preg_replace('%.*(http(s)?://\S{5,})(\s)?[^$]*$%siU', 'ok - $1', $p_link);
					//$p_link = preg_replace('%.*(http(s)?://\S{5,})%siU', '$1', $p_link);
					//$arPagesOutLinks[$key] = $p_link;

					// ÷èñòèì àäðåñà îò áîêîâûõ ïðîáåëîâ åñëè åñòü
					//$arPagesOutLinks[$key] = trim($p_link);
				
					//echo $arPagesOutLinks[$key]."<br>";
				//}
				//exit;

				//î÷èùàåì ìàññèâ îò äóáëåé
				$arPagesOutLinks = array_unique($arPagesOutLinks);

				//ïîëó÷åì àäðåñ íàøåãî ñàéòà ïî ïåðâîìó óðëó
				if(preg_match('%https?://([^/$]*)(/.*)?$%siU', $arPagesOutLinks[0], $match))
				//if(preg_match('%https?://([^/$]*)/?$%siU', $arPagesOutLinks[0], $match))
					$site = $match[1];
					//echo $match[1];
				else
					throw new Exception("Íå ìîãó îïðåäåëèòü ñàéò");

				//ïðîâåðÿåì óðëû íà íàëè÷èå âíåøíèõ ññûëîê íå çàêðûòûõ â rel="nofollow"
				//$ar_result_check   - ãëîáàëüíûé ìàññèâ ðåçóëüòàòà ïðîâåðêè
				//$html_result_check - ãëîáàëüíàÿ ïåðåìåííàÿ - ðåçóëüòàò ïðîâåðêè ñòðàíèö â html
				foreach ($arPagesOutLinks as $key => $url_page) {
					//çàãðóæàåì ñòðàíè÷êó $url_page
					$page = file_get_contents($url_page);
					if(!$page) { //åñëè ñòðàíèêà íå çàãðóçèëàñü èëè ïóñòà, òî ïåðåõîäèì ê ñëåäóþùåé
						$ar_result_check[$url_page] = 'error_loading';
						continue;
					}
					if(empty($page)) { //åñëè ñòðàíèêà ïóñòà, òî ïåðåõîäèì ê ñëåäóþùåé
						$ar_result_check[$url_page] = 'clear_page';
						continue;
					}

					//÷èñòèì ñòðàíè÷êó îò html êîììåíòàðèåâ
					$page = preg_replace('%<!--.*-->%siU', '', $page);

					//èùåì íå çàêðûòûå â rel="nofollow" ññûëêè, êðîìå ññûëîê c http íà ñàì ñàéò
					//if(preg_match_all('%<a[^>]*href\s*=\s*[\'"](https?://(?!'.$site.')[^>]*)[\'"][^>]*>%siU', $page, $matches, PREG_PATTERN_ORDER)) {
					if(preg_match_all('%<a[^>]*href\s*=\s*[\'"]\s*(https?://(?!'.$site.')[^\'"]*)[\'"][^>]*>%siU', $page, $matches, PREG_PATTERN_ORDER)) {
						// $matches[0][$i] - âåñü html êîä ññûëêè
						// $matches[1][$i] - òîëüêî àíêîð ññûëêè
						for($i=0; $i<count($matches[0]); $i++) {
							//åñëè íåò rel="nofollow" ó ññûëêè
							if(!preg_match('%\s+rel\s*=\s*[\'"]?\s*nofollow\s*[\'"]?%siU',$matches[0][$i]))
								$link_color = 'red';
							else //åñëè åñòü
								$link_color = 'blue';

							//äîáàâëÿåì â ìàññèâ ðåçóëüòàòà ññûëêó è å¸ öâåò
							$ar_result_check[$url_page][$matches[1][$i]] = $link_color;
						}
					}
					else
						$ar_result_check[$url_page] = 'not_outlinks';
				}

				//ïå÷àòü ðåçóëüòàòà
				//echo '<!--'; print_r($ar_result_check); echo '-->';
				print_result_cheking();

				//ðåçóëüòàòû ïðîâåðêè ñîõðàíÿåì â ñåññèþ
				$_SESSION['ar_result_check'] = $ar_result_check;
				$_SESSION['site'] = $site;

			} catch (Exception $e) {
				echo $e->getMessage();
				exit;
			}
		}
	}

	//åñëè ïîëüçîâàòåëü íàæàë - ïîêàçàòü âñ¸
	if(isset($_POST['show_all'])) {
		$ar_result_check = $_SESSION['ar_result_check'];
		print_result_cheking();
	}

	//åñëè ïîëüçîâàòåëü íàæàë - ïîêàçàòü íå çàêðûòûå
	if(isset($_POST['show_no_rel'])) {
		$ar_result_check = $_SESSION['ar_result_check'];
		print_result_cheking('no_nofollow');
	}

	// ôóíêöèÿ ïå÷àòè ðåçóëüòàòà ïðîâåðêè
	function print_result_cheking($mode) {
		global $ar_result_check;    //ãëîáàëüíûé ìàññèâ ðåçóëüòàòà ïðîâåðêè
		global $html_result_check;  //ãëîáàëüíàÿ ïåðåìåííàÿ - ðåçóëüòàò ïðîâåðêè ñòðàíèö â html

		foreach($ar_result_check as $url_page => $value) {
			$html_result_check.= '<table cellspacing="0" class="page-links">';

			if($ar_result_check[$url_page] == 'error_loading') {      // åñëè ïðîèçîøëà îøèáêà ïðè çàãðóçêå
				$html_result_check .= '<tr class="header">
								    <td class="link-page" colspan="2"><span style="font-weight:bold;">! Íå óäàëîñü çàãðóçèòü</span> - <a target="_blank" href="'.$url_page.'">'.$url_page.'</a></td>
							    </tr>';
			} 
			else if ($ar_result_check[$url_page] == 'clear_page') { // åñëè çàãðóæåííàÿ ñòðàíè÷êà îêàçàëàñü ïóñòà
				$html_result_check .= '<tr class="header">
								    <td class="link-page" colspan="2"><span style="font-weight:bold;">! Ñòðàíèöà ïóñòà - </span> - <a target="_blank" href="'.$url_page.'">'.$url_page.'</a></td>
							    </tr>';
			} 
			else {
				$html_result_check .= '<tr class="header">
							        <td class="link-page" colspan="2">Íà ñòðàíèöå - <a target="_blank" href="'.$url_page.'">'.$url_page.'</a></td>
						         </tr>';

				if($ar_result_check[$url_page] == 'not_outlinks') {   // åñëè íà ñòðàíè÷êå íåíàéäåíî âíåøíèõ ññûëîê
					$html_result_check .= '<tr>
								       <td class="not-found">&nbsp;&nbsp;&nbsp;&nbsp;- Íå íàéäåíî âíåøíèõ ññûëîê</td>
							        </tr>';
				} 
				else { // åñëè âñ¸ õîðîøî è âíåøíèå ññûëêè íàéäåíû
					foreach ($ar_result_check[$url_page] as $out_link => $link_color) {
						if($mode == 'no_nofollow' and $link_color == 'blue') // ðåæèì áåç ïîêàçà çàêðûòûõ ññûëîê
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

	//åñëè åñòü ÷å ïîêàçàòü (ðåçóëüòàò)
	if(!empty($html_result_check)) {
		$site = $_SESSION['site']; 

		$html_output = 
			'<div id="response">
				<h2>Ðåçóëüòàòû ïðîâåðêè</h2>
				<div id="site-url">Ñàéò - <a href="http://'.$site.'">'.$site.'</a></div>
				<div id="response-data">'.$html_result_check.'</div>
				<div id="note">
					<span class="red">&nbsp;</span> - íå çàêðûòûå <span class="blue">&nbsp;</span> - çàêðûòûå
				</div>
				<div id="show_buttons">
					<form name="form2" action="" method="POST">
						<input name="show_all" type="submit" value="Ïîêàçàòü âñå"/>
						<input name="show_no_rel" type="submit" value="Ïîêàçàòü íå çàêðûòûå"/>
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
				<title>Ïðîâåðêà âíåøíèõ ññûëîê</title>
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
					<h1 style="text-align:center;">Ïðîâåðêà âíåøíèõ ññûëîê</h1>
					<div id="request">
						<form name="form1" action="" method="POST">
							<label for="pages-outer-links"><h2>Ñòðàíèöû ãäå íàéäåíû âíåøíèå ññûëêè:</h2></label>
							<textarea id="pages-outer-links" name="pages-outer-links" rows="25">$tmp1</textarea>
							<input name="start" type="submit" value="Ïðîâåðèòü"/>
						</form>
					</div>
					$html_output
				</div>
			</body>
		</html>
HTML;

	echo $html;

?>
