<?php

namespace WebUi;

/**
 * @Instance
 */
class RootController {

	/**
	 * Name of the module.
	 * Set automatically by module loader.
	 */
	public $moduleName;

	/** @Inject */
	public $httpApi;

	/** @Inject */
	public $chatBot;

	/** @Inject("WebUi\LoginController") */
	public $login;

	/** @Inject("WebUi\Template") */
	public $template;

	const LOG_EVENTS_TOPIC = 'http://localhost/logEvents';

	/**
	 * @Setup
	 */
	public function setup() {

		$this->httpApi->registerHandler("|^/{$this->moduleName}/$|i",
			array($this, 'handleRootResource'));

		$this->httpApi->registerHandler("|^/{$this->moduleName}/wsendpoint$|i",
			array($this, 'handleWsResource'));

		$this->onLogEventsPublishToClients();
		$this->onSubscribeSendLogEvents();
	}

	private function onSubscribeSendLogEvents() {
		$that = $this;
		$this->httpApi->onWampSubscribe(self::LOG_EVENTS_TOPIC, function ($client) use ($that) {
			$events = $that::getBufferAppender()->getEvents();
			foreach ($events as $event) {
				$client->event($that::LOG_EVENTS_TOPIC, $event);
			}
		});
	}

	private function onLogEventsPublishToClients() {
		$that = $this;
		self::getBufferAppender()->onEvent(function ($event) use ($that) {
			$that->httpApi->wampPublish($that::LOG_EVENTS_TOPIC, $event);
		});
	}

	public static function getBufferAppender() {
		$appender = \Logger::getRootLogger()->getAppender('appenderBuffer');
		if (!$appender) {
			$appender = new LoggerAppenderBuffer();
		}
		return $appender;
	}

	public function handleRootResource($request, $response, $body, $session) {
		if ($this->login->isLoggedIn($session)) {
			$response->writeHead(200);
			$response->end($this->template->render('index.html', $session, array(
				'webSocketUri' => $this->httpApi->getWebSocketUri(
					"/{$this->moduleName}/wsendpoint"),
				'logEventsTopic' => self::LOG_EVENTS_TOPIC
			)));
		} else {
			$this->httpApi->redirectToPath($response, "/{$this->moduleName}/login");
		}
	}

	public function handleWsResource($request, $response, $body, $session) {
		if ($request->isWebSocketHandshake()) {
			if ($this->login->isLoggedIn($session)) {
				$this->httpApi->upgradeToWebSocket($request, $response);
			} else {
				$response->writeHead(403);
				$response->end();
			}
		}
	}
}
