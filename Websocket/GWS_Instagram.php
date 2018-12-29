<?php
namespace GDO\Instagram\Websocket;

use GDO\Core\Module_Core;
use GDO\User\GDO_User;
use GDO\Websocket\Server\GWS_Command;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;
use GDO\User\GDO_Session;
use GDO\Instagram\Method\Auth;

final class GWS_Instagram extends GWS_Command
{
	public function execute(GWS_Message $msg)
	{
		$accessToken = $msg->readString();
		$this->onAccess($msg, $accessToken, Auth::make());
	}
	
	public function onAccess(GWS_Message $msg, $accessToken, Auth $method)
	{
		$method->gotAccessToken($accessToken);
		
		$user = GDO_User::current();
		$user->tempSet('sess_id', GDO_Session::instance()->getID());
		$msg->conn()->setUser($user);
		
		$msg->replyText($msg->cmd(), json_encode(Module_Core::instance()->gdoUserJSON()));
	}
}

GWS_Commands::register(0x0112, new GWS_Instagram());
