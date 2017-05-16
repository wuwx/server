<?php

/**
 * @copyright Copyright (c) 2017 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Settings\Personal;

use OC\Accounts\AccountManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Settings\ISettings;

class PersonalInfo implements ISettings {
	/** @var IConfig */
	private $config;
	/** @var IUserManager */
	private $userManager;
	/** @var AccountManager */
	private $accountManager;
	/** @var IGroupManager */
	private $groupManager;
	/** @var IFactory */
	private $l10nFactory;

	/**
	 * @param IConfig $config
	 * @param IUserManager $userManager
	 * @param IGroupManager $groupManager
	 * @param AccountManager $accountManager
	 * @param IFactory $l10nFactory
	 */
	public function __construct(IConfig $config, IUserManager $userManager, IGroupManager $groupManager, AccountManager $accountManager, IFactory $l10nFactory) {
		$this->config = $config;
		$this->userManager = $userManager;
		$this->accountManager = $accountManager;
		$this->groupManager = $groupManager;
		$this->l10nFactory = $l10nFactory;
	}

	/**
	 * @return TemplateResponse returns the instance with all parameters set, ready to be rendered
	 * @since 9.1
	 */
	public function getForm() {
		$lookupServerUploadEnabled = $this->config->getAppValue('files_sharing', 'lookupServerUploadEnabled', 'yes');
		$lookupServerUploadEnabled = $lookupServerUploadEnabled === 'yes';

		$uid = \OC_User::getUser();
		$user = $this->userManager->get($uid);

		$userData = $this->accountManager->getUser($user);

		$parameters = [
			'avatarChangeSupported' => \OC_User::canUserChangeAvatar($uid),
			'lookupServerUploadEnabled' => $lookupServerUploadEnabled,
			'avatar_scope' => $userData[AccountManager::PROPERTY_AVATAR]['scope'],
			'displayNameChangeSupported' => \OC_User::canUserChangeDisplayName($uid),
			'displayName' => $userData[AccountManager::PROPERTY_DISPLAYNAME]['value'],
			'email' => $userData[AccountManager::PROPERTY_EMAIL]['value'],
			'emailScope' => $userData[AccountManager::PROPERTY_EMAIL]['scope'],
			'emailMesage' => '',
			'emailVerification' => $userData[AccountManager::PROPERTY_EMAIL]['verified'],
			'phone' => $userData[AccountManager::PROPERTY_PHONE]['value'],
			'phoneScope' => $userData[AccountManager::PROPERTY_PHONE]['scope'],
			'address', $userData[AccountManager::PROPERTY_ADDRESS]['value'],
			'addressScope', $userData[AccountManager::PROPERTY_ADDRESS]['scope'],
			'website' =>  $userData[AccountManager::PROPERTY_WEBSITE]['value'],
			'websiteScope' =>  $userData[AccountManager::PROPERTY_WEBSITE]['scope'],
			'websiteVerification' => $userData[AccountManager::PROPERTY_WEBSITE]['verified'],
			'twitter' => $userData[AccountManager::PROPERTY_TWITTER]['value'],
			'twitterScope' => $userData[AccountManager::PROPERTY_TWITTER]['scope'],
			'twitterVerification' => $userData[AccountManager::PROPERTY_TWITTER]['verified'],
			'groups' => $this->groupManager->getUserGroups($user),
			'passwordChangeSupported' => \OC_User::canUserChangePassword($uid),
			'activelanguage' => $this->getLanguage($user),
		];


		return new TemplateResponse('settings', 'settings/personal/personal.info', $parameters, '');
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 * @since 9.1
	 */
	public function getSection() {
		return 'personal-info';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 * @since 9.1
	 */
	public function getPriority() {
		return 10;
	}

	private function getLanguage(IUser $user) {
		$uid = $user->getUID();

		$userLang = $this->config->getUserValue($uid, 'core', 'lang', $this->l10nFactory->findLanguage());
		$languageCodes = $this->l10nFactory->findAvailableLanguages();
		foreach($languageCodes as $lang) {
			$l = \OC::$server->getL10N('settings', $lang);
			// TRANSLATORS this is the language name for the language switcher in the personal settings and should be the localized version
			$potentialName = (string) $l->t('__language_name__');
			if($l->getLanguageCode() === $lang && substr($potentialName, 0, 1) !== '_') {//first check if the language name is in the translation file
				$ln = array('code' => $lang, 'name' => $potentialName);
			} elseif ($lang === 'en') {
				$ln = ['code' => $lang, 'name' => 'English (US)'];
			}else{//fallback to language code
				$ln=array('code'=>$lang, 'name'=>$lang);
			}

			// put appropriate languages into appropriate arrays, to print them sorted
			// used language -> common languages -> divider -> other languages
			if ($lang === $userLang) {
				$userLang = $ln;
			} elseif (in_array($lang, $commonLangCodes)) {
				$commonLanguages[array_search($lang, $commonLangCodes)]=$ln;
			} else {
				$languages[]=$ln;
			}
		}

		// if user language is not available but set somehow: show the actual code as name
		if (!is_array($userLang)) {
			$userLang = [
				'code' => $userLang,
				'name' => $userLang,
			];
		}

		return $userLang;
	}
}
