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

export function hasNewModel(models) {
	return models.filter((model) => model.id === -1).length > 0;
}
