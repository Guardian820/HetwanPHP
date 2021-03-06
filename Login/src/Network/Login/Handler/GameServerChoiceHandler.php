<?php

namespace Hetwan\Network\Login\Handler;

use Hetwan\Entity\AccountEntity;
use Hetwan\Network\Login\Protocol\Enum\ServerState;
use Hetwan\Network\Login\Protocol\Enum\ServerPopulation;
use Hetwan\Network\Login\Protocol\Formatter\LoginMessageFormatter;


final class GameServerChoiceHandler extends \Hetwan\Network\Login\Handler\Base\Handler
{
	/**
	 * @Inject
	 * @var \Hetwan\Loader\ServerLoader
	 */
	private $serverLoader;

	/**
	 * @Inject
	 * @var \Hetwan\Network\Exchange\ExchangeServer
	 */
	private $exchangeServer;

	public function initialize() : void
	{
		$this->send(LoginMessageFormatter::accountNicknameInformationMessage($this->getAccount()->getNickname()));
		$this->send(LoginMessageFormatter::accountCommunityInformationMessage($this->getAccount()->getCommunity()));

		$this->refreshServersList();

		$this->send(LoginMessageFormatter::identificationSuccessMessage($this->getAccount()->getGmLevel() > 0 ? 1 : 0));
		$this->send(LoginMessageFormatter::accountSecretQuestionInformationMessage($this->getAccount()->getSecretQuestion()));
	}

	public function handle(string $data) : bool
	{
		switch (substr($data, 1, 1)) {
			case 'f':
				$this->queueSystem(); // TODO: queue

				break;
			case 'F':
				$this->searchPlayersByAccountNickname(substr($data, 2));

				break;
			case 'x':
				$this->send(LoginMessageFormatter::playersListMessage($this->getAccount()));

				break;
			case 'X':
				$this->sendAccessServerResponse(substr($data, 2));

				break;
		}

		return true;
	}

	public function queueSystem() : void
	{
		$this->send(LoginMessageFormatter::queueMessage(1, 0, 0, 1));
	}

	public function refreshServersList() : void
	{
		$this->send(LoginMessageFormatter::serversListMessage(
			$this->serverLoader->getValues()
		));
	}

	private function searchPlayersByAccountNickname(string $nickname) : void
	{
		$account = $this->entityManager->get()
								   	   ->getRepository(AccountEntity::class)
								   	   ->findOneByNickname($nickname);

		$this->send(LoginMessageFormatter::searchPlayersMessage($account ? $account->getPlayers() : []));
	}

	private function generateTicket(int $serverId) : string
	{
		$ticketKey = uniqid();

		$server = $this->exchangeServer->getServerWithId($serverId);
		$server->sendTicket($ticketKey, $this->client->getConnection()->remotePort, $this->getAccount()->getId());

		return $ticketKey;
	}

	private function sendAccessServerResponse(int $id) : void
	{
		if (($server = $this->serverLoader->getBy(['id' => $id], $assertCount = false, $first = true)) === null or 
			$server->getState() !== ServerState::ONLINE) {
			$this->send(LoginMessageFormatter::serverInaccessible());
		} elseif ($server->getRequireSubscription() === true and !$this->getAccount()->getSubscriptionTimeLeft()) {
			$this->send(LoginMessageFormatter::serverRequireSubscription());
		} elseif ($server->getPopulation() === ServerPopulation::FULL) {
			$this->send(LoginMessageFormatter::serverFull());
		} else {
			$this->send(LoginMessageFormatter::serverAccess($server->getIpAddress(), $server->getPort(), $this->generateTicket($server->getId())));
		}
	}
}