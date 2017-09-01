/**
 * Sociality Share
 *
 * Based on Social Likes
 * http://sapegin.github.com/social-likes
 */

/* global define:false, socialLikesButtons:false */

(function(factory) {  // Try to register as an anonymous AMD module
    if (typeof define === 'function' && define.amd) {
        define(['jquery'], factory);
    }
    else {
        factory(jQuery);
    }
}(function($, undefined) {
    'use strict';

    var prefix = 'sociality-share';
    var protocol = location.protocol === 'https:' ? 'https:' : 'http:';

    /**
     * Buttons
     */
    var services = {
        facebook: {
            counterUrl: 'https://graph.facebook.com/?id={url}',
            convertNumber: function(data) {
                return data.share.share_count;
            },
            popupUrl: 'https://www.facebook.com/sharer/sharer.php?u={url}',
            popupWidth: 600,
            popupHeight: 359
        },
        twitter: {
            counters: false,
            popupUrl: 'https://twitter.com/intent/tweet?url={url}&text={title}',
            popupWidth: 600,
            popupHeight: 250,
            click: function() {
                // Add colon to improve readability
                if (!/[.?:\-–—]\s*$/.test(this.options.title)) {
                    this.options.title += ':';
                }
                return true;
            }
        },
        google_plus: {
            counterUrl: protocol + '//share.yandex.ru/gpp.xml?url={url}&callback=?',
            convertNumber: function(number) {
                return parseInt(number.replace(/\D/g, ''), 10);
            },
            popupUrl: 'https://plus.google.com/share?url={url}',
            popupWidth: 500,
            popupHeight: 550
        },
        pinterest: {
            counterUrl: protocol + '//api.pinterest.com/v1/urls/count.json?url={url}&callback=?',
            convertNumber: function(data) {
                return data.count;
            },
            popupUrl: 'https://pinterest.com/pin/create/button/?url={url}&description={title}',
            popupWidth: 740,
            popupHeight: 550
        },
        vkontakte: {
            counterUrl: 'https://vk.com/share.php?act=count&url={url}&index={index}',
            counter: function(jsonUrl, deferred) {
                var options = services.vkontakte;
                if (!options._) {
                    options._ = [];
                    if (!window.VK) {
                        window.VK = {};
                    }
                    window.VK.Share = {
                        count: function(idx, number) {
                            options._[idx].resolve(number);
                        }
                    };
                }

                var index = options._.length;
                options._.push(deferred);
                $.getScript(makeUrl(jsonUrl, { index: index }))
                    .fail(deferred.reject);
            },
            popupUrl: 'https://vk.com/share.php?url={url}&title={title}',
            popupWidth: 655,
            popupHeight: 450
        },
        odnoklassniki: {
            counterUrl: protocol + '//connect.ok.ru/dk?st.cmd=extLike&ref={url}&uid={index}',
            counter: function(jsonUrl, deferred) {
                var options = services.odnoklassniki;
                if (!options._) {
                    options._ = [];
                    if (!window.ODKL) {
                        window.ODKL = {};
                    }
                    window.ODKL.updateCount = function(idx, number) {
                        options._[idx].resolve(number);
                    };
                }

                var index = options._.length;
                options._.push(deferred);
                $.getScript(makeUrl(jsonUrl, { index: index }))
                    .fail(deferred.reject);
            },
            popupUrl: 'https://connect.ok.ru/dk?st.cmd=WidgetSharePreview&service=odnoklassniki&st.shareUrl={url}',
            popupWidth: 580,
            popupHeight: 336
        },
        mailru: {
            counterUrl: protocol + '//connect.mail.ru/share_count?url_list={url}&callback=1&func=?',
            convertNumber: function(data) {
                for (var url in data) {
                    if (data.hasOwnProperty(url)) {
                        return data[url].shares;
                    }
                }
            },
            popupUrl: 'https://connect.mail.ru/share?share_url={url}&title={title}',
            popupWidth: 492,
            popupHeight: 500
        }
    };


    /**
     * Counters manager
     */
    var counters = {
        promises: {},
        fetch: function(service, url, extraOptions) {
            if (!counters.promises[service]) {
                counters.promises[service] = {};
            }
            var servicePromises = counters.promises[service];

            if (!extraOptions.forceUpdate && servicePromises[url]) {
                return servicePromises[url];
            }

            var options = $.extend({}, services[service], extraOptions);
            var deferred = $.Deferred();
            var jsonUrl = options.counterUrl && makeUrl(options.counterUrl, { url: url });

            if (jsonUrl && $.isFunction(options.counter)) {
                options.counter(jsonUrl, deferred);
            }
            else if (options.counterUrl) {
                $.getJSON(jsonUrl)
                    .done(function(data) {
                        try {
                            var number = data;
                            if ($.isFunction(options.convertNumber)) {
                                number = options.convertNumber(data);
                            }
                            deferred.resolve(number);
                        }
                        catch (e) {
                            deferred.reject();
                        }
                    })
                    .fail(deferred.reject);
            }
            else {
                deferred.reject();
            }

            servicePromises[url] = deferred.promise();
            return servicePromises[url];
        }
    };


    // jQuery plugin
    $.fn.socialityShare = function(options) {
        return this.each(function() {
            var elem = $(this);
            var instance = elem.data(prefix);
            if (instance) {
                if ($.isPlainObject(options)) {
                    instance.update(options);
                }
            }
            else {
                instance = new SocialityShare(elem, $.extend({}, $.fn.socialityShare.defaults, options, dataToOptions(elem)));
                elem.data(prefix, instance);
            }
        });
    };

    $.fn.socialityShare.defaults = {
        url: window.location.href.replace(window.location.hash, ''),
        title: document.title,
        counters: true,
        zeroes: false,
        timeout: 10000,  // Show counters after this amount of time even if they aren’t ready
        popupCheckInterval: 500
    };

    function SocialityShare(container, options) {
        this.container = container;
        this.options = options;
        this.init();
    }

    SocialityShare.prototype = {
        init: function() {
            this.countersLeft = 0;
            this.number = 0;
            this.container.on('counter.' + prefix, $.proxy(this.updateCounter, this));

            var buttons = this.container.find('.' + prefix + '-button');

            this.buttons = [];
            buttons.each($.proxy(function(idx, elem) {
                var button = new Button($(elem), this.options);
                this.buttons.push(button);
                if (button.options.counterUrl) {
                    this.countersLeft++;
                }
            }, this));

            if (this.options.counters) {
                this.timeout = setTimeout($.proxy(this.ready, this, true), this.options.timeout);
            }
        },
        update: function(options) {
            if (!options.forceUpdate && options.url === this.options.url) {
                return;
            }

            // Reset counters
            this.number = 0;
            this.countersLeft = this.buttons.length;
            if (this.widget) {
                this.widget.find('.' + prefix + '-counter').html('');
            }

            // Update options
            $.extend(this.options, options);
            for (var buttonIdx = 0; buttonIdx < this.buttons.length; buttonIdx++) {
                this.buttons[buttonIdx].update(options);
            }
        },
        updateCounter: function(e, service, number) {
            number = number || 0;

            if (number || this.options.zeroes) {
                this.number += number;
            }

            this.countersLeft--;

            if (this.countersLeft === 0) {
                this.ready();
            }
        },
        ready: function(silent) {
            if (this.timeout) {
                clearTimeout(this.timeout);
            }
            if (!silent) {
                this.container.trigger('ready.' + prefix, this.number);
            }
        }
    };


    function Button(widget, options) {
        this.widget = widget;
        this.options = $.extend({}, options);
        this.detectService();
        if (this.service) {
            this.init();
        }
    }

    Button.prototype = {
        init: function() {
            this.detectParams();
            this.widget.on('click', $.proxy(this.click, this));
            setTimeout($.proxy(this.initCounter, this), 0);
        },

        update: function(options) {
            $.extend(this.options, { forceUpdate: false }, options);
            this.widget.find('.' + prefix + '-counter').html('');
            this.initCounter();
        },

        detectService: function() {
            var service = this.widget.data('share');
            if (!service) {
                return;
            }
            this.service = service;
            $.extend(this.options, services[service]);
        },

        detectParams: function() {
            var data = this.widget.data();

            // Custom page counter URL or number
            if (data.counter) {
                var number = parseInt(data.counter, 10);
                if (isNaN(number)) {
                    this.options.counterUrl = data.counter;
                }
                else {
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
        },

        initCounter: function() {
            if (this.options.counters) {
                if (this.options.counterNumber) {
                    this.updateCounter(this.options.counterNumber);
                }
                else {
                    var extraOptions = {
                        counterUrl: this.options.counterUrl,
                        forceUpdate: this.options.forceUpdate
                    };
                    counters.fetch(this.service, this.options.url, extraOptions)
                        .always($.proxy(this.updateCounter, this));
                }
            }
        },

        updateCounter: function(number) {
            number = parseInt(number, 10) || 0;

            if (!number && !this.options.zeroes) {
                number = '';
            }

            this.widget.find('.' + prefix + '-counter').html(number);

            this.widget.trigger('counter.' + prefix, [this.service, number]);
        },

        click: function(e) {
            var options = this.options;
            var process = true;
            if ($.isFunction(options.click)) {
                process = options.click.call(this, e);
            }
            if (process) {
                var url = makeUrl(options.popupUrl, {
                    url: options.url,
                    title: options.title
                });
                url = this.addAdditionalParamsToUrl(url);
                this.openPopup(url, {
                    width: options.popupWidth,
                    height: options.popupHeight
                });
            }
            return false;
        },

        addAdditionalParamsToUrl: function(url) {
            var params = $.param($.extend(this.widget.data(), this.options.data));
            if ($.isEmptyObject(params)) {
                return url;
            }
            var glue = url.indexOf('?') === -1 ? '?' : '&';
            return url + glue + params;
        },

        openPopup: function(url, params) {
            var dualScreenLeft = window.screenLeft !== undefined ? window.screenLeft : screen.left;
            var dualScreenTop = window.screenTop !== undefined ? window.screenTop : screen.top;
            var width = window.innerWidth
                ? window.innerWidth
                : document.documentElement.clientWidth
                    ? document.documentElement.clientWidth
                    : screen.width
            ;
            var height = window.innerHeight
                ? window.innerHeight
                : document.documentElement.clientHeight
                    ? document.documentElement.clientHeight
                    : screen.height
            ;

            var left = Math.round(width / 2 - params.width / 2) + dualScreenLeft;
            var top = 0;
            if (height > params.height) {
                top = Math.round(height / 3 - params.height / 2) + dualScreenTop;
            }

            var win = window.open(url, 'sl_' + this.service, 'left=' + left + ',top=' + top + ',' +
                'width=' + params.width + ',height=' + params.height + ',personalbar=0,toolbar=0,scrollbars=1,resizable=1');
            if (win) {
                win.focus();
                this.widget.trigger('popup_opened.' + prefix, [this.service, win]);
                var timer = setInterval($.proxy(function() {
                    if (!win.closed) {
                        return;
                    }
                    clearInterval(timer);
                    this.widget.trigger('popup_closed.' + prefix, this.service);
                }, this), this.options.popupCheckInterval);
            }
            else {
                location.href = url;
            }
        }
    };


    /**
     * Helpers
     */

    // Camelize data-attributes
    function dataToOptions(elem) {
        function upper(m, l) {
            return l.toUpper();
        }
        var options = {};
        var data = elem.data();
        for (var key in data) {
            var value = data[key];
            if (value === 'yes') {
                value = true;
            }
            else if (value === 'no') {
                value = false;
            }
            options[key.replace(/-(\w)/g, upper)] = value;
        }
        return options;
    }

    function makeUrl(url, context) {
        return template(url, context, encodeURIComponent);
    }

    function template(tmpl, context, filter) {
        return tmpl.replace(/\{([^}]+)\}/g, function(m, key) {
            // If key doesn't exists in the context we should keep template tag as is
            return key in context ? (filter ? filter(context[key]) : context[key]) : m;
        });
    }


    /**
     * Auto initialization
     */
    $(document).on('ready.' + prefix, function () {
        $('.' + prefix).socialityShare();
    });
}));