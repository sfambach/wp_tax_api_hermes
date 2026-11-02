<?php declare(strict_types=1);

namespace TaxApiHermes\Core;

use Taxmod\Core\Import\Mail\MailMessage;
use Taxmod\Core\Import\Mail\OrderMailParser;
use Taxmod\Core\Import\OrderProposal;
use Taxmod\Core\Import\ProposalKind;

/**
 * Hermes's parcel notices («Deine Sendung ist unterwegs», «Erfolgreiche Haustürzustellung»): no shop order, only the
 * tracking number. The modeller gives them to the order that carries that number, whatever shop it is from (D-1020).
 * Hermes offers recipients no public API, so the mail is the only source.
 *
 * @see https://github.com/sfambach/wp-taxonomy-modeler/blob/main/docs/pakete/import/module-schnitt.md
 */
final class HermesNoticeMailParser implements OrderMailParser
{
    private const DOMAINS = ['myhermes.de', 'hermesworld.com', 'hermes-europe.de'];

    public function __construct(private readonly string $version)
    {
    }

    public function key(): string
    {
        return 'hermes';
    }

    public function shopName(): string
    {
        return 'Hermes';
    }

    public function recognises(MailMessage $mail): bool
    {
        $domain = $mail->senderDomain();

        return array_filter(self::DOMAINS, static fn (string $hermes): bool => $domain === $hermes || str_ends_with($domain, '.' . $hermes)) !== [];
    }

    public function parse(MailMessage $mail): OrderProposal
    {
        $number = $this->trackingNumber($mail);
        $kind = $this->kind($mail);

        if ($kind === ProposalKind::NoReceipt || $number === null) {
            return new OrderProposal($this->shopName(), '', ProposalKind::NoReceipt);
        }

        return new OrderProposal($this->shopName(), 'sendung:' . $number, $kind, trackingNumbers: [$number], numberMissing: true);
    }

    public function kinds(): array
    {
        return [ProposalKind::Shipped, ProposalKind::Delivered];
    }

    public function version(): string
    {
        return $this->version;
    }

    /**
     * Subject and first sentence together, because Hermes words the subject freely («Erfolgreiche Haustürzustellung»).
     * ⚠️ *«wird voraussichtlich am Dienstag zugestellt» is a forecast, not a delivery: tested against seven of his real
     * mails (2021–2026), four «auf dem Weg» read as delivered before. A delay («Wichtige Information zur Zustellung»)
     * and a parcel waiting in a PaketShop are still on the way.*
     */
    private function kind(MailMessage $mail): ProposalKind
    {
        $said = mb_strtolower($mail->subject . ' ' . ($mail->lines()[0] ?? ''));
        $forecast = preg_match('/voraussichtlich|erst am|kommt leider/u', $said) === 1;

        return match (true) {
            ! $forecast && ! str_contains($said, 'paketshop')
                && preg_match('/wurde[^.]*zugestellt|erfolgreich[^.]*zugestellt|haustürzustellung|beim nachbarn|abgestellt|zustellung erfolgt/u', $said) === 1 => ProposalKind::Delivered,
            preg_match('/unterwegs|auf dem weg|kommt heute|in zustellung|zustellung|angekündigt|paketshop|übergeben|voraussichtlich/u', $said) === 1 => ProposalKind::Shipped,
            default => ProposalKind::NoReceipt,
        };
    }

    /** «Sendungsnummer H1033…» on one line, or the number on the next; the tracking link carries it after «#». */
    private function trackingNumber(MailMessage $mail): ?string
    {
        $lines = $mail->lines();

        foreach ($lines as $k => $line) {
            if (! str_contains($line, 'Sendungsnummer')) {
                continue;
            }

            if (preg_match('/Sendungsnummer:?\s+([0-9A-Z]{10,40})(?![0-9A-Z])/', $line, $found) === 1
                || preg_match('/^([0-9A-Z]{10,40})$/', $lines[$k + 1] ?? '', $found) === 1) {
                return $found[1];
            }
        }

        return preg_match('/sendungsverfolgung\/[^"\'\s]*#([0-9A-Z]{10,40})\b/', $mail->html, $found) === 1 ? $found[1] : null;
    }
}
