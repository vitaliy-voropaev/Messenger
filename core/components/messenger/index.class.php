<?php

/**
 * Class modExtraMainController
 */
abstract class MessengerMainController extends MessengerManagerController {
	/** @var modExtra $modExtra */
	public $Messenger;


	/**
	 * @return void
	 */
	public function initialize() {
		$corePath = $this->modx->getOption('messenger_core_path', null, $this->modx->getOption('core_path') . 'components/messenger/');
		require_once $corePath . 'model/messenger/messenger.class.php';

		$this->modExtra = new Messenger($this->modx);
		//$this->addCss($this->modExtra->config['cssUrl'] . 'mgr/main.css');
		$this->addJavascript($this->Messenger->config['jsUrl'] . 'mgr/messenger.js');
		$this->addHtml('
		<script type="text/javascript">
			Messenger.config = ' . $this->modx->toJSON($this->Messenger->config) . ';
			Messenger.config.connector_url = "' . $this->Messenger->config['connectorUrl'] . '";
		</script>
		');

		parent::initialize();
	}


	/**
	 * @return array
	 */
	public function getLanguageTopics() {
		return array('messenger:default');
	}


	/**
	 * @return bool
	 */
	public function checkPermissions() {
		return true;
	}
}

