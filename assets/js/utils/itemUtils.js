import $ from 'jquery';

export function parseLength(str) {
	const parts = str.split(':');

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

/**
 * @typedef {import('../../js/backend/Item.js')}
 *
 * @param {Item[]} models
 * @return {boolean}
 */
export function hasNewModel(models) {
	return models.filter((model) => model.id.value === -1).length > 0;
}

// this is cursed, but ICBA to rewrite it in plain js
export function mirrorColumnWidths(sourceTable, targets) {
	const sources = $('tr:first > *', sourceTable);

	for (let i = 0, len = sources.length; i < len; ++i) {
		const w = $(sources[i]).innerWidth();

		$(targets[i]).css({
			maxWidth: w,
			width: w
		});
	}
}
