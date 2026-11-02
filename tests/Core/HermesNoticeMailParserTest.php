<?php declare(strict_types=1);

namespace TaxApiHermes\Tests\Core;

use PHPUnit\Framework\TestCase;
use TaxApiHermes\Core\HermesNoticeMailParser;
use Taxmod\Core\Import\Mail\MimeParser;

/**
 * The fixtures are synthetic, shaped after his real Hermes mails (2021–2026): on the way, comes today, delay, delivered.
 */
final class HermesNoticeMailParserTest extends TestCase
{
    /** @return array<string, mixed> */
    private static function read(string $fixture): array
    {
        $parser = new HermesNoticeMailParser('0.0.1');
        $mail = (new MimeParser())->parse(1, (string) file_get_contents(__DIR__ . '/fixtures/' . $fixture . '.eml'));
        self::assertTrue($parser->recognises($mail), 'not recognised: ' . $fixture);

        return $parser->parse($mail)->toArray();
    }

    public function testADoorDeliveryIsDeliveredToTheOrderWithItsTrackingNumber(): void
    {
        $notice = self::read('h1');

        self::assertSame(['Hermes', 'sendung:H1000000000000000001', 'delivered', ['H1000000000000000001']], [$notice['platform'], $notice['order_number'], $notice['kind'], $notice['tracking']]);
        self::assertTrue($notice['number_missing']);
    }

    public function testOnTheWayIsShipped(): void
    {
        self::assertSame('shipped', self::read('h2')['kind']);
    }

    public function testAParcelWaitingInAPaketShopIsNotDeliveredYet(): void
    {
        self::assertSame('shipped', self::read('h3')['kind']);
    }

    public function testAForecastDeliveryIsStillOnTheWay(): void
    {
        self::assertSame(['shipped', ['H1000000000000000006']], [self::read('h6')['kind'], self::read('h6')['tracking']]);
    }

    public function testADelayIsStillOnTheWay(): void
    {
        self::assertSame(['shipped', ['H1000000000000000007']], [self::read('h7')['kind'], self::read('h7')['tracking']]);
    }

    public function testTheTrackingLinkGivesTheNumberWhenTheTextDoesNot(): void
    {
        self::assertSame(['H1000000000000000004'], self::read('h4')['tracking']);
    }

    public function testAMailWithoutStatusOrNumberBringsNothing(): void
    {
        self::assertSame('no_receipt', self::read('h5')['kind']);
    }

    public function testOtherSendersAreNotHermes(): void
    {
        $mail = (new MimeParser())->parse(1, "From: x@dhl.de\r\nSubject: Ihre Sendung ist unterwegs\r\n\r\nx");
        self::assertFalse((new HermesNoticeMailParser('0.0.1'))->recognises($mail));
    }
}
