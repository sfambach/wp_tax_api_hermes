<?php declare(strict_types=1);

namespace TaxApiHermes\WordPress;

use TaxApiHermes\Core\HermesNoticeMailParser;
use Taxmod\Core\Import\Mail\OrderMailParser;

/**
 * The boundary: registers the Hermes notice reader with Taxmod Mail (filter `taxmail_parsers`) and a tab on the
 * modeller's Configuration page whose part «Mail» names what it reads (D-1006, D-1007).
 *
 * @see https://github.com/sfambach/wp-taxonomy-modeler/blob/main/docs/pakete/import/module-schnitt.md
 */
final class Plugin
{
    public const VERSION = '0.0.1';
    public const TAB     = 'hermes';

    public static function boot(): void
    {
        add_action('plugins_loaded', static function (): void {
            load_plugin_textdomain('taxapihermes', false, dirname(plugin_basename(dirname(__DIR__, 2) . '/wp-tax-api-hermes.php')) . '/languages');

            // The contract lives in the modeller; without it there is nothing to register with.
            if (! interface_exists(OrderMailParser::class)) {
                add_action('admin_notices', static fn () => print '<div class="notice notice-warning"><p><strong>Taxmod Hermes:</strong> '
                    . esc_html__('needs the Taxonomy Modeller 0.1.89 or later and Taxmod Mail 0.6.0 or later.', 'taxapihermes') . '</p></div>');

                return;
            }

            $reader = new HermesNoticeMailParser(self::VERSION);
            add_filter('taxmail_parsers', static fn (array $parsers): array => [...$parsers, $reader]);
            add_filter('taxmod_config_tabs', static fn (array $tabs): array => $tabs + [self::TAB => [
                'title'   => 'Hermes',
                'version' => self::VERSION,
                'reader'  => $reader,
                'guide'   => self::guide(...),
                'render'  => static fn (): string => '',
                'save'    => static function (array $post): void {
                },
            ]], 30);
        });
    }

    /** The box «How to connect» beside the tab (D-963). */
    public static function guide(): string
    {
        return '<ol><li>' . esc_html__('Let Hermes send its parcel notices to the mailbox of Taxmod Mail (or forward them there).', 'taxapihermes') . '</li><li>'
            . esc_html__('Keep this module active: each notice goes to the order with the same tracking number; it never creates an order.', 'taxapihermes') . '</li></ol><p>'
            . esc_html__('Hermes offers recipients no public API, so the mails are the only source.', 'taxapihermes') . '</p>';
    }
}
