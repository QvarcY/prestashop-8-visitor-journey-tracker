# Changelog

All notable changes to **CraftIN Visitor Journey** are documented in this file.

---

## 1.2.0

### Added

- Logged-in customer synchronization for visitor sessions.
- Existing anonymous session can be updated with `id_customer` when the visitor logs in during the same session.
- Optional customer details display in Back Office statistics.
- Customer column in the recent sessions table.
- Customer information block in the individual visitor path view.
- Direct Back Office link to the customer profile when customer details are enabled.
- New metric: logged-in sessions during the last 30 days.
- Upgrade script: `upgrade/upgrade-1.2.0.php`.

### Changed

- Visitor journey statistics now provide clearer separation between anonymous sessions and customer-linked sessions.
- Customer name/email display is controlled by a separate setting from customer ID linking.

### Preserved

- Existing session and pageview data is preserved during upgrade.

---

## 1.1.0

### Added

- Dedicated Back Office controller: `AdminCraftinVisitorJourney`.
- Separate left-side Back Office menu entry: **Apmeklētāju ceļi**.
- Statistics page outside the module configuration screen.
- Quick button from module configuration to visitor statistics.
- Upgrade script: `upgrade/upgrade-1.1.0.php`.

### Preserved

- Existing database tables and analytics data remain unchanged during upgrade.

---

## 1.0.0

### Added

- Initial installable module version.
- Anonymous visitor/session tracking.
- First landing page tracking.
- Referrer tracking.
- Traffic source detection for common platforms:
  - Google;
  - Facebook;
  - Instagram;
  - Threads;
  - Bing;
  - DuckDuckGo;
  - Pinterest;
  - YouTube;
  - X / Twitter;
  - direct visits;
  - other referrers.
- UTM support:
  - `utm_source`;
  - `utm_medium`;
  - `utm_campaign`.
- Page path tracking during a session.
- Page title storage.
- Controller name storage.
- Product/category ID storage when available.
- Approximate time between pageviews.
- Device type detection.
- Salted SHA-256 hashes for IP address and user agent.
- Dashboard with recent sessions and individual visitor path view.
- Data retention setting.
- Manual old-data cleanup action.
- Manual full analytics-data deletion action.
- Optional analytics-consent cookie gate.
- Optional Do Not Track respect.
- Common bot filtering.
