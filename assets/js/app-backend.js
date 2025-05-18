/*global jQuery, ko, horaro, horaroTimeFormat, moment */

jQuery(function($) {
	'use strict';

	var scheduleColumns, scheduleID, scheduleStart, scheduleTZ, scheduleSetupTime, viewModel, items, columns, maxItems;

	// init CSRF token information

	var csrfToken     = $('meta[name="csrf_token"]').attr('content');
	var csrfTokenName = $('meta[name="csrf_token_name"]').attr('content');

	// init date and time pickers

	$('#start_date').pickadate({
		formatSubmit: 'yyyy-mm-dd',
		hiddenName: true
	});

	$('#start_time').pickatime({
		interval: 15,
		formatSubmit: 'HH:i',
		format: horaroTimeFormat,
		formatLabel: horaroTimeFormat,
		hiddenName: true
	});

	// setup back buttons

	$('body').on('click', '.h-back-btn', function() {
		history.back();
		return false;
	});

	// setup Select2

	$('select.h-fancy').select2();

	// insert safety-guard for some forms

	$('form.h-confirmation').on('submit', function() {
		return confirm($(this).data('confirmation') || 'Are you sure?');
	});

	// render localized times

	$('time.h-fancy').each(function() {
		// do not convert into the user's timezone, but leave the given one
		// (i.e. the schedule's timezone)
		$(this).text(moment.parseZone($(this).attr('datetime')).format('llll'));
	});

	// render flash messages

	function growl(msg) {
		$.notify({ message: msg }, growlOpt);
	}

	if ($('#h-flashes').length > 0) {
		var growlOpt = {
			type:      'info',
			placement: {from: 'top', align: 'center'},
			offset:    26,
			width:     350,
			delay:     3000,
			spacing:   5,
			animate:   { enter: '', exit: '' }
		};

		var flashes = JSON.parse($('#h-flashes').text());

		for (var flashType in flashes) {
			growlOpt.type = flashType;

			flashes[flashType].forEach(growl);
		}
	}

	// prepare X-Editable

	$.fn.editable.defaults.mode = 'popup';
	$.fn.editableform.buttons =
		'<button type="submit" class="btn btn-primary btn-xs editable-submit">'+
			'<i class="fa fa-check"></i>'+
		'</button>'+
		'<button type="button" class="btn btn-default btn-xs editable-cancel">'+
			'<i class="fa fa-ban"></i>'+
		'</button>';

	// markdown helper for inline content

	function inlineMarkdown(markup) {
		var parser = new Remarkable('commonmark');
		parser.set({ html: false, xhtmlOut: false });

		// we don't want this stuff in our inline content
		parser.block.ruler.disable(['code', 'fences', 'blockquote', 'hr', 'list', 'footnote', 'heading', 'lheading', 'htmlblock', 'table', 'deflist']);
		parser.inline.ruler.disable(['newline', 'htmltag']);

		var rendered = parser.render(markup);

		// strip paragraphs
		rendered = rendered.replace(/<\/?p>/g, '');

		// strip images (can't be disabled easily in Remarkable, just like paragraphs)
		rendered = rendered.replace(/<img.+?>/g, '');

		return rendered;
	}

	// setup Knockout bindings

	ko.bindingHandlers.activate = {
		init: function(element, valueAccessor, allBindings, viewModel, bindingContext) {
			var value = valueAccessor();

			$(element).keydown(function(e) {
				if (e.keyCode === 13 /* return */ || e.keyCode === 32 /* space */) {
					e.preventDefault();
					e.stopPropagation();

					value.call(bindingContext['$data'], bindingContext['$data'], e);
				}
			});
		}
	};

	function parseLength(str) {
	var parts = str.split(':');

	// 'HH:MM:SS'
	if (parts.length >= 3) {
		return parts[0] * 3600 + parts[1] * 60 + parseInt(parts[2], 10);
	}

	// 'HH:MM'
	if (parts.length === 2) {
		return parts[0] * 3600 + parts[1] * 60;
	}

	// 'MM'
	if (parts.length === 1) {
		return parts[0] * 60;
	}

	return 0;
}

function findModelByID(models, id) {
	for (var len = models.length, i = 0; i < len; ++i) {
		if (models[i].id() === id) {
			return models[i];
		}
	}

	return null;
}

function hasNewModel(models) {
	return models.filter(function(model) {
		return model.id() === -1;
	}).length > 0;
}

function mirrorColumnWidths(sourceTable, targets) {
	var sources = $('tr:first > *', sourceTable);

	for (var i = 0, len = sources.length; i < len; ++i) {
		var w = $(sources[i]).innerWidth();

		$(targets[i]).css({
			maxWidth: w,
			width: w
		});
	}
}

	function SpatialNavigation(root) {
	var self  = this;
	var codes = {
		KEY_LEFT:  37,
		KEY_UP:    38,
		KEY_RIGHT: 39,
		KEY_DOWN:  40
	};

	self.root   = root;
	self.addBtn = function() { $('#h-add-model') };

	root.on('keydown', function(e) {
		var target = $(e.target);

		// do nothing on elements we don't care about
		if (!target.is('.editable') && !target.is('.h-co button')) {
			return;
		}

		var interesting = false;

		for (var c in codes) {
			if (codes[c] === e.keyCode) {
				e.preventDefault();
				e.stopPropagation();
				interesting = true;
				break;
			}
		}

		if (!interesting) {
			return;
		}

		var row   = target.closest('tbody');
		var rows  = root.find('tbody');
		var nodes = row.find('.h-primary a:visible, .h-primary .h-co button:visible');
		var x     = nodes.index(target);
		var y     = rows.index(row);
		var maxX  = nodes.length - 1;
		var maxY  = rows.length - 1;
		var newX  = x;
		var newY  = y;

		switch (e.keyCode) {
			case codes.KEY_RIGHT: newX++; break;
			case codes.KEY_DOWN:  newY++; break;
			case codes.KEY_LEFT:  newX--; break;
			case codes.KEY_UP:    newY--; break;
		}

		// focus the add button when pressing down in the last row
		if (newY > maxY) {
			$('#h-add-model').focus();
			return;
		}

		if (newX > maxX) {
			return;
		}

		if (newY !== y) {
			nodes = $(rows[newY]).find('.h-primary a:visible, .h-primary button:visible');
		}

		$(nodes[newX]).focus();
	});

	$('body').on('keydown', '#h-add-model', function(e) {
		if (e.keyCode !== codes.KEY_UP) {
			return false;
		}

		e.preventDefault();
		e.stopPropagation();

		var row = root.find('tbody:last');

		if (row.length > 0) {
			row.find('a:visible:first').focus();
		}
	});
}


	function Item(id, length, columns, pos) {
	var self = this;

	// setup simple data properties

	self.id         = ko.observable(id);
	self.length     = ko.observable(length);
	self.scheduled  = ko.observable();      // will be set by calculateSchedule()
	self.dateSwitch = ko.observable(false); // will be set by calculateSchedule()
  self.setupTime = ko.observable(0); // will be set by calculateSchedule()

	// setup simple properties for the schedule columns

	scheduleColumns.forEach(function(colID) {
		var name  = 'col_' + colID;
		var value = '';

		if (columns.hasOwnProperty(colID)) {
			value = columns[colID];
		}

		self[name] = ko.observable(value);
	});

	// setup properties for managing app state

	self.position  = ko.observable(parseInt(pos, 10));
	self.suspended = false;
	self.nextFocus = false;
	self.expanded  = ko.observable(false);
	self.deleting  = ko.observable(false);
	self.busy      = ko.observable(false);
	self.errors    = ko.observable(false);

	// computed properties

	self.formattedLength = ko.pureComputed({
		owner: self,
		read: function() {
			return moment.unix(self.length()).utc().format('HH:mm:ss');
		},
		write: function(value) {
			self.length(parseLength(value));
		}
	}, self);

	self.formattedSchedule = ko.pureComputed(function() {
		return moment.unix(self.scheduled() / 1000).utcOffset(scheduleTZ).format('LT');
	}, self);

	self.rowClass = ko.pureComputed(function() {
		if (self.busy()) {
			return 'warning';
		}

		if (self.errors()) {
			return 'danger h-has-errors';
		}

		if (self.deleting()) {
			return 'danger';
		}

		return '';
	}, self);

	self.bodyClass = function() {
		return 'h-item ' + (this.$context.$index() % 2 === 1 ? 'h-odd' : 'h-even');
	};

	self.first = ko.pureComputed(function() {
		return self.position() <= 1;
	}, self);

	self.last = function() {
		return self.position() >= viewModel.items().length;
	};

	// subscribers

	self.length.subscribe(function(newValue) {
		self.sync({length: newValue});
		viewModel.calculateSchedule(0);
	});

	scheduleColumns.forEach(function(colID) {
		var name = 'col_' + colID;

		self[name].subscribe(function(newValue) {
			var columns = {};
			columns[colID] = newValue;

			self.sync({columns: columns});
		});
	});

	self.sync = function(patch) {
		if (self.suspended) {
			return;
		}

		var itemID = self.id();
		var isNew  = itemID === -1;
		var method = 'POST';
		var url    = '';

		if (isNew) {
			url = '/-/schedules/' + scheduleID + '/items';

			// When creating an element, send all non-empty fields instead of just the one that
			// has been changed (i.e. the one in patch); this makes sure the length gets sent
			// along when someone edits a content column first (without the length, the request
			// would always fail, because items with length=0 are not allowed).
			patch = {
				length: self.length(),
				columns: {}
			};

			scheduleColumns.forEach(function(colID) {
				var key   = 'col_' + colID;
				var value = self[key]();

				patch.columns[colID] = value;
			});
		}
		else {
			url = '/-/schedules/' + scheduleID + '/items/' + itemID + '?_method=PATCH';
		}

		self.busy(true);

		patch[csrfTokenName] = csrfToken;

		$.ajax({
			type: method,
			url: url,
			dataType: 'json',
			contentType: 'application/json',
			data: JSON.stringify(patch),
			success: function(result) {
				self.suspended = true;

				self.id(result.data.id);
				self.length(result.data.length);
				self.errors(false);

				scheduleColumns.forEach(function(id) {
					var key   = 'col_' + id;
					var value = id in result.data.columns ? result.data.columns[id] : '';

					self[key](value);
				});

				self.suspended = false;

				if (self.nextFocus) {
					$('#h-add-model').focus();
					self.nextFocus = false;
				}
			},
			error: function(result) {
        const errors = {};
        const violations = result.responseJSON.violations ?? [];

        violations.forEach((violation) => {
          if (!errors[violation.propertyPath]) {
            errors[violation.propertyPath] = [];
          }

          errors[violation.propertyPath].push(violation.title);
        })

				self.errors(errors);
			},
			complete: function() {
				self.busy(false);
			}
		});
	};

	self.deleteItem = function() {
		if (self.suspended) {
			return;
		}

		var itemID = self.id();
		var data   = {};

		data[csrfTokenName] = csrfToken;

		self.busy(true);

		$.ajax({
			type: 'POST',
			url: '/-/schedules/' + scheduleID + '/items/' + itemID + '?_method=DELETE',
			dataType: 'json',
			contentType: 'application/json',
			data: JSON.stringify(data),
			success: function() {
				viewModel.items.remove(self);
			},
			complete: function() {
				self.busy(false);
			}
		});
	};

	// behaviours

	self.toggle = function(item, event) {
		self.expanded(!self.expanded());
		$(event.target).parent().find('button:visible').focus();
	};

	function move(event, direction) {
		var scheduler = $(event.target).closest('table');
		var newPos    = self.position() + (direction === 'up' ? -1 : 1);

		viewModel.move(self.id(), newPos);

		// find the new DOM node for the just pressed button and focus it, if possible
		// (i.e. we're not first or last)
		var row = scheduler.find('tbody[data-itemid="' + self.id() + '"]');
		var btn = row.find('button.move-' + direction);

		if (btn.is('.disabled')) {
			btn = row.find('button.move-' + (direction === 'up' ? 'down' : 'up'));
		}

		btn.focus();
	}

	self.moveUp = function(item, event) {
		move(event, 'up');
	};

	self.moveDown = function(item, event) {
		move(event, 'down');
	};

	self.confirmDelete = function(item, event) {
		var parent = $(event.target).parent();
		self.deleting(true);
		parent.find('.btn-default').focus();
	};

	self.cancelDelete = function(item, event) {
		var parent = $(event.target).parent();
		self.deleting(false);
		parent.find('.btn-danger').focus();
	};

	self.doDelete = function(item, event) {
		var row  = $(event.target).closest('tbody');
		var next = row.next('tbody');

		if (next.length === 0) {
			next = row.prev('tbody');

			if (next.length === 0) {
				next = $('#h-add-model');
			}
		}

		if (next.is('tbody')) {
			next = next.find('button:visible:last');
		}

		self.deleteItem();
		next.focus();
	};

	self.onEditableHidden = function(event, reason) {
		var
			me      = $(this),
			root    = me.closest('table'),
			links   = root.find('a.editable:visible'),
			selfIdx = links.index(me),
			next    = (selfIdx < (links.length - 1)) ? $(links[selfIdx+1]) : $('#h-add-model');

		// advance to the next editable
		if (reason === 'save' || reason === 'nochange') {
			if (next.is('.editable')) {
				next.editable('show');
			}
			else {
				next.focus();

				// in case this saving triggers an ajax call to create the element,
				// the add button is still disabled right now. We set a flag to let
				// the success handler of the create call do the focussing.
				self.nextFocus = true;
			}
		}
		else {
			me.focus();
		}
	};

	self.getDisplayText = function(value) {
		if (typeof value === 'string' && value.length > 0) {
			var markup = inlineMarkdown(value);

			// Turn links into glorified spans, as they won't work anyway because we have click listeners
			// set up for X-Editable.
			var m      = $('<div>' + markup + '</div>');
			var suffix = ' <sup><i class="fa fa-external-link"></i></sup>';

			m.find('a').each(function() {
				var link   = $(this);
				var target = link.attr('href');
				var text   = link.text();

				link.replaceWith($('<span>').attr('title', 'link to ' + target).addClass('h-link').text(text).append(suffix));
			});

			$(this).html(m.html());
		}
		else {
			$(this).html('');
		}
	};
}

	function ItemsViewModel(items) {
	var self = this;

	self.items = ko.observableArray(items);

	// helper

	function findItem(itemID) {
		return findModelByID(self.items(), itemID);
	}

	// computed properties

	self.hasNewItem = ko.pureComputed(function() {
		return hasNewModel(self.items());
	}, self);

	self.isFull = ko.pureComputed(function() {
		return self.items().length >= maxItems;
	});

	// subscribers

	self.items.subscribe(function(items) {
		var pos = 1;

		items.forEach(function(item) {
			item.position(pos);
			pos++;
		});
	});

	// behaviours

	self.calculateSchedule = function(startIdx) {
		var start, i, len, items, item, scheduled, prev, date, dayOfYear;

		startIdx = startIdx || 0;
		items    = self.items();

		if (startIdx === 0) {
			start = scheduleStart.getTime();
		}
		else {
			start = items[startIdx].scheduled() + (items[startIdx].length() * 1000);
		}

		scheduled = start;
		prev      = null;

		for (i = startIdx, len = items.length; i < len; ++i) {
			item = items[i];

      if (optionsColumnId) {
        const columnId = 'col_' + optionsColumnId;
        const optionsValue = item[columnId]();

        if (optionsValue) {
          try {
            const { setup } = JSON.parse(optionsValue);

            if (setup) {
              const parsedSetup = ReadableTime.parse(setup);

              item.setupTime(parsedSetup)
            }
          } catch (ignored) {
            // We are being little shits and silently ignoring user errors
          }
        } else {
          item.setupTime(0);
        }
      }

			item.scheduled(scheduled);
			item.dateSwitch(false);

			date       = moment.unix(scheduled / 1000).utcOffset(scheduleTZ);
			dayOfYear  = date.dayOfYear();
			scheduled += ((item.length() + scheduleSetupTime + item.setupTime()) * 1000);

			if (prev !== null && prev !== dayOfYear) {
				item.dateSwitch(date.format('dddd, ll'));
			}

			prev = dayOfYear;
		}
	};

	self.add = function() {
		var data = {}, item;

		scheduleColumns.forEach(function(id) {
			data[id] = '';
		});

		item = new Item(-1, 30*60, data, self.items().length + 1);
		item.sync();

		self.items.push(item);
		$('.h-scheduler tbody:last a.editable:visible:first').editable('show');
	};

	self.move = function(itemID, newPos) {
		var item   = findItem(itemID);
		var data   = { item: itemID, position: newPos };
		var oldPos = item.position();

		// illegal move
		if (newPos < 1 || newPos > self.items().length) {
			return;
		}

		// Even if we don't actually move the item, we need to re-generate a fresh tbody element
		// because the old one was detached from the DOM during the dragging.

		var insertAt = newPos - 1; // -1 because splice() uses the internal, 0-based array

		self.items.remove(item);
		self.items.splice(insertAt, 0, item);

		// Now we can stop.

		if (oldPos == newPos) {
			return;
		}

		data[csrfTokenName] = csrfToken;

		item.busy(true);

		$.ajax({
			type: 'POST',
			url: '/-/schedules/' + scheduleID + '/items/move',
			dataType: 'json',
			contentType: 'application/json',
			data: JSON.stringify(data),
			complete: function() {
				item.busy(false);
			}
		});
	};

	self.initDragAndDrop = function() {
		nativesortable($('.h-scheduler')[0], {
			change: function(table, tbody) {
				var row    = $(tbody);
				var newPos = row.index() + 1;
				var itemID = row.data('itemid');

				// This is just the detached row that KO doesn't know anything about anymore.
				// The move() will take care of re-adding the moved row at the correct spot and
				// thereby trigger a fresh tbody element by KO.

				row.remove();
				self.move(itemID, newPos);
			}
		});
	};

	ko.computed(function() {
		self.calculateSchedule();
	});
}


	function Column(id, name, pos, hidden, fixed) {
	var self = this;

	// setup simple data properties

	self.id     = ko.observable(id);
	self.name   = ko.observable(name);
	self.hidden = ko.observable(hidden);
	self.fixed  = !!fixed;

	// setup properties for managing app state

	self.position  = ko.observable(parseInt(pos, 10));
	self.suspended = false;
	self.nextFocus = false;
	self.deleting  = ko.observable(false);
	self.busy      = ko.observable(false);
	self.errors    = ko.observable(false);

	// computed properties

	self.rowClass = ko.pureComputed(function() {
		if (self.busy()) {
			return 'warning';
		}

		if (self.errors()) {
			return 'danger h-has-errors';
		}

		if (self.deleting()) {
			return 'danger';
		}

		return '';
	}, self);

	self.bodyClass = function() {
		return 'h-column ' + (this.$context.$index() % 2 === 1 ? 'h-odd' : 'h-even');
	};

	self.deleteBtnClass = function() {
		return (self.fixed || self.id() === -1 || viewModel.isMinimal()) ? ' disabled' : '';
	};

	self.handleText = function() {
		return self.fixed ? '' : '::';
	};

	self.first = ko.pureComputed(function() {
		return self.position() <= 1;
	}, self);

	self.last = function() {
		return self.position() >= viewModel.numOfFlexibleColumns();
	};

	self.isOptionsColumn = function() {
		return self.name() === "[[options]]";
	};

	// subscribers

	function handleNameChange() {
		if (self.isOptionsColumn()) {
			self.suspended = true;
			self.hidden(true);
			self.suspended = false;
		}
	}

	function updateColumn() {
		if (self.suspended) {
			return;
		}

		var colID = self.id();
		var isNew = colID === -1;
		var url   = '/-/schedules/' + scheduleID + '/columns';

		if (self.fixed) {
			url += '/fixed';
		}

		if (!isNew) {
			url += '/' + colID + '?_method=PUT';
		}

		var data = {
			name: self.name(),
			hidden: self.hidden(),
		};

    if (data.name === '[[options]]') {
      data.hidden = true;
    }

		data[csrfTokenName] = csrfToken;

		self.busy(true);

		$.ajax({
			type: 'POST',
			url: url,
			dataType: 'json',
			contentType: 'application/json',
			data: JSON.stringify(data),
			success: function(result) {
				self.suspended = true;

				self.id(result.data.id);
				self.name(result.data.name);
				self.errors(false);

				self.suspended = false;

				if (self.nextFocus) {
					$('#h-add-model').focus();
					self.nextFocus = false;
				}
			},
			error: function(result) {
        console.log(result.responseJSON);
				self.errors(result.responseJSON.detail);
			},
			complete: function() {
				self.busy(false);
			}
		});
	}

	self.name.subscribe(handleNameChange);
	self.name.subscribe(updateColumn);
	self.hidden.subscribe(updateColumn);

	self.deleteColumn = function() {
		if (self.suspended) {
			return;
		}

		var colID = self.id();
		var data  = {};

		data[csrfTokenName] = csrfToken;

		self.busy(true);

		$.ajax({
			type: 'POST',
			url: '/-/schedules/' + scheduleID + '/columns/' + colID + '?_method=DELETE',
			dataType: 'json',
			contentType: 'application/json',
			data: JSON.stringify(data),
			success: function() {
				viewModel.columns.remove(self);
			},
      error: function(result) {
        self.errors({
          [colID]: [result.responseJSON.detail],
        });
      },
			complete: function() {
				self.busy(false);
			}
		});
	};

	// behaviours

	function move(event, direction) {
		var columnist = $(event.target).closest('table');
		var newPos    = self.position() + (direction === 'up' ? -1 : 1);

		viewModel.move(self.id(), newPos);

		// find the new DOM node for the just pressed button and focus it, if possible
		// (i.e. we're not first or last)
		var row = columnist.find('tbody[data-colid="' + self.id() + '"]');
		var btn = row.find('button.move-' + direction);

		if (btn.is('.disabled')) {
			btn = row.find('button.move-' + (direction === 'up' ? 'down' : 'up'));
		}

		btn.focus();
	}

	self.moveUp = function(col, event) {
		move(event, 'up');
	};

	self.moveDown = function(col, event) {
		move(event, 'down');
	};

	self.confirmDelete = function(item, event) {
		var parent = $(event.target).parent();
		self.deleting(true);
		parent.find('.btn-default').focus();
	};

	self.cancelDelete = function(item, event) {
		var parent = $(event.target).parent();
		self.deleting(false);
		parent.find('.btn-danger').focus();
	};

	self.doDelete = function() {
		self.deleteColumn();
	};

	self.onEditableHidden = function(event, reason) {
		var
			me      = $(this),
			root    = me.closest('table'),
			links   = root.find('a.editable:visible'),
			selfIdx = links.index(me),
			next    = (selfIdx < (links.length - 1)) ? $(links[selfIdx+1]) : $('#h-add-model');

		// advance to the next editable
		if (reason === 'save' || reason === 'nochange') {
			if (next.is('.editable')) {
				next.editable('show');
			}
			else {
				next.focus();

				// in case this saving triggers an ajax call to create the element,
				// the add button is still disabled right now. We set a flag to let
				// the success handler of the create call do the focussing.
				self.nextFocus = true;
			}
		}
		else {
			me.focus();
		}
	};
}

	function ColumnsViewModel(columns) {
	var self = this;

	self.columns = ko.observableArray(columns);

	// helper

	function findColumn(colID) {
		return findModelByID(self.columns(), colID);
	}

	// computed properties

	self.fixedColumns = ko.pureComputed(function() {
		return ko.utils.arrayFilter(self.columns(), function(col) {
			return col.fixed === true;
		});
	}, self);

	self.flexibleColumns = ko.pureComputed(function() {
		return ko.utils.arrayFilter(self.columns(), function(col) {
			return col.fixed === false;
		});
	}, self);

	self.hasNewColumn = ko.pureComputed(function() {
		return hasNewModel(self.columns());
	}, self);

	self.numOfFlexibleColumns = ko.pureComputed(function() {
		return self.flexibleColumns().length;
	}, self);

	self.numOfFixedColumns = ko.pureComputed(function() {
		return self.fixedColumns().length;
	}, self);

	// full only is a restriction on non-hidden columns, so remove hidden columns from the list
	self.isFull = ko.pureComputed(function() {
		return ko.utils.arrayFilter(self.columns(), function(col) {
			return col.fixed === false && !col.hidden();
		}).length >= 10;
	}, self);

	self.isMinimal = ko.pureComputed(function() {
		for (var acc = 0, i = 0, cols = self.columns(), len = cols.length; i < len; ++i) {
			if (cols[i].fixed === false && cols[i].id() !== -1) {
				acc++;
			}
		}

		return acc <= 1;
	});

	// subscribers

	self.columns.subscribe(function(columns) {
		var pos = 1;

		columns.forEach(function(col) {
			if (col.fixed === false) {
				col.position(pos);
				pos++;
			}
		});
	});

	// behaviours

	self.add = function() {
		var
			name = 'New Column',

			// if we're already at the limit, create a hidden column by default; the user cannot un-hide
			// it until another column is removed or marked as hidden; set the hidden flag here to not
			// cause TWO POST requests, with the first one possibly failing because it could create a
			// non-hidden column exceeding the limit
			hidden = self.isFull(),
			col    = new Column(-1, '', self.numOfFlexibleColumns() + 1, hidden, false);

		self.columns.push(col);
		col.name(name); // trigger storing the column immediately

		$('.h-columnist tbody:last a.editable:visible:first').editable('show');
	};

	self.move = function(columnID, newPos) {
		var col    = findColumn(columnID);
		var data   = { column: columnID, position: newPos };
		var oldPos = col.position();

		// illegal move
		if (newPos < 1 || newPos > self.numOfFlexibleColumns()) {
			return;
		}

		// Even if we don't actually move the column, we need to re-generate a fresh tbody element
		// because the old one was detached from the DOM during the dragging.

		var insertAt = newPos + self.numOfFixedColumns() - 1; // -1 because splice() uses the internal, 0-based array

		self.columns.remove(col);
		self.columns.splice(insertAt, 0, col);

		// Now we can stop.

		if (oldPos == newPos) {
			return;
		}

		data[csrfTokenName] = csrfToken;

		col.busy(true);

		$.ajax({
			type: 'POST',
			url: '/-/schedules/' + scheduleID + '/columns/move',
			dataType: 'json',
			contentType: 'application/json',
			data: JSON.stringify(data),
			complete: function() {
				col.busy(false);
			}
		});
	};

	self.initDragAndDrop = function() {
		nativesortable($('.h-columnist')[0], {
			change: function(table, tbody) {
				var row    = $(tbody);
				var newPos = row.index() + 1;
				var colID  = row.data('colid');

				// This is just the detached row that KO doesn't know anything about anymore.
				// The move() will take care of re-adding the moved row at the correct spot and
				// thereby trigger a fresh tbody element by KO.

				row.remove();
				self.move(colID, newPos);
			}
		});
	};
}


	var ui = $('body').data('ui');

	if (ui) {
		if (ui === 'scheduler') {
			var dataNode = $('.h-scheduler');
			var itemData = JSON.parse($('#h-item-data').text());

			scheduleID        = dataNode.data('id');
			scheduleColumns   = (''+dataNode.data('columns')).split(',');
			scheduleStart     = new Date(dataNode.data('start'));
			scheduleSetupTime = parseInt(dataNode.data('setuptime'), 10);
			scheduleTZ        = dataNode.data('tz');
			maxItems          = parseInt(dataNode.data('maxitems'), 10);
			items             = [];

			if (itemData) {
				itemData.forEach(function(item, idx) {
					items.push(new Item(item[0], item[1], item[2], idx + 1));
				});
			}

			viewModel = new ItemsViewModel(items);
		}
		else if (ui === 'columnist') {
			var dataNode = $('.h-columnist');
			var colData  = JSON.parse($('#h-column-data').text());

			scheduleID = dataNode.data('id');
			columns    = [];

			if (colData) {
				colData.forEach(function(column, idx) {
					columns.push(new Column(column[0], column[1], column[2], column[3], column[4]));
				});
			}

			viewModel = new ColumnsViewModel(columns);
		}

		if (viewModel) {
			var options = {
				attribute: 'data-bind',        // default "data-sbind"
				globals: window,               // default {}
				bindings: ko.bindingHandlers,  // default ko.bindingHandlers
				noVirtualElements: false       // default true
			};
			ko.bindingProvider.instance = new ko.secureBindingsProvider(options);

			ko.applyBindings(viewModel);
			viewModel.initDragAndDrop();
			$('#h-scheduler-loading').hide();
			$('#h-scheduler-container').show();

			// init spatial navigation (i.e. allow going up/down/left/right with array keys)
			new SpatialNavigation(dataNode);

			if (ui === 'scheduler') {
				// sync the table column widths the hard way
				setInterval(function() {
					mirrorColumnWidths(dataNode, $('tr:first > *', dataNode.prev()));
				}, 500);
			}
		}
	}

	var mdParser = new Remarkable('commonmark');
	mdParser.set({ html: false, xhtmlOut: false });

	$('.remarkable').each(function(i, textarea) {
		var timeout = null;

		textarea = $(textarea);

		function update(text) {
			var container = $('.remarkable-preview');

			container
				.html(mdParser.render(text))
				.find('img')
					.addClass('img-responsive')
					.attr('src', container.data('placeholder'))
					.attr('title', '(placeholder image by Casey Muir-Taylor, CC-BY)')
			;
		}

		textarea.on('keyup paste cut mouseup', function() {
			if (timeout) {
				clearTimeout(timeout);
				timeout = null;
			}

			timeout = setTimeout(function() {
				update(textarea.val());
			}, 300);
		});

		update(textarea.val());
	});
});
