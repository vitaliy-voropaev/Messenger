<?php
class Messenger {
	
	public $modx;

	function __construct(modX &$modx, array $config = array()) {
		$this->modx =& $modx;

		$corePath = $this->modx->getOption('messenger_core_path', $config, $this->modx->getOption('core_path') . 'components/messenger/');
		$assetsUrl = $this->modx->getOption('messenger_assets_url', $config, $this->modx->getOption('assets_url') . 'components/messenger/');
		$push = $this->modx->getOption('messenger_use_push');
		$connectorUrl = $assetsUrl . 'connector.php';
		$actionUrl = $assetsUrl . 'action.php';

		$dialog = ($_GET['dialog'] ? $_GET['dialog'] : $_POST['dialog']);

		$this->config = array_merge(array(
			'assetsUrl' => $assetsUrl,
			'cssUrl' => $assetsUrl . 'css/',
			'jsUrl' => $assetsUrl . 'js/',
			'connectorUrl' => $connectorUrl,
			'actionUrl' => $actionUrl,
			'corePath' => $corePath,
			'modelPath' => $corePath . 'model/',
			'chunksPath' => $corePath . 'elements/chunks/',
			'templatesPath' => $corePath . 'elements/templates/',
			'chunkSuffix' => '.chunk.tpl',
			'snippetsPath' => $corePath . 'elements/snippets/',
			'processorsPath' => $corePath . 'processors/',
			'push' => $push,
			'dialog' => ($dialog ?: 0),
			'user' => $this->modx->user->id
		), $config);

		if ($this->config['push']) {
			$this->config['push_key'] = $this->modx->getOption('messenger_push_key');
			$this->config['push_id'] = $this->modx->getOption('messenger_push_id');
		}

		$this->table_prefix = $this->modx->config['table_prefix'] ? $this->modx->config['table_prefix'] : 'modx_';

		$this->modx->addPackage('messenger', $this->config['modelPath']);
		$this->modx->lexicon->load('messenger:default');
	}


	public function initialize($ctx = 'web', $scriptProperties = array()) {
		
		$this->config = array_merge($this->config, $scriptProperties);
		$this->config['ctx'] = $ctx;

		if (empty($this->initialized[$ctx])) {

			$config_js = array(
				'ctx' => $ctx,
				'jsUrl' => $this->config['jsUrl'] . 'web/',
				'cssUrl' => $this->config['cssUrl'] . 'web/',
				'actionUrl' => $this->config['actionUrl'],
				'dialog' => ($_GET['dialog'] ? $_GET['dialog'] : 0),
			);

			if($this->config['push']){
				$config_js['push_id'] = $this->modx->getOption('messenger_push_id');
				$config_js['user_id'] = $this->modx->user->id;
				$config_js['user_name'] = $this->modx->user->username;
				$config_js['user_hash'] = $this->push_('auth', '');
				$this->modx->regClientScript($this->config['jsUrl'].'web/lib/CometServerApi.js');
			}
			$this->modx->regClientStartupScript('<script type="text/javascript">MessengerConfig=' . $this->modx->toJSON($config_js) . '</script>', true);
			$this->modx->regClientCSS($this->config['cssUrl'] . 'web/messenger.css');
			$this->modx->regClientScript($this->config['jsUrl'] . 'web/messenger.js');
			$this->initialized[$ctx] = true;
		}
		
		return true;
	}

	// загрузка сообщений
	public function messageLoad($message_id, $type, $json) {
		global $modx;

		$output = '';
		$messages = '';
		$dialog = $modx->getObject('MessengerDialog', $this->config['dialog']);

		// аватарки пользователей
		$sql = "
		SELECT u.id, u.username, p.photo
			FROM `{$this->table_prefix}users` AS u
			JOIN (SELECT internalKey, photo FROM `{$this->table_prefix}user_attributes` GROUP BY id) p ON p.internalKey = u.id
	    WHERE u.id IN ({$dialog->users})
		";
		
		if ($query = $modx->query($sql)) {
			
			$user_info_ = $query->fetchAll(PDO::FETCH_ASSOC);
			$user_info = array();
			
			foreach ($user_info_ as $key => $value) {
				$user_info[$value['id']]['photo'] = $value['photo'];
				$user_info[$value['id']]['username'] = $value['username'];
			}
		}


		if ($type == 'first') $where = "AND `id` < {$message_id}";
		if ($type == 'last') $where = "AND `id` > {$message_id}";

		$id = $modx->user->id;
		$sql = "
			SELECT m.*, EXISTS (SELECT * FROM `{$this->table_prefix}messenger_messages_unread` WHERE `message_id` = m.id AND `to` = {$modx->user->id}) as unread
			FROM `{$this->table_prefix}messenger_messages` as m
			WHERE m.dialog = {$dialog->id} {$where} ORDER BY m.id DESC LIMIT 0,10
		";

		if ($query = $modx->query($sql)) {
			
			$messages_ = $query->fetchAll(PDO::FETCH_ASSOC);
			$sorted = array_reverse($messages_);

			foreach ($sorted as $message) {
				$message['type'] = '';
				if ($message['from'] == $modx->user->id) $message['type'] = 'out';
				$message['photo'] = $user_info[$message['from']]['photo'];
				$message['author'] = $user_info[$message['from']]['username'];
				$messages .= $modx->getChunk('tpl.Messenger.message', $message);
			}
		}

		if ($json) {
			$output = array(
				'success' => true,
				'dialog' => $messages
			);
		} else {
			$output = $messages;
		}

		return $output;
	}

	public function message_load_last($dialog_id) {
		global $modx;

		$output = '';
		$sql = "SELECT * FROM `{$this->table_prefix}messenger_messages` WHERE `dialog` = {$dialog_id} ORDER BY `id` DESC LIMIT 1";
		
		if($query = $modx->query($sql)) {
		}
	}

	// отправка сообщения
	public function messageSend($id, $message_text) {
		global $modx;
		
		$output = '';

		$id = isset($id) ? $id : $this->config['dialog'];
		$message = isset($message) ? $message : $_POST['message'];

		$dialog = $modx->getObject('MessengerDialog', $id);
		$message = $modx->newObject('MessengerMessage',array(
		   'from' => $modx->user->id,
		   'dialog' => $dialog->id,
		   'timestamp' => time(),
		   'message' => strip_tags($message_text)
		));

		if ($message->save()) {
			$this->userOnline($modx->user->id);

			// записыват смс как новое всем пользователям в диалоге
			$this->messageUnread($message->id, $message->from, $message->dialog);
			$output = array('success' => true, 'data' => $message->toArray());
			
			if ($this->config['push']) {
				$user = $modx->getUser();
				$fields = $message->toArray();
				$fields['from_name'] = $modx->user->username;
				$this->push_('message', $fields);
			}
		}

		return $output;
	}

	// запись новых сообщений каждому пользователю
	public function messageUnread($message_id, $from_id, $dialog_id){
		global $modx;

		$output = '';
		$dialog = $modx->getObject('MessengerDialog', $dialog_id);
		$users = explode(',', $dialog->users);

		foreach ($users as $key => $value) {
			if ($modx->user->id == $value) continue;

			$notification = $modx->newObject('MessengerMessageUnread', array(
				'message_id' => $message_id,
				'dialog' => $dialog_id,
				'from' => $from_id,
				'to' => $value
			));

			$notification->save();
		}

		return $output;
	}

	// удаление прочитанных сообщений из таблицы
	public function messageRead($messages, $dialog){
		global $modx;

		$output = '';
		
		if (is_array($messages)) {
			$ids = implode(',', $messages);
			$sql = "DELETE FROM `{$this->table_prefix}messenger_messages_unread` WHERE `message_id` IN({$ids}) AND `to` = {$modx->user->id}";
			
			if ($query = $modx->query($sql)) {
				$q = $query->fetchAll(PDO::FETCH_ASSOC);
				$output = array(
					'success' => true,
					'messages' => $ids,
					'dialog' => $dialog,
					'count' => count($messages)
				);
			}
		}

		return $output;
	}

	// проверка новых сообщений
	public function checkUnread($dialog_id){
		global $modx;

		$output = 0;

		if ($dialog_id) {
			$where = "AND `dialog` = ".$dialog_id;
		}

		$sql = "SELECT COUNT(*) as `count` FROM `{$this->table_prefix}messenger_messages_unread` WHERE `to` = {$modx->user->id} {$where}";
		
		if ($query = $modx->query($sql)) {
			$q = $query->fetchAll(PDO::FETCH_ASSOC);
			$output = $q[0]['count'];
		}

		return $output;
	}

	// загрузка диалога
	public function dialogues_load($json, $dialog_id) {
		global $modx;
		$output = '';

		if($dialog_id) $where = "AND u.id = {$dialog_id}";
		$sql = "

		SELECT u.id, u.name, u.users, m.timestamp, m2.message
		FROM `{$this->table_prefix}messenger_dialogues` AS u
		JOIN
			(SELECT dialog, MAX(timestamp) AS timestamp
		     FROM `{$this->table_prefix}messenger_messages` GROUP BY dialog) m ON m.dialog = u.id
		JOIN     (SELECT message, `timestamp`
		     FROM `{$this->table_prefix}messenger_messages`) m2 ON m2.timestamp = m.timestamp

		WHERE u.users LIKE '%{$modx->user->id}%' {$where}
		ORDER BY m.timestamp DESC

		";

		// return id, name, users, timestamp
		if ($query = $modx->query($sql)) {
			$dialogues = $query->fetchAll(PDO::FETCH_ASSOC);
			if (!$dialogues) 
				return ($json ? $this->error('Список диалогов пуст') : "<span class='dialogues-empty'>Список диалогов пуст</span>");
			
			$dialogues = $this->dialogues_parser($dialogues, $modx->user->id);

			foreach ($dialogues as $dialog) {
				$output .= $modx->getChunk('tpl.Messenger.dialog.row', $dialog);
			}
		}

		if($json) $output = array('success' => true, 'html' => $output);
		return $output;

	}

	// загрузка диалога (инфо + сообщения)
	public function dialog_load($id, $json) {
		global $modx;

		$output = '';
		
		if (empty($id)) return ($json ? $this->error('Диалог не выбран') : "<span class='dialog-empty'>Диалог не выбран</span>");
		
		$dialog = $modx->getObject('MessengerDialog', $id);

		if ($dialog) {
			$dialog_ = $this->dialogues_parser(array($dialog->toArray()), $modx->user->id);
			$settings = '';
			
			if ($dialog_[0]['type'] == 'group') {
				$dialog_info = "{$dialog_[0]['users']} пользователей";
				$settings = $modx->getChunk('tpl.messenger.dialog.settings', array());
			} else {
				$status = $this->checkUserOnline($dialog_[0]['user'], true);
				if ($status == 'online') $status = '<span class="user-online">Online</span>';
				$dialog_info = $status;
			}

			$output_ = $modx->getChunk('tpl.Messenger.dialog', array(
				'dialog_name' => $dialog_[0]['name'],
				'dialog_info' => $dialog_info,
				'settings' => $settings,
				'messages' => $this->messageLoad(false, false, false)
			));

			$output = ($json ? $this->success('', $output_, '', '') : $output_);

		} else {

			$output = ($json ? $this->error('Диалог не найден') : 'Диалог не найден');
		}


		return $output;
	}

	// создание диалога
	public function dialogNew() {
		global $modx;

		$output = '';
		
		if ($_POST['type'] == 'new' && $_POST['message']) {

			$users = $_POST['users'];
			$users[] = $modx->user->id;

			sort($users);

			$params = array(
				'owner' => $modx->user->id,
				'users' => implode(',', $users),
				'name' => $_POST['name']
			);

			if (count($users) == 2) {
				if ($check_dialog = $modx->getObject('MessengerDialog', array('users' => $params['users']))) {
					$message = $this->messageSend($check_dialog->id, $_POST['message']);
					return array('success' => true, 'dialog' => $check_dialog->id);
				}

				unset($params['name']);
			}

			$dialog = $modx->newObject('MessengerDialog', $params);

			if ($dialog->save()) {
				$message = $this->messageSend($dialog->id, $_POST['message']);

				if ($message) {
					$output = array('success' => true, 'dialog' => $dialog->id);
				}
			}
		}

		if ($_POST['type'] == 'wrapper') {

			$output = array(
				'success' => true,
				'html' => $modx->getChunk('tpl.Messenger.dialog.new', array())
			);
		}

		return $output;
	}

	// поиск пользователя
	public function dialog_find_users() {
		global $modx;

		$output = '';
		$search = $_GET['search'];

		$sql = "
			SELECT u.id, u.username as text, p.photo
			FROM {$this->table_prefix}users AS u
			JOIN (SELECT internalKey, photo FROM {$this->table_prefix}user_attributes) p ON p.internalKey = u.id
			WHERE u.username LIKE '{$search}%' AND u.id != {$modx->user->id}
		";

		$query = $modx->query($sql);
		$users_ = $query->fetchAll(PDO::FETCH_ASSOC);

		if ($users_) {
			$output = array(
				'results' => $users_
			);
		} else {
			$output = array(
				'results' => array()
			);
		}

		return $output;
	}

	// записывает онлайн пользователя
	public function userOnline($id) {
		global $modx;
		
		$output = '';
		$user = $modx->getObject('MessengerUser', array('internalKey' => $id));

		if ($user) {
			$user->set('lasthit', time());
			$user->save();
		} else {
			$new = $modx->newObject('MessengerUser', array('internalKey' => $modx->user->id, 'lasthit' => time()));
			$new->save();
		}

		return true;
	}

	// проверяет пользователя на онлайн
	public function checkUserOnline($id, $ago) {
		global $modx;

		$output = '';
		$user = $modx->getObject('MessengerUser', array('internalKey' => $id));
		
		if ($user) {
			$time = time() - strtotime($user->lasthit);
			$min = floor($time / 60);
			
			if($min < 15) return 'online';
			if($ago) $output = $modx->runSnippet('dateAgo', array('input' => $user->lasthit));
		}

		return $output;
	}

	// парсит диалоги для вывода
	public function dialogues_parser($dialogues, $user) {
		global $modx;

		$output = '';
		
		foreach($dialogues as $dialog_key => $dialog) {

			$dialogues[$dialog_key]['users'] = explode(',', $dialog['users']);
			$dialogues[$dialog_key]['online'] = '';
			$dialogues[$dialog_key]['type'] = '';
		 	$dialogues[$dialog_key]['active'] = ($this->config['dialog'] == $dialog['id'] ? 'active' : '');
		 	$dialogues[$dialog_key]['unread'] = $this->checkUnread($dialog['id']);

			// если в диалоге 2 человека убирает id текущего и получает username собседеника
			if (count($dialogues[$dialog_key]['users']) == 2) {
		 		$key = array_search($user, $dialogues[$dialog_key]['users']);
		 		unset($dialogues[$dialog_key]['users'][$key]);
		 		$users = array_values($dialogues[$dialog_key]['users']);
		 		$dialogues[$dialog_key]['users'] = $users;
			 	$interlocutor = $modx->getObject('modUser', $dialogues[$dialog_key]['users'][0]);
			 	$interlocutor_profile = $modx->getObject('modUserProfile', $dialogues[$dialog_key]['users'][0]);
			 	$dialogues[$dialog_key]['name'] = $interlocutor->username;
			 	$dialogues[$dialog_key]['photo'] = ($interlocutor_profile->photo ? '<img src="'.$interlocutor_profile->photo.'">' : mb_substr($dialogues[$dialog_key]['name'], 0, 1, 'UTF-8'));
			 	$dialogues[$dialog_key]['user'] = $interlocutor->id;
			 	$dialogues[$dialog_key]['online'] = $this->checkUserOnline($interlocutor->id, false);
			 	unset($dialogues[$dialog_key]['users']);

			} else {
				$dialogues[$dialog_key]['type'] = 'group';
				$dialogues[$dialog_key]['users'] = count($dialogues[$dialog_key]['users']);
				$dialogues[$dialog_key]['photo'] = mb_substr($dialogues[$dialog_key]['name'], 0, 1, 'UTF-8');
			}
		}

		$output = $dialogues;
		return $output;
	}

	public function success($message, $template, $data) {
		$output = array(
			'success' => true,
			'message' => $message,
			'template' => $template,
			'data' => $data
		);

		return $output;
	}

	public function error($message) {
		$output = array(
			'success' => false,
			'message' => $message
		);

		return $output;
	}

	// уведомления
	public function push_($action, $data) {
		global $modx;

		$output = '';
		$data['type'] = $action;

		// push бд
		$link = mysqli_connect("app.comet-server.ru", $this->config['push_id'], $this->config['push_key'], "CometQL_v1");
		if (!$link) return "Невозможно подключение к CometQL";

		if ($action == 'auth'){
			$hash = md5($modx->user->id);
			$q = "INSERT INTO users_auth (id, hash) VALUES ({$modx->user->id}, '{$hash}');";
			$result = mysqli_query ( $link, $q );
			if($result) $output = $hash;
		}

		if ($action == 'message') {

			$dialog = $modx->getObject('MessengerDialog', $data['dialog']);
			$users = explode(',', $dialog->users);
			$data= $modx->toJson($data);

			// отправка уведомления каждому пользователю
			foreach ($users as $user_id) {
				if ($user_id == $modx->user->id) continue;
				$q = "INSERT INTO users_messages (id, event, message) VALUES ({$user_id}, 'event', '{$data}')";

				//$modx->log(ModX::LOG_LEVEL_ERROR, $q );
				$result = mysqli_query ( $link, $q );
			}
		}

		mysqli_close ( $link );
		return $output;
	}
}
