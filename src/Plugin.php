<?php

namespace smallpics\craft;

use craft\base\Plugin as BasePlugin;
use craft\elements\Asset;
use craft\events\DefineAssetThumbUrlEvent;
use craft\events\DefineAssetUrlEvent;
use craft\services\Assets;
use craft\web\twig\variables\CraftVariable;
use smallpics\craft\models\Settings;
use smallpics\craft\services\Transformer;
use smallpics\craft\variables\SmallPicsVariable;
use yii\base\Event;

/**
 * @property Transformer $transformer
 */
class Plugin extends BasePlugin
{
	public static self $instance;

	public function init(): void
	{
		self::$instance = $this;

		parent::init();

		$this->setComponents([
			'transformer' => Transformer::class,
		]);

		Event::on(
			CraftVariable::class,
			CraftVariable::EVENT_INIT,
			static function (Event $event): void {
				/** @var CraftVariable $variable */
				$variable = $event->sender;
				$variable->set('smallpics', SmallPicsVariable::class);
			}
		);

		Event::on(
			Asset::class,
			Asset::EVENT_BEFORE_DEFINE_URL,
			function (DefineAssetUrlEvent $event): void {
				$settings = self::settings();

				if (! $settings->transformNativeImages || $event->transform === null || $event->url !== null || $event->handled) {
					return;
				}

				if (! $event->sender instanceof Asset || $event->sender->kind !== Asset::KIND_IMAGE) {
					return;
				}

				$event->url = (string) $this->transformer->transformImage($event->sender, [
					'transform' => $event->transform ?? [],
					...$settings->nativeTransformsParams,
				]);
				$event->handled = true;
			}
		);

		Event::on(
			Assets::class,
			Assets::EVENT_DEFINE_THUMB_URL,
			function (DefineAssetThumbUrlEvent $event): void {
				$settings = self::settings();

				if (! $settings->transformThumbnails || $event->url !== null || $event->asset->kind !== Asset::KIND_IMAGE) {
					return;
				}

				$event->url = (string) $this->transformer->transformImage($event->asset, [
					'width' => $event->width,
					'height' => $event->height,
					'mode' => 'fit',
					...$settings->thumbnailParams,
				]);
			}
		);
	}

	public static function settings(): Settings
	{
		/** @var Settings $settings */
		$settings = self::$instance->getSettings();

		return $settings;
	}

	protected function createSettingsModel(): ?Settings
	{
		return new Settings();
	}
}
