<?php
	session_start();

	if (isset($_GET['username'])) {
		$username = htmlspecialchars($_GET['username']);
	} else {
		$username = 'user'.rand(100,999);
	}

	if (isset($_GET['chatid'])) {
		$_COOKIE[session_name()] = $_GET['chatid'];
	}

	if (!isset($_SESSION['chat'])) {
		$_SESSION['chat'] = array();
		$_SESSION['chat'][date('Y-m-d H:i:s')] = 'chat started';
		session_commit();
	}

	function getParam($name) {
		return isset($_GET[$name]) ? $_GET[$name] : (isset($_POST[$name]) ? $_POST[$name] : "");
	}
	
	function issetParam($name) {
		return isset($_GET[$name]) || isset($_POST[$name]);
	}

	if (issetParam('action')) {
		$action = getParam('action');
		if ($action == 'newchat') {
			session_regenerate_id(true);
			
			$result = array(
				'result' => 'ok',
				'data' => array(),
			);
			$result['data']['session_id'] = session_id();
			$_SESSION['chat'] = array();
			$_SESSION['chat'][date('Y-m-d H:i:s')] = 'chat started';
			session_commit();
			header('Content-Type: application/json');
			echo json_encode($result);
			exit;
		} else if($action == 'addmsg') {
			
			$result = array(
				'result' => 'fail',
				'data' => array(),
			);
			if (issetParam('msg')) {
				$_SESSION['chat'][date('Y-m-d H:i:s')] = htmlspecialchars(getParam('msg'));
				session_commit();
				$result['result'] = 'ok';
			}
			header('Content-Type: application/json');
			echo json_encode($result);
			exit;
		} else if ($action == 'clear') {
			
			$result = array(
				'result' => 'ok',
				'data' => array(),
			);
	
			$_SESSION['chat'] = array();
			$_SESSION['chat'][date('Y-m-d H:i:s')] = 'chat clean';
			session_commit();
			header('Content-Type: application/json');
			echo json_encode($result);
			exit;
		} else if ($action == 'messages') {
			header('Content-Type: application/json');
			$result = array(
				'data' => $_SESSION['chat'],
				'result' => 'ok',
			);
			echo json_encode($result);
			exit;
		}
	}
?>
<html>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<title># crychat - your secret chat </title>
	<style type="text/css">
		body {
			color: purple;
			background-color: #191007;
			color: #3ADF00;
		}
	</style>
	
	<script type="text/javascript">
		function getParameterByName(name) {
			name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
			var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
				results = regex.exec(location.search);
			return results == null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
		}
		
		var session_id = "<?php echo session_id(); ?>";
		var session_name = "<?php echo session_name(); ?>";
		if (getParameterByName('chatid') != "") {
			session_id = getParameterByName('chatid');
			var date = new Date( new Date().getTime() + (7 * 24 * 60 * 60 * 1000) ); // cookie on week
			document.cookie = session_name + "=" + encodeURIComponent(session_id) + "; path=/; expires="+date.toUTCString();

		}

		function makeChatLink(session_id_) {
			if (session_id_ != session_id)
				session_id = session_id_;

			var path = location.pathname.split("/");
			path.splice(path.indexOf('index.php'), 1);
			var newURL = location.protocol + '//' + location.host + path.join("/") + "/" + "index.php?chatid=" + session_id_;
			document.getElementById('chatid').innerHTML = '<a class="chatid" href="' + newURL + '">chat link</a>';
			document.getElementById('qrcode_link').src= '?qrcode=' + encodeURIComponent(newURL);
		};

		function createUrlFromObj(obj) {
			var str = "";
			for (k in obj) {
				if (str.length > 0)
					str += "&";
				str += encodeURIComponent(k) + "=" + encodeURIComponent(obj[k]);
			}
			return str;
		}

		function send_request_post(params, callbackf) {
			var tmpXMLhttp = null;
			if (window.XMLHttpRequest) {
				// code for IE7+, Firefox, Chrome, Opera, Safari
				tmpXMLhttp = new XMLHttpRequest();
			};
			tmpXMLhttp.onreadystatechange = function() {
				if (tmpXMLhttp.readyState == 4 && tmpXMLhttp.status == 200) {
					if (tmpXMLhttp.responseText == "")
						alert("error");
					else {
						try {
							var obj = JSON.parse(tmpXMLhttp.responseText);
							callbackf(obj);
						} catch (e) {
							alert("Error in js " + e.name + " on request \nResponse:\n" + tmpXMLhttp.responseText);
						}
						tmpXMLhttp = null;
					}
				}
			}
			tmpXMLhttp.open("POST", 'index.php', true);
			tmpXMLhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
			tmpXMLhttp.send(createUrlFromObj(params));
		};
		
		function updateChat(runagain) {
			var params = {};
			params['action'] = 'messages';
			send_request_post(
				params,
				function(obj) {
					var el = document.getElementById('chat');
					if (obj.result == "fail") {
						el.innerHTML = "fail";
					} else {
						el.innerHTML = "";
						for (var k in obj.data) {
							if (obj.data.hasOwnProperty(k)) {
								el.innerHTML = "<p class='line'>[" + k + "] " + obj.data[k] + '</p>' + el.innerHTML;
							}
						}
						el.scrollTop = el.scrollHeight;
					}
					if (runagain == true)
						setTimeout(function() { updateChat(true); }, 2000);
				}
			);
		}
		
		function sendMessage() {
			var params = {};
			params['action'] = 'addmsg';
			params['msg'] = document.getElementById('username').value + ": "+ document.getElementById('message').value;
			if (params.msg.trim() == "") {
				return;
			}
			send_request_post(
				params,
				function(obj) {
					if (obj.result == "ok") {
						document.getElementById('message').value = "";
						updateChat(false);
					}
				}
			);
		}

		function createNewChat() {
			var params = {};
			params['action'] = 'newchat';
			send_request_post(
				params,
				function(obj) {
					if (obj.result == "ok") {
						makeChatLink(obj.data.session_id);
						updateChat(false);
					}
				}
			);
		}
		
		function clearChat() {
			var params = {};
			params['action'] = 'clear';
			send_request_post(
				params,
				function(obj) {
					if (obj.result == "ok") {
						updateChat(false);
					}
				}
			);
		}
	</script>
</head>

<body onload="updateChat(true); makeChatLink(session_id);">
	
	# CRYCHAT2
	<input type="button" onclick="createNewChat();" value="new chat"/>
	<input type="button" onclick="clearChat();" value="clear chat"/>
	<table>
		<tr>
			<td>Name</td>
			<td>Message</td>
			<td></td>
		</tr>
		<tr>
			<td><input type="text" size="10" id="username" value="<?php echo $username; ?>" /></td>
			<td><input type="text" class="textfield" id="message" value="" onkeyup="if (event.keyCode == 13) sendMessage();" /></td>
			<td><input type="button" onclick="sendMessage();" value="send"/></td>
			<td id="chatid"></td>
		</tr>
	</table>
						
    <!-- wrappage begin here -->
    <div id="wrappage">
        <div class="container">
            <div class="center-block">
                <div class="main">
                    <div class="page">
                        
						<div id="newchatpage" class="box left">
							<div id="chat" class="box-content">
							</div>
						</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
