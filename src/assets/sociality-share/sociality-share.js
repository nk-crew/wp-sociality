/**
 * Sociality Share
 *
 * Based on Social Likes
 * http://sapegin.github.com/social-likes
 */
const { jQuery: $, location, socialityData } = window;

const prefix = 'sociality-share';
const protocol = location.protocol === 'https:' ? 'https:' : 'http:';

/**
 * Helpers
 */

// Camelize data-attributes
function dataToOptions(elem) {
  function upper(m, l) {
    return l.toUpper();
  }
  const options = {};
  const data = elem.data();

  Object.keys(data).forEach((key) => {
    let value = data[key];
    if (value === 'yes') {
      value = true;
    } else if (value === 'no') {
      value = false;
    }
    options[key.replace(/-(\w)/g, upper)] = value;
  });

  return options;
}

function template(tmpl, context, filter) {
  return (tmpl || '').replace(/\{([^}]+)\}/g, (m, key) => {
    // If key doesn't exists in the context we should keep template tag as is
    if (key in context) {
      return filter ? filter(context[key]) : context[key];
    }
    return m;
  });
}

function makeUrl(url, context) {
  return template(url, context, encodeURIComponent);
}

/**
 * Buttons
 */
const services = {
  facebook: {
    popupUrl: 'https://www.facebook.com/sharer.php?t={title}&u={url}',
    popupWidth: 600,
    popupHeight: 359,
  },
  twitter: {
    counters: false,
    popupUrl: 'https://twitter.com/intent/tweet?text={text}&url={url}',
    popupWidth: 670,
    popupHeight: 350,
    click() {
      // Add colon to improve readability
      if (!/[.?:\-–—]\s*$/.test(this.options.title)) {
        this.options.title += ':';
      }
      return true;
    },
  },
  pinterest: {
    counterUrl: `${protocol}//api.pinterest.com/v1/urls/count.json?url={url}&callback=?`,
    convertNumber(data) {
      return data.count;
    },
    popupUrl: 'https://pinterest.com/pin/create/button/?url={url}&description={text}&media={media}',
    popupWidth: 740,
    popupHeight: 550,
  },
  vkontakte: {
    counterUrl: 'https://vk.com/share.php?act=count&url={url}&index={index}',
    counter(jsonUrl, deferred) {
      const options = services.vkontakte;
      if (!options._) {
        options._ = [];
        if (!window.VK) {
          window.VK = {};
        }
        window.VK.Share = {
          count(idx, number) {
            options._[idx].resolve(number);
          },
        };
      }

      const index = options._.length;
      options._.push(deferred);
      $.getScript(makeUrl(jsonUrl, { index })).fail(deferred.reject);
    },
    popupUrl: 'https://vk.com/share.php?url={url}&title={title}&comment={excerpt}',
    popupWidth: 655,
    popupHeight: 450,
  },
  odnoklassniki: {
    counterUrl: `${protocol}//connect.ok.ru/dk?st.cmd=extLike&ref={url}&uid={index}`,
    counter(jsonUrl, deferred) {
      const options = services.odnoklassniki;
      if (!options._) {
        options._ = [];
        if (!window.ODKL) {
          window.ODKL = {};
        }
        window.ODKL.updateCount = function (idx, number) {
          options._[idx].resolve(number);
        };
      }

      const index = options._.length;
      options._.push(deferred);
      $.getScript(makeUrl(jsonUrl, { index })).fail(deferred.reject);
    },
    popupUrl: 'https://connect.ok.ru/offer?url={url}',
    popupWidth: 580,
    popupHeight: 336,
  },
  mailru: {
    counterUrl: `${protocol}//connect.mail.ru/share_count?url_list={url}&callback=1&func=?`,
    convertNumber(data) {
      let result = '';

      Object.keys(data).forEach((url) => {
        if (!result && data[url] && data[url].shares) {
          result = data[url].shares;
        }
      });

      return result;
    },
    popupUrl: 'https://connect.mail.ru/share?share_url={url}&title={title}',
    popupWidth: 492,
    popupHeight: 500,
  },
  linkedin: {
    popupUrl:
      'https://www.linkedin.com/shareArticle?title={title}&url={url}&summary={excerpt}&mini=true',
    popupWidth: 500,
    popupHeight: 550,
  },
  tumblr: {
    popupUrl:
      'https://www.tumblr.com/widgets/share/tool?canonicalUrl={url}&title={title}&caption={excerpt}&posttype=link',
    popupWidth: 500,
    popupHeight: 550,
  },
  skype: {
    popupUrl: 'https://web.skype.com/share?url={url}&text={text}',
    popupWidth: 500,
    popupHeight: 550,
  },
  buffer: {
    popupUrl: 'https://buffer.com/add?url={url}&text={text}',
    popupWidth: 500,
    popupHeight: 550,
  },
  pocket: {
    popupUrl: 'https://getpocket.com/save?url={url}',
    popupWidth: 500,
    popupHeight: 550,
  },
  xing: {
    popupUrl: 'https://www.xing.com/spi/shares/new?url={url}',
    popupWidth: 500,
    popupHeight: 550,
  },
  reddit: {
    popupUrl: 'https://www.reddit.com/submit?title={title}&url={url}',
    popupWidth: 500,
    popupHeight: 550,
  },
  flipboard: {
    popupUrl: 'https://share.flipboard.com/bookmarklet/popout?v=2&title={text}&url={url}',
    popupWidth: 500,
    popupHeight: 550,
  },
  delicious: {
    popupUrl: 'https://del.icio.us/post?url={url}&title={title}',
    popupWidth: 500,
    popupHeight: 550,
  },
  amazon: {
    popupUrl: 'https://www.amazon.com/gp/wishlist/static-add?u={url}&t={title}',
    popupWidth: 500,
    popupHeight: 550,
  },
  digg: {
    popupUrl: 'https://digg.com/submit?title={text}&url={url}',
    popupWidth: 500,
    popupHeight: 550,
  },
  evernote: {
    popupUrl: 'https://www.evernote.com/clip.action?url={url}&title={text}',
    popupWidth: 500,
    popupHeight: 550,
  },
  blogger: {
    popupUrl: 'https://www.blogger.com/blog-this.g?u={url}&n={title}&t={excerpt}',
    popupWidth: 500,
    popupHeight: 550,
  },
  yahoo: {
    popupUrl: 'https://compose.mail.yahoo.com/?body={text}%20{url}&subject={title}',
    popupWidth: 500,
    popupHeight: 550,
  },
  whatsapp: {
    popupUrl: 'https://api.whatsapp.com/send?text={text}%20{url}',
    popupWidth: 500,
    popupHeight: 550,
  },
  viber: {
    popupUrl: 'viber://forward?text={text}%20{url}',
    popupWidth: 500,
    popupHeight: 550,
  },
  telegram: {
    popupUrl: 'https://t.me/share/url?url={url}&text={text}',
    popupWidth: 500,
    popupHeight: 550,
  },
  mix: {
    popupUrl: 'https://mix.com/mixit?su=submit&url={url}',
    popupWidth: 850,
    popupHeight: 735,
  },
  diaspora: {
    popupUrl: 'https://share.diasporafoundation.org/?title={title}&url={url}',
    popupWidth: 500,
    popupHeight: 550,
  },
  line: {
    popupUrl: 'https://lineit.line.me/share/ui?url={url}&text={text}',
    popupWidth: 500,
    popupHeight: 550,
  },
  renren: {
    popupUrl:
      'http://widget.renren.com/dialog/share?resourceUrl={url}&srcUrl={url}&title={title}&description={excerpt}',
    popupWidth: 500,
    popupHeight: 550,
  },
  wechat: {
    popupUrl: `${socialityData.site_url}?sociality_share_wechat=1&url={url}`,
    popupWidth: 400,
    popupHeight: 650,
  },
  weibo: {
    popupUrl: 'http://service.weibo.com/share/share.php?url={url}&title={title}',
    popupWidth: 650,
    popupHeight: 320,
  },
  'tencent-weibo': {
    popupUrl: 'http://v.t.qq.com/share/share.php?url={url}&title={title}',
    popupWidth: 650,
    popupHeight: 320,
  },

  // Deprecated.
  google_plus: {
    popupUrl: 'https://plus.google.com/share?url={url}',
    popupWidth: 500,
    popupHeight: 550,
  },
  stumbleupon: {
    popupUrl: 'http://www.stumbleupon.com/submit?url={url}',
    popupWidth: 500,
    popupHeight: 550,
  },
};

/**
 * Counters manager
 */
const counters = {
  promises: {},
  fetch(service, url, extraOptions) {
    if (!counters.promises[service]) {
      counters.promises[service] = {};
    }
    const servicePromises = counters.promises[service];

    if (!extraOptions.forceUpdate && servicePromises[url]) {
      return servicePromises[url];
    }

    const options = $.extend({}, services[service], extraOptions);
    const deferred = $.Deferred();
    const jsonUrl = options.counterUrl && makeUrl(options.counterUrl, { url });

    if (jsonUrl && $.isFunction(options.counter)) {
      options.counter(jsonUrl, deferred);
    } else if (options.counterUrl) {
      $.getJSON(jsonUrl)
        .done((data) => {
          try {
            let number = data;
            if ($.isFunction(options.convertNumber)) {
              number = options.convertNumber(data);
            }
            deferred.resolve(number);
          } catch (e) {
            deferred.reject();
          }
        })
        .fail(deferred.reject);
    } else {
      deferred.reject();
    }

    servicePromises[url] = deferred.promise();
    return servicePromises[url];
  },
};

/*
 * Button
 */
function Button(widget, options) {
  this.widget = widget;
  this.options = $.extend({}, options);
  this.detectService();
  if (this.service) {
    this.init();
  }
}

Button.prototype = {
  init() {
    this.getPopupUrl = $.proxy(this.getPopupUrl, this);
    this.click = $.proxy(this.click, this);
    this.initCounter = $.proxy(this.initCounter, this);

    this.detectParams();

    // set link href.
    const { options } = this;
    if (options.popupUrl) {
      this.widget.attr('href', this.getPopupUrl());
    }

    // click action.
    this.widget.on('click', this.click);

    setTimeout(this.initCounter, 0);
  },

  update(options) {
    $.extend(this.options, { forceUpdate: false }, options);
    this.widget.find(`.${prefix}-counter`).html('');
    this.initCounter();
  },

  detectService() {
    const service = this.widget.data('share');
    if (!service) {
      return;
    }
    this.service = service;
    $.extend(this.options, services[service]);
  },

  detectParams() {
    const data = this.widget.data();

    // Custom page counter URL or number
    if (data.counter) {
      const number = parseInt(data.counter, 10);
      if (Number.isNaN(number)) {
        this.options.counterUrl = data.counter;
      } else {
        this.options.counterNumber = number;
      }
    }

    // Custom page title
    if (data.title) {
      this.options.title = data.title;
    }

    // Custom page URL
    if (data.url) {
      this.options.url = data.url;
    }

    // Custom page Media
    if (data.media) {
      this.options.media = data.media;
    }

    // Custom page Excerpt
    if (data.excerpt) {
      this.options.excerpt = data.excerpt;
    }

    // Custom page Text
    if (data.text) {
      this.options.text = data.text;
    }
  },

  initCounter() {
    if (this.options.counters) {
      if (this.options.counterNumber) {
        this.updateCounter(this.options.counterNumber);
      } else {
        const extraOptions = {
          counterUrl: this.options.counterUrl,
          forceUpdate: this.options.forceUpdate,
        };
        counters
          .fetch(this.service, this.options.url, extraOptions)
          .always($.proxy(this.updateCounter, this));
      }
    }
  },

  updateCounter(number) {
    number = parseInt(number, 10) || 0;

    if (!number && !this.options.zeroes) {
      number = '';
    }

    this.widget.find(`.${prefix}-counter`).html(number);

    this.widget.trigger(`counter.${prefix}`, [this.service, number]);
  },

  click(e) {
    const { options } = this;
    let process = true;
    if ($.isFunction(options.click)) {
      process = options.click.call(this, e);
    }
    if (process) {
      this.openPopup(this.getPopupUrl(), {
        width: options.popupWidth,
        height: options.popupHeight,
      });
    }
    return false;
  },

  getPopupUrl() {
    const { options } = this;
    const url = makeUrl(options.popupUrl, {
      url: options.url,
      title: options.title,
      media: options.media,
      excerpt: options.excerpt,
      text: options.text,
    });

    const params = $.param($.extend(this.widget.data(), this.options.data));
    if ($.isEmptyObject(params)) {
      return url;
    }
    const glue = url.indexOf('?') === -1 ? '?' : '&';
    return url + glue + params;
  },

  openPopup(url, params) {
    const dualScreenLeft = window.screenLeft;
    const dualScreenTop = window.screenTop;
    const width = window.innerWidth;
    const height = window.innerHeight;

    const left = Math.round(width / 2 - params.width / 2) + dualScreenLeft;
    let top = 0;
    if (height > params.height) {
      top = Math.round(height / 3 - params.height / 2) + dualScreenTop;
    }

    const win = window.open(
      url,
      `sl_${this.service}`,
      `left=${left},top=${top},` +
        `width=${params.width},height=${params.height},personalbar=0,toolbar=0,scrollbars=1,resizable=1`
    );
    if (win) {
      win.focus();
      this.widget.trigger(`popup_opened.${prefix}`, [this.service, win]);

      const timer = setInterval(
        $.proxy(function () {
          if (!win.closed) {
            return;
          }
          clearInterval(timer);
          this.widget.trigger(`popup_closed.${prefix}`, this.service);
        }, this),
        this.options.popupCheckInterval
      );
    } else {
      location.href = url;
    }
  },
};

/*
 * Sociality Share
 */
function SocialityShare(container, options) {
  this.container = container;
  this.options = options;
  this.init();
}

SocialityShare.prototype = {
  init() {
    this.countersLeft = 0;
    this.number = 0;
    this.container.on(`counter.${prefix}`, $.proxy(this.updateCounter, this));

    const buttons = this.container.find(`.${prefix}-button`);

    this.buttons = [];
    buttons.each(
      $.proxy(function (idx, elem) {
        const button = new Button($(elem), this.options);
        this.buttons.push(button);
        if (button.options.counterUrl) {
          this.countersLeft += 1;
        }
      }, this)
    );

    if (this.options.counters) {
      this.timeout = setTimeout($.proxy(this.ready, this, true), this.options.timeout);
    }
  },
  update(options) {
    if (!options.forceUpdate && options.url === this.options.url) {
      return;
    }

    // Reset counters
    this.number = 0;
    this.countersLeft = this.buttons.length;
    if (this.widget) {
      this.widget.find(`.${prefix}-counter`).html('');
    }

    // Update options
    $.extend(this.options, options);
    for (let buttonIdx = 0; buttonIdx < this.buttons.length; buttonIdx += 1) {
      this.buttons[buttonIdx].update(options);
    }
  },
  updateCounter(e, service, number) {
    number = number || 0;

    if (number || this.options.zeroes) {
      this.number += number;
    }

    this.countersLeft -= 1;

    if (this.countersLeft === 0) {
      this.ready();
    }
  },
  ready(silent) {
    if (this.timeout) {
      clearTimeout(this.timeout);
    }
    if (!silent) {
      this.container.trigger(`ready.${prefix}`, this.number);
    }
  },
};

/*
 * jQuery plugin
 */
$.fn.socialityShare = function (options) {
  return this.each(function () {
    const elem = $(this);
    let instance = elem.data(prefix);
    if (instance) {
      if ($.isPlainObject(options)) {
        instance.update(options);
      }
    } else {
      instance = new SocialityShare(
        elem,
        $.extend({}, $.fn.socialityShare.defaults, options, dataToOptions(elem))
      );
      elem.data(prefix, instance);
    }
  });
};

$.fn.socialityShare.defaults = {
  url: window.location.href.replace(window.location.hash, ''),
  title: document.title,
  media: '',
  excerpt: '',
  text: '',
  counters: true,
  zeroes: false,
  timeout: 10000, // Show counters after this amount of time even if they aren’t ready
  popupCheckInterval: 500,
};

/**
 * Auto initialization
 */
$(() => {
  $(`.${prefix}`).socialityShare();
});
