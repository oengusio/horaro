class ReadableTime {
  static #itemRegex = /(\d+)(hr|h|min|m|sec|s)/g;

  /**
   *
   * @param {string} input
   * @return {number}
   */
  static parse(input) {
    const string = input.trim();

    const matches = string.matchAll(this.#itemRegex).toArray();

    if (!matches.length) {
      throw new Error('This time string does not contain anything I can understand.');
    }

    let time = 0;

    for (const match of matches) {
      const amount = parseInt(match[1], 10);

      if (amount < 1) {
        continue;
      }

      switch (match[2]) {
        case 'h':
        case 'hr':
          time += amount * 3600;
          break;

        case 'm':
        case 'min':
          time += amount * 60;
          break;

        case 's':
        case 'sec':
          time += amount;
          break;
      }
    }

    if (time >= 24*3600) {
      time = 24*3600 - 1;
    }

    return time;
  }
}
