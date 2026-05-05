# CraftIN Visitor Journey for PrestaShop 8

**CraftIN Visitor Journey** is a lightweight first-party visitor journey analytics module for **PrestaShop 8.x**. It helps a store owner understand where visitors come from, which page they land on first, how they move through the shop, and — only when enabled — which logged-in customer was connected to a visitor journey.

The module was originally created for **CraftIN.lv**, a small handmade / custom-order e-commerce store, where understanding the real visitor path is often more useful than looking only at generic pageview numbers.

---

## Latviski īsumā

Šis modulis ļauj PrestaShop administrācijā redzēt:

- no kurienes apmeklētājs atnāca uz veikalu;
- vai avots bija Google, Facebook, Instagram, Threads, Bing, tiešā saite vai UTM kampaņa;
- kura bija pirmā apmeklētā lapa;
- kāds bija apmeklētāja ceļš pa veikalu vienas sesijas laikā;
- cik lapas tika apskatītas;
- kāda ierīce tika izmantota — desktop, mobile, tablet vai bot;
- vai sesija ir saistīta ar reģistrētu klientu;
- pēc izvēles — klienta vārdu, e-pastu un saiti uz klienta profilu Back Office pusē.

Modulis **neieraksta ekrānu**, **neseko peles kustībām**, **nelasa formas laukus** un **neglabā IP adresi tiešā veidā**.

---

## Features

### Traffic source detection

The module detects and stores the first traffic source for a visitor session:

- direct visits;
- Google;
- Bing;
- DuckDuckGo;
- Facebook;
- Instagram;
- Threads;
- Pinterest;
- YouTube;
- X / Twitter;
- other referring websites;
- UTM campaign parameters.

Supported UTM values include:

- `utm_source`;
- `utm_medium`;
- `utm_campaign`.

### Visitor journey tracking

For each anonymous session, the module can store:

- anonymous visitor key;
- anonymous session key;
- first landing page;
- first referrer;
- traffic source;
- medium;
- campaign;
- page URL;
- page title;
- PrestaShop controller name;
- product ID when available;
- category ID when available;
- screen size;
- approximate time between pageviews;
- device type;
- creation and update timestamps.

### Back Office statistics

The module adds a dedicated Back Office menu item:

> **Apmeklētāju ceļi**

The statistics page shows:

- sessions during the last 7 days;
- sessions during the last 30 days;
- pageviews during the last 30 days;
- logged-in sessions during the last 30 days;
- average pages per session;
- top traffic sources;
- recent visitor sessions;
- full page path for a selected session.

### Optional customer identification

By default, customer identification is disabled.

When enabled, the module can connect a visitor session to a logged-in PrestaShop customer by storing `id_customer`.

There are two separate settings:

1. **Link logged-in customer ID**  
   Stores the customer ID when the visitor is logged in.

2. **Show customer name and email in statistics**  
   Displays the customer name, email and Back Office customer-profile link in the statistics page.

This separation allows a shop owner to collect a customer link internally without automatically displaying personal details everywhere in the dashboard.

---

## What this module does not do

This module is intentionally simple and privacy-conscious.

It does **not** record:

- screen recordings;
- heatmaps;
- mouse movement;
- scroll depth;
- keystrokes;
- form field values;
- passwords;
- raw IP addresses;
- raw user-agent strings;
- payment data;
- checkout field contents.

IP addresses and user-agent values are stored only as salted SHA-256 hashes. These hashes are used only to support session analysis and reduce direct personal-data exposure.

---

## Requirements

- PrestaShop 8.x
- Tested target: PrestaShop 8.2.3
- PHP version compatible with your PrestaShop 8 installation
- MySQL / MariaDB with InnoDB support
- Back Office access with permission to install modules

---

## Installation

1. Download the module ZIP file.
2. In PrestaShop Back Office, go to:

   > **Modules → Module Manager → Upload a module**

3. Upload the ZIP file.
4. Install the module.
5. Open module configuration.
6. Review the privacy and consent settings.
7. Open the new Back Office menu item:

   > **Apmeklētāju ceļi**

After installation, visit your shop from another browser or an incognito window, open a few pages, then refresh the statistics page.

---

## Upgrade

If an older version of this module is already installed, upload the new ZIP through the normal PrestaShop module upload/update flow.

Existing visitor journey data is preserved during upgrades.

Included upgrade scripts:

- `upgrade/upgrade-1.1.0.php`
- `upgrade/upgrade-1.2.0.php`

If the Back Office menu item does not appear immediately after an upgrade:

1. clear PrestaShop cache;
2. log out and back in to Back Office;
3. open the module configuration once — the module will try to recreate the missing admin tab.

---

## Recommended first configuration

For testing in a development or private environment:

```text
Enable tracking: Yes
Require analytics consent cookie: No
Respect Do Not Track: No
Ignore common bots: Yes
Link logged-in customer ID: No
Show customer name and email in statistics: No
```

For a public production shop, a safer starting point is:

```text
Enable tracking: Yes
Require analytics consent cookie: Yes
Respect Do Not Track: Yes
Ignore common bots: Yes
Link logged-in customer ID: No, unless truly needed
Show customer name and email in statistics: No, unless truly needed
```

---

## Cookie consent integration

The module can require an analytics-consent cookie before it starts tracking.

Default supported cookie names are stored in the setting:

```text
cicc_analytics,craftin_cookie_analytics,cookie_consent_analytics,analytics_consent,cc_analytics,CIVJ_ANALYTICS_OK
```

You can edit this list in the module configuration.

The module considers consent accepted when one of the configured cookies contains a clearly positive value such as:

```text
1, true, yes, accepted, granted, allow, allowed
```

If your cookie-consent module uses another cookie name or value format, adjust the configuration accordingly.

---

## GDPR and privacy note

This module is a first-party analytics tool. Depending on your jurisdiction, implementation and configuration, visitor journey tracking may require disclosure in your privacy policy and cookie policy.

Recommended public-facing wording should explain:

- that the shop uses first-party analytics;
- that the analytics helps understand how visitors move through the store;
- what data is stored;
- how long the data is kept;
- whether logged-in customer sessions can be linked to a customer account;
- how the visitor can refuse or withdraw analytics consent.

The default retention period is **90 days**, but it can be changed in the module configuration.

This README is not legal advice. For a live shop, especially if customer identification is enabled, the store owner should review the privacy wording carefully.

---

## Database tables

The module creates two database tables using the active PrestaShop table prefix:

```text
PREFIX_civj_session
PREFIX_civj_pageview
```

### `PREFIX_civj_session`

Stores the main visitor session record:

- visitor key;
- session key;
- optional customer ID;
- first URL;
- first referrer;
- source;
- medium;
- campaign;
- device;
- user-agent hash;
- IP hash;
- timestamps.

### `PREFIX_civj_pageview`

Stores individual pageviews connected to a visitor session:

- session ID;
- page URL;
- page title;
- controller;
- product ID;
- category ID;
- referrer;
- screen size;
- approximate time on previous page;
- timestamp.

On uninstall, these tables are removed.

---

## File structure

```text
craftinvisitorjourney/
├── controllers/
│   ├── admin/
│   │   └── AdminCraftinVisitorJourneyController.php
│   └── front/
│       └── collect.php
├── upgrade/
│   ├── upgrade-1.1.0.php
│   └── upgrade-1.2.0.php
├── views/
│   ├── css/
│   ├── img/
│   └── js/
│       └── tracker.js
├── CHANGELOG.md
├── LICENSE.txt
├── README.md
└── craftinvisitorjourney.php
```

---

## Known limitations

- This is not a replacement for advanced analytics platforms such as Matomo or GA4.
- Time on page is approximate and is calculated from pageview timing signals.
- If consent is required and the consent cookie name is incorrect, tracking will not start.
- Ad blockers or strict browser privacy settings may block some tracking requests.
- Customer identification works only for logged-in customer sessions and only when enabled.
- Historical anonymous sessions cannot always be retroactively connected to a customer unless the customer logs in during the same active session.

---

## Suggested GitHub repository description

```text
Lightweight first-party visitor journey analytics module for PrestaShop 8.x. Tracks traffic source, landing page, visitor path and optional logged-in customer connection.
```

Suggested GitHub topics:

```text
prestashop prestashop-module analytics visitor-journey ecommerce gdpr first-party-analytics traffic-source php
```

---

## Changelog

See [`CHANGELOG.md`](CHANGELOG.md).

---

## Author

Created by **QvarcY / CraftIN**

- Website: https://craftin.lv
- Author website: https://kas.id.lv
- Email: info@kas.id.lv

---

## License

AFL-3.0

See [`LICENSE.txt`](LICENSE.txt).
