<?php
namespace GDO\Instagram;

use GDO\Avatar\GDO_UserAvatar;
use GDO\Core\GDO_Module;
use GDO\Form\GDT_Form;
use GDO\DB\GDT_Checkbox;
use GDO\Core\GDT_Secret;
use GDO\User\GDO_User;
use GDO\Net\HTTP;
use GDO\Core\GDT_Success;
use GDO\Core\GDT_Error;
use GDO\UI\GDT_Button;
use GDO\Avatar\GDO_Avatar;
use GDO\File\GDO_File;
use GDO\Util\Strings;
/**
 * Instagram SDK Module and Authentication.
 * 
 * @author gizmore
 * @version 6.08
 * 
 * @see OAuthToken
 * @see GDT_FBAuthButton
 */
final class Module_Instagram extends GDO_Module
{
	public $module_priority = 46;
	
	public function onLoadLanguage() { $this->loadLanguage('lang/instagram'); }

	##############
	### Config ###
	##############
	public function getConfig()
	{
		return array(
			GDT_Checkbox::make('instagram_auth')->initial('1'),
			GDT_Secret::make('instagram_client_id')->ascii()->caseS()->max(64)->initial('56a0cdbb322d46009d39ae653d3d29b1'),
			GDT_Secret::make('instagram_secret')->ascii()->caseS()->max(64)->initial('15aa3116224b4c76af3cca2a2dfbad13'),
		);
	}
	public function cfgAuth() { return $this->getConfigValue('instagram_auth'); }
	public function cfgClientID() { return $this->getConfigValue('instagram_client_id'); }
	public function cfgSecret() { return $this->getConfigValue('instagram_secret'); }
	
// 	#############
// 	### Hooks ###
// 	#############
// 	/**
// 	 * Hook into register and login form creation and add a link.
// 	 * @param GDT_Form $form
// 	 */
	public function hookLoginForm(GDT_Form $form) { $this->hookRegisterForm($form); }
	public function hookRegisterForm(GDT_Form $form)
	{
	    /** @var $cont \GDO\UI\GDT_Container **/
	    $form->actions()->addField(GDT_Button::make('link_instagram_auth')->secondary()->href(href('Instagram', 'Auth')));
	}

	public function hookIGUserAuthenticated(GDO_User $user, $accessToken, $data)
	{
		$this->hookIGUserActivated($user, $accessToken, $data);
	}
	
	public function hookIGUserActivated(GDO_User $user, $accessToken, $data)
	{
		if (module_enabled('Avatar'))
		{
			$avatar = GDO_Avatar::forUser($user);
			$file = GDO_File::blank($avatar->getGDOVars());
			
			# custom avatar set. do not check for updates.
			if ($avatar->isPersisted() &&
				(!str_starts_with($file->getName(), 'IG-Avatar-')) )
			{
				return;
			}
			
			# check for update
			$url = $data->profile_picture;
			if ($contents = HTTP::getFromURL($url))
			{
				if ($this->hasFileChanged($file, $contents))
				{
					if (GDO_UserAvatar::createAvatarFromString($user, "IG-Avatar-{$data->id}.jpg", $contents))
					{
						echo GDT_Success::with('msg_ig_avatar_imported')->render();
						return;
					}
				}
				else
				{
					return; # all fine
				}
			}
			echo GDT_Error::with('err_ib_avatar_not_imported')->render();
		}
	}
	
	private function hasFileChanged(GDO_File $file, $contents)
	{
		return md5_file($file->getPath()) !== md5($contents);
	}
}
