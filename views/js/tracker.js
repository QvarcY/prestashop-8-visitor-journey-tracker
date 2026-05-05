(function () {
  'use strict';

  var cfg = window.civjConfig || {};
  if (!cfg.collectUrl) {
    return;
  }

  function hasDoNotTrack() {
    return !!(
      (navigator.doNotTrack && navigator.doNotTrack === '1') ||
      (window.doNotTrack && window.doNotTrack === '1') ||
      (navigator.globalPrivacyControl === true)
    );
  }

  if (cfg.respectDnt && hasDoNotTrack()) {
    return;
  }

  function getCookie(name) {
    var cookies = document.cookie ? document.cookie.split('; ') : [];
    for (var i = 0; i < cookies.length; i++) {
      var parts = cookies[i].split('=');
      var key = decodeURIComponent(parts.shift());
      if (key === name) {
        return decodeURIComponent(parts.join('='));
      }
    }
    return '';
  }

  function consentValueAccepted(value) {
    if (!value) {
      return false;
    }
    value = String(value).toLowerCase();
    if (['1', 'true', 'yes', 'accepted', 'allowed', 'granted'].indexOf(value) !== -1) {
      return true;
    }
    if (value.indexOf('analytics') !== -1 && (
      value.indexOf('true') !== -1 ||
      value.indexOf('1') !== -1 ||
      value.indexOf('granted') !== -1 ||
      value.indexOf('accepted') !== -1
    )) {
      return true;
    }
    return value.indexOf('all') !== -1 || value.indexOf('accept') !== -1 || value.indexOf('grant') !== -1;
  }

  function hasConsent() {
    if (!cfg.requireConsent) {
      return true;
    }
    var names = cfg.consentCookies || [];
    for (var i = 0; i < names.length; i++) {
      if (consentValueAccepted(getCookie(names[i]))) {
        return true;
      }
    }
    return false;
  }

  if (!hasConsent()) {
    return;
  }

  function setCookie(name, value, seconds) {
    var date = new Date();
    date.setTime(date.getTime() + seconds * 1000);
    var cookie = encodeURIComponent(name) + '=' + encodeURIComponent(value) + '; expires=' + date.toUTCString() + '; path=/; SameSite=Lax';
    if (window.location.protocol === 'https:') {
      cookie += '; Secure';
    }
    document.cookie = cookie;
  }

  function randomKey(prefix) {
    var bytes;
    var out = prefix + '_';
    if (window.crypto && window.crypto.getRandomValues) {
      bytes = new Uint8Array(16);
      window.crypto.getRandomValues(bytes);
      for (var i = 0; i < bytes.length; i++) {
        out += ('0' + bytes[i].toString(16)).slice(-2);
      }
      return out;
    }
    return out + String(Date.now()) + '_' + Math.random().toString(36).slice(2);
  }

  function getOrCreateVisitorKey() {
    var value = getCookie('civj_vid');
    if (!value) {
      value = randomKey('v');
    }
    setCookie('civj_vid', value, 365 * 24 * 60 * 60);
    return value;
  }

  function getOrCreateSessionKey() {
    var value = getCookie('civj_sid');
    if (!value) {
      value = randomKey('s');
    }
    var minutes = parseInt(cfg.sessionMinutes || 30, 10);
    setCookie('civj_sid', value, Math.max(5, minutes) * 60);
    return value;
  }

  function getTimeOnPrevious() {
    var previous = parseInt(getCookie('civj_last_ts') || '0', 10);
    var now = Math.floor(Date.now() / 1000);
    setCookie('civj_last_ts', String(now), 30 * 60);
    if (!previous || previous > now) {
      return null;
    }
    var diff = now - previous;
    if (diff < 0 || diff > 3600) {
      return null;
    }
    return diff;
  }

  function send(payload) {
    var body = JSON.stringify(payload);
    if (navigator.sendBeacon) {
      try {
        var blob = new Blob([body], { type: 'application/json' });
        navigator.sendBeacon(cfg.collectUrl, blob);
        return;
      } catch (e) {}
    }

    if (window.fetch) {
      fetch(cfg.collectUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: body,
        credentials: 'same-origin',
        keepalive: true
      }).catch(function () {});
    }
  }

  function trackPageView() {
    if (document.visibilityState === 'prerender') {
      return;
    }

    var payload = {
      visitor_key: getOrCreateVisitorKey(),
      session_key: getOrCreateSessionKey(),
      url: window.location.href,
      referrer: document.referrer || '',
      title: document.title || '',
      screen: window.screen ? (window.screen.width + 'x' + window.screen.height) : '',
      time_on_previous: getTimeOnPrevious(),
      context: cfg.context || {},
      ts: Date.now()
    };

    send(payload);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', trackPageView);
  } else {
    trackPageView();
  }
})();
