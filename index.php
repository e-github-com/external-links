<?php
	#123
        # ������
	# http://remontokon-yes.ru/
	# 1. ������ ������� ������ (������ ������ ���� � ������ � �������)
        # 2. �������� ����� - �������� �� ��������
	# 3, ������� ������ - ��������� ���������� (����� � ������� - �������� �� ��������)
    # 4. ������� ������ �������� ��� ���������� ���������
    # 5. � alt ������ �������� ����� ������ ��� ��� ���� �������
    # 6. ������� ����������� �������������� ������������� ������������� ������ �����

	//� ������ ����� ��������� ���������
	session_start();

	$site;                         //����� �����
	$ar_result_check = array();    //������ ���������� ��������
	$html_result_check;            //html ��������� �������� �������

	$_SESSION['input_data'];       //���������� ��� �������� ������� ������ � ������
	$_SESSION['site'];             //���������� ��� �������� ������ ����� � ������
	$_SESSION['ar_result_check'];  //������ ��� �������� ����������� �������� � ������

    //���� ������������ ����� ���������
	if(isset($_POST['start'])) {
		//���� ������� ����
		if(!empty($_POST['pages-outer-links'])) {
			try {
				//��������� ������� ������
				$input_data = $_SESSION['input_data'] = $_POST['pages-outer-links'];

				//������� ������ ������
				//$input_data = preg_replace("%\n\s*\n%siU", "\n", $input_data);
				// ������� ������ � ������ �� ������
				//$input_data = preg_replace("%\n.*(http(s)?://\S{5,})[^\n]*%siU", "\n$1", $input_data);
				//echo $input_data;
				//exit; 

				//�������� ������ ���-��
				$arPagesOutLinks = explode("\r\n", $input_data);
				if(!is_array($arPagesOutLinks))
					throw new Exception("��� ������� �����");
                
                // ������� ������ � ������ �� ������
				//foreach ($arPagesOutLinks as $key => $p_link) {
					//if(preg_match('%http(s)?://\S{5,}%', $p_link, $match))
					//	$arPagesOutLinks[$key] = $match[0];

					//$p_link = preg_replace('%.*(http(s)?://\S{5,})(\s)?[^$]*$%siU', 'ok - $1', $p_link);
					//$p_link = preg_replace('%.*(http(s)?://\S{5,})%siU', '$1', $p_link);
					//$arPagesOutLinks[$key] = $p_link;

					// ������ ������ �� ������� �������� ���� ����
					//$arPagesOutLinks[$key] = trim($p_link);
				
					//echo $arPagesOutLinks[$key]."<br>";
				//}
				//exit;

				//������� ������ �� ������
				$arPagesOutLinks = array_unique($arPagesOutLinks);

				//������� ����� ������ ����� �� ������� ����
				if(preg_match('%https?://([^/$]*)(/.*)?$%siU', $arPagesOutLinks[0], $match))
				//if(preg_match('%https?://([^/$]*)/?$%siU', $arPagesOutLinks[0], $match))
					$site = $match[1];
					//echo $match[1];
				else
					throw new Exception("�� ���� ���������� ����");

				//��������� ���� �� ������� ������� ������ �� �������� � rel="nofollow"
				//$ar_result_check   - ���������� ������ ���������� ��������
				//$html_result_check - ���������� ���������� - ��������� �������� ������� � html
				foreach ($arPagesOutLinks as $key => $url_page) {
					//��������� ��������� $url_page
					$page = file_get_contents($url_page);
					if(!$page) { //���� �������� �� ����������� ��� �����, �� ��������� � ���������
						$ar_result_check[$url_page] = 'error_loading';
						continue;
					}
					if(empty($page)) { //���� �������� �����, �� ��������� � ���������
						$ar_result_check[$url_page] = 'clear_page';
						continue;
					}

					//������ ��������� �� html ������������
					$page = preg_replace('%<!--.*-->%siU', '', $page);

					//���� �� �������� � rel="nofollow" ������, ����� ������ c http �� ��� ����
					//if(preg_match_all('%<a[^>]*href\s*=\s*[\'"](https?://(?!'.$site.')[^>]*)[\'"][^>]*>%siU', $page, $matches, PREG_PATTERN_ORDER)) {
					if(preg_match_all('%<a[^>]*href\s*=\s*[\'"]\s*(https?://(?!'.$site.')[^\'"]*)[\'"][^>]*>%siU', $page, $matches, PREG_PATTERN_ORDER)) {
						// $matches[0][$i] - ���� html ��� ������
						// $matches[1][$i] - ������ ����� ������
						for($i=0; $i<count($matches[0]); $i++) {
							//���� ��� rel="nofollow" � ������
							if(!preg_match('%\s+rel\s*=\s*[\'"]?\s*nofollow\s*[\'"]?%siU',$matches[0][$i]))
								$link_color = 'red';
							else //���� ����
								$link_color = 'blue';

							//��������� � ������ ���������� ������ � � ����
							$ar_result_check[$url_page][$matches[1][$i]] = $link_color;
						}
					}
					else
						$ar_result_check[$url_page] = 'not_outlinks';
				}

				//������ ����������
				//echo '<!--'; print_r($ar_result_check); echo '-->';
				print_result_cheking();

				//���������� �������� ��������� � ������
				$_SESSION['ar_result_check'] = $ar_result_check;
				$_SESSION['site'] = $site;

			} catch (Exception $e) {
				echo $e->getMessage();
				exit;
			}
		}
	}

	//���� ������������ ����� - �������� ��
	if(isset($_POST['show_all'])) {
		$ar_result_check = $_SESSION['ar_result_check'];
		print_result_cheking();
	}

	//���� ������������ ����� - �������� �� ��������
	if(isset($_POST['show_no_rel'])) {
		$ar_result_check = $_SESSION['ar_result_check'];
		print_result_cheking('no_nofollow');
	}

	// ������� ������ ���������� ��������
	function print_result_cheking($mode) {
		global $ar_result_check;    //���������� ������ ���������� ��������
		global $html_result_check;  //���������� ���������� - ��������� �������� ������� � html

		foreach($ar_result_check as $url_page => $value) {
			$html_result_check.= '<table cellspacing="0" class="page-links">';

			if($ar_result_check[$url_page] == 'error_loading') {      // ���� ��������� ������ ��� ��������
				$html_result_check .= '<tr class="header">
								    <td class="link-page" colspan="2"><span style="font-weight:bold;">! �� ������� ���������</span> - <a target="_blank" href="'.$url_page.'">'.$url_page.'</a></td>
							    </tr>';
			} 
			else if ($ar_result_check[$url_page] == 'clear_page') { // ���� ����������� ��������� ��������� �����
				$html_result_check .= '<tr class="header">
								    <td class="link-page" colspan="2"><span style="font-weight:bold;">! �������� ����� - </span> - <a target="_blank" href="'.$url_page.'">'.$url_page.'</a></td>
							    </tr>';
			} 
			else {
				$html_result_check .= '<tr class="header">
							        <td class="link-page" colspan="2">�� �������� - <a target="_blank" href="'.$url_page.'">'.$url_page.'</a></td>
						         </tr>';

				if($ar_result_check[$url_page] == 'not_outlinks') {   // ���� �� ��������� ��������� ������� ������
					$html_result_check .= '<tr>
								       <td class="not-found">&nbsp;&nbsp;&nbsp;&nbsp;- �� ������� ������� ������</td>
							        </tr>';
				} 
				else { // ���� �� ������ � ������� ������ �������
					foreach ($ar_result_check[$url_page] as $out_link => $link_color) {
						if($mode == 'no_nofollow' and $link_color == 'blue') // ����� ��� ������ �������� ������
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

	//���� ���� �� �������� (���������)
	if(!empty($html_result_check)) {
		$site = $_SESSION['site']; 

		$html_output = 
			'<div id="response">
				<h2>���������� ��������</h2>
				<div id="site-url">���� - <a href="http://'.$site.'">'.$site.'</a></div>
				<div id="response-data">'.$html_result_check.'</div>
				<div id="note">
					<span class="red">&nbsp;</span> - �� �������� <span class="blue">&nbsp;</span> - ��������
				</div>
				<div id="show_buttons">
					<form name="form2" action="" method="POST">
						<input name="show_all" type="submit" value="�������� ���"/>
						<input name="show_no_rel" type="submit" value="�������� �� ��������"/>
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
				<title>�������� ������� ������</title>
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
					<h1 style="text-align:center;">�������� ������� ������</h1>
					<div id="request">
						<form name="form1" action="" method="POST">
							<label for="pages-outer-links"><h2>�������� ��� ������� ������� ������:</h2></label>
							<textarea id="pages-outer-links" name="pages-outer-links" rows="25">$tmp1</textarea>
							<input name="start" type="submit" value="���������"/>
						</form>
					</div>
					$html_output
				</div>
			</body>
		</html>
HTML;

	echo $html;

?>
