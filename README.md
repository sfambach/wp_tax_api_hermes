# Taxmod Hermes

Hermes connection for the [Taxonomy Modeller](https://github.com/sfambach/wp-taxonomy-modeler) (WordPress plugin).

**Status 0.0.2 (needs modeller 0.1.89 and [Taxmod Mail](https://github.com/sfambach/wp_tax_mail) 0.6.0 or later):
reads Hermes parcel notices («on the way», «comes today», «delay», «delivered») from the mailbox of Taxmod Mail and
hands them to the modeller's inbox.** Since 0.0.2 an «on the way» notice also carries the expected day, the delivery
window and the shipper (D-1131); the modeller writes them to the order from 0.1.143 on, older modellers get the notice
without them. A notice carries no shop order number; the modeller gives it to the order with the same tracking
number, from whatever shop (D-1017, D-1020), and never creates an order from it.

Hermes offers recipients no public tracking API (its HSI interface needs a business contract), so the mails are the
only source. Every connection is a module of its own (D-1006,
[concept](https://github.com/sfambach/wp-taxonomy-modeler/blob/main/docs/pakete/import/module-schnitt.md)).

## Setup

1. Install and set up Taxmod Mail (mailbox access).
2. Activate this module. *Taxonomy Modeller → Configuration → Hermes* shows under «Mail» which mails it reads.

## Development

`composer install`, then `vendor/bin/phpunit` (core suite, no WordPress). The sample mails in `tests/Core/fixtures`
are synthetic.

## License

Copyright (C) 2026 Stefan Fambach (SteFam), https://www.fambach.net — [PolyForm Noncommercial 1.0.0](LICENSE.md).
Commercial use needs the owner's prior consent.

## Authors

- **Stefan Fambach** (SteFam) – idea and concept, review and testing – https://www.fambach.net
- **Claude** (Anthropic AI) – implementation, via Claude Code

## AI disclosure

This project is built with AI assistance. Stefan Fambach had the idea, wrote the concept and reviews and tests every change; the implementation (code and documentation) is written largely by Claude (Anthropic) via Claude Code.
