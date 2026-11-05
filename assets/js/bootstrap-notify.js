/*
 * Bootstrap Notify: turns standard Bootstrap alerts into "Growl-like" notifications.
 * Based on bootstrap-notify 3.1.5 by Robert McIntosh (mouse0270), MIT License.
 * https://github.com/mouse0270/bootstrap-notify
 *
 * rewrite available at https://github.com/Mechazawa/bootstrap4-notify under MIT
 */

import $ from 'jquery';

const CONTEXTUAL_TYPES = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'];
const POSITION_PATTERN = /^(top|bottom)-(left|center|right)$/;
const ANIMATION_FALLBACK_MS = 600;

let defaults = {
    element: 'body',
    position: null,
    type: 'info',
    allow_dismiss: true,
    allow_duplicates: true,
    newest_on_top: false,
    showProgressbar: false,
    placement: {
        from: 'top',
        align: 'right',
    },
    offset: 20,
    spacing: 10,
    z_index: 1031,
    delay: 5000,
    timer: 1000,
    url_target: '_blank',
    mouse_over: null,
    animate: {
        enter: 'animated fadeInDown',
        exit: 'animated fadeOutUp',
    },
    onShow: null,
    onShown: null,
    onClose: null,
    onClosed: null,
    onClick: null,
    icon_type: 'class',
    template: '<div data-notify="container" class="col-11 col-sm-4 alert alert-{0}" role="alert"><button type="button" class="close" aria-label="Close" data-notify="dismiss"><span aria-hidden="true">&times;</span></button><span data-notify="icon"></span> <span data-notify="title">{1}</span> <span data-notify="message">{2}</span><div class="progress" data-notify="progressbar"><div class="progress-bar bg-{0}" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;"></div></div><a href="{3}" target="{4}" data-notify="url"></a></div>',
};

const invoke = (callback, context) => {
    if (typeof callback === 'function') {
        callback.call(context);
    }
};

// Parse through the DOM so equal markup compares equal (`<br>` and `<br />`).
const normalizeHtml = (html) => $('<div>').html(String(html)).html().trim();

class Notify {
    constructor(content, options) {
        const contentSettings = {
            content: {
                message: typeof content === 'object' ? content.message : content,
                title: content.title || '',
                icon: content.icon || '',
                url: content.url || '#',
                target: content.target || '-',
            },
        };

        this.settings = $.extend(true, {}, defaults, contentSettings, options);

        if (this.settings.content.target === '-') {
            this.settings.content.target = this.settings.url_target;
        }

        if (typeof this.settings.offset === 'number') {
            this.settings.offset = { x: this.settings.offset, y: this.settings.offset };
        }

        if (!this.settings.allow_duplicates && this.isDuplicate()) {
            return;
        }

        this.buildNotify();
        this.placement();
        this.bind();

        this.notify = {
            $ele: this.$ele,
            update: (command, value) => {
                const commands = typeof command === 'string' ? { [command]: value } : command;

                Object.entries(commands).forEach(([name, commandValue]) => this.applyUpdate(name, commandValue));

                this.reposition(this.$ele.outerHeight() + parseInt(this.settings.spacing) + parseInt(this.settings.offset.y));
            },
            close: () => this.close(),
        };
    }

    isDuplicate() {
        const { type, content } = this.settings;
        const title = normalizeHtml(content.title);
        const message = normalizeHtml(content.message);

        return $('[data-notify="container"]').toArray().some((element) => {
            const $element = $(element);

            return $element.find('[data-notify="title"]').html().trim() === title
                && $element.find('[data-notify="message"]').html().trim() === message
                && $element.hasClass(`alert-${type}`);
        });
    }

    get positionSelector() {
        const { from, align } = this.settings.placement;

        return `[data-notify-position="${from}-${align}"]:not([data-closing="true"])`;
    }

    applyUpdate(name, value) {
        switch (name) {
            case 'type':
                this.$ele.removeClass(`alert-${this.settings.type}`)
                    .find('[data-notify="progressbar"] > .progress-bar')
                    .removeClass(`bg-${this.settings.type}`);
                this.settings.type = value;
                this.$ele.addClass(`alert-${value}`)
                    .find('[data-notify="progressbar"] > .progress-bar')
                    .addClass(`bg-${value}`);
                break;
            case 'icon': {
                const $icon = this.$ele.find('[data-notify="icon"]');

                if (this.settings.icon_type.toLowerCase() === 'class') {
                    $icon.removeClass(this.settings.content.icon).addClass(value);
                } else {
                    ($icon.is('img') ? $icon : $icon.find('img')).attr('src', value);
                }

                this.settings.content.icon = value;
                break;
            }
            case 'progress':
                this.$ele.data('notify-delay', this.settings.delay - (this.settings.delay * (value / 100)));
                this.setProgress(value);
                break;
            case 'url':
                this.$ele.find('[data-notify="url"]').attr('href', value);
                break;
            case 'target':
                this.$ele.find('[data-notify="url"]').attr('target', value);
                break;
            default:
                this.$ele.find(`[data-notify="${name}"]`).html(value);
        }
    }

    setProgress(percent) {
        this.$ele.find('[data-notify="progressbar"] > div')
            .attr('aria-valuenow', percent)
            .css('width', `${percent}%`);
    }

    buildNotify() {
        const { allow_dismiss, content, icon_type, placement, showProgressbar, template, type, z_index } = this.settings;
        const values = [type, content.title, content.message, content.url, content.target];

        // `{n}` takes the n-th value; `{{n}}` stays as it is.
        this.$ele = $(template.replace(/\{\{\d\}\}|\{\d\}/g, (token) => (token.startsWith('{{') ? token : values[token[1]])))
            .attr('data-notify-position', `${placement.from}-${placement.align}`);

        const $dismiss = this.$ele.find('[data-notify="dismiss"]').css({
            position: 'absolute',
            right: '10px',
            top: '5px',
            zIndex: z_index + 2,
        });

        if (!allow_dismiss) {
            $dismiss.css('display', 'none');
        }

        if (!showProgressbar) {
            this.$ele.find('[data-notify="progressbar"]').remove();
        }

        if (content.icon) {
            const $icon = this.$ele.find('[data-notify="icon"]');

            if (icon_type.toLowerCase() === 'class') {
                $icon.addClass(content.icon);
            } else if ($icon.is('img')) {
                $icon.attr('src', content.icon);
            } else {
                $icon.append($('<img alt="Notify Icon">').attr('src', content.icon));
            }
        }

        if (content.url !== '#') {
            // A transparent background image keeps the overlay link clickable in old Internet Explorer.
            this.$ele.find('[data-notify="url"]').css({
                backgroundImage: 'url(data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7)',
                height: '100%',
                left: 0,
                position: 'absolute',
                top: 0,
                width: '100%',
                zIndex: z_index + 1,
            });
        }
    }

    placement() {
        const { settings } = this;
        const { from, align } = settings.placement;
        const css = {
            display: 'inline-block',
            margin: '0px auto',
            position: settings.position || (settings.element === 'body' ? 'fixed' : 'absolute'),
            transition: 'all .5s ease-in-out',
            zIndex: settings.z_index,
        };

        const offset = settings.newest_on_top
            ? settings.offset.y
            : $(this.positionSelector).toArray().reduce(
                (max, element) => Math.max(max, parseInt($(element).css(from)) + $(element).outerHeight() + parseInt(settings.spacing)),
                settings.offset.y,
            );

        css[from] = `${offset}px`;

        if (align === 'center') {
            css.left = 0;
            css.right = 0;
        } else if (align === 'left' || align === 'right') {
            css[align] = `${settings.offset.x}px`;
        }

        this.$ele.css(css).addClass(settings.animate.enter).appendTo(settings.element);

        if (settings.newest_on_top) {
            this.reposition(parseInt(offset) + parseInt(settings.spacing) + this.$ele.outerHeight());
        }

        invoke(settings.onShow, this.$ele);

        this.afterAnimation(() => {
            this.$ele.removeClass(settings.animate.enter);
            invoke(settings.onShown, this.$ele[0]);
        }, () => invoke(settings.onShown, this.$ele[0]));
    }

    // Runs `onEnd` when the CSS animation ends, or `onNoAnimation` when no animation starts in time.
    afterAnimation(onEnd, onNoAnimation) {
        let hasAnimation = false;

        this.$ele
            .one('animationstart', () => {
                hasAnimation = true;
            })
            .one('animationend', onEnd);

        setTimeout(() => {
            if (!hasAnimation) {
                onNoAnimation();
            }
        }, ANIMATION_FALLBACK_MS);
    }

    bind() {
        const { settings } = this;
        const $dismiss = this.$ele.find('[data-notify="dismiss"]');

        $dismiss.on('click', () => this.close());

        if (typeof settings.onClick === 'function') {
            this.$ele.on('click', (event) => {
                if (!$(event.target).closest($dismiss).length) {
                    settings.onClick.call(event.currentTarget, event);
                }
            });
        }

        this.$ele
            .data('data-hover', 'false')
            .on('mouseover', () => this.$ele.data('data-hover', 'true'))
            .on('mouseout', () => this.$ele.data('data-hover', 'false'));

        if (settings.delay <= 0) {
            return;
        }

        this.$ele.data('notify-delay', settings.delay);
        this.timer = setInterval(() => {
            const remaining = parseInt(this.$ele.data('notify-delay')) - settings.timer;

            if (this.$ele.data('data-hover') === 'false' || settings.mouse_over !== 'pause') {
                this.$ele.data('notify-delay', remaining);
                this.setProgress(((settings.delay - remaining) / settings.delay) * 100);
            }

            if (remaining <= -settings.timer) {
                this.close();
            }
        }, settings.timer);
    }

    close() {
        if (this.$ele.attr('data-closing') === 'true') {
            return;
        }

        clearInterval(this.timer);

        const { settings } = this;
        const position = parseInt(this.$ele.css(settings.placement.from));

        this.$ele.attr('data-closing', 'true').addClass(settings.animate.exit);
        this.reposition(position);

        invoke(settings.onClose, this.$ele);

        const remove = () => {
            this.$ele.remove();
            invoke(settings.onClosed, this.$ele[0]);
        };

        this.afterAnimation(remove, remove);
    }

    reposition(position) {
        const { from } = this.settings.placement;
        const $elements = this.settings.newest_on_top
            ? this.$ele.prevAll(this.positionSelector)
            : this.$ele.nextAll(this.positionSelector);

        $elements.toArray().reduce((offset, element) => {
            $(element).css(from, offset);

            return parseInt(offset) + parseInt(this.settings.spacing) + $(element).outerHeight();
        }, position);
    }
}

export const notify = (content, options) => new Notify(content, options).notify;

export const notifyDefaults = (options) => {
    defaults = $.extend(true, {}, defaults, options);

    return defaults;
};

export const notifyClose = (selector) => {
    let $containers = $();

    if (selector === undefined || selector === 'all') {
        $containers = $('[data-notify]');
    } else if (CONTEXTUAL_TYPES.includes(selector)) {
        $containers = $(`.alert-${selector}[data-notify]`);
    } else if (POSITION_PATTERN.test(selector)) {
        $containers = $(`[data-notify-position="${selector}"]`);
    } else if (selector) {
        $containers = $(`${selector}[data-notify]`);
    }

    $containers.find('[data-notify="dismiss"]').trigger('click');
};

export const notifyCloseExcept = (selector) => {
    const except = CONTEXTUAL_TYPES.includes(selector) ? `.alert-${selector}` : selector;

    $('[data-notify]').not(except).find('[data-notify="dismiss"]').trigger('click');
};

$.extend({ notify, notifyDefaults, notifyClose, notifyCloseExcept });
