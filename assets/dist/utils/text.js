/*
 * Search in a text, as people type it: the letters without their accents, in lower case. Used by the command palette
 * and the list with search (combobox).
 */

/**
 * "Café" and "cafe" are the same word: the letters without their accents, in lower case, one for one (the position of
 * a match in the normalized text is the position in the text).
 *
 * @param {string} text
 * @returns {string[]}
 */
export function fold(text) {
    return Array.from(text, (character) => Array.from(character.normalize('NFD').replace(/\p{M}/gu, '').toLowerCase())[0] ?? character);
}

/**
 * Index of the first place where `words` (folded characters) appear in `folded`, -1 without.
 *
 * @param {string[]} folded
 * @param {string[]} words
 * @returns {number}
 */
export function find(folded, words) {
    for (let start = 0; start + words.length <= folded.length; start++) {
        if (words.every((character, offset) => folded[start + offset] === character)) {
            return start;
        }
    }

    return -1;
}

/**
 * The text with the words typed in <mark>, at their first place only: nodes, never a string of HTML.
 *
 * @param {string} text
 * @param {string[]} words folded characters
 * @returns {Node[]}
 */
export function marked(text, words) {
    const characters = Array.from(text);
    const start = words.length > 0 ? find(fold(text), words) : -1;
    if (start < 0) {
        return [document.createTextNode(text)];
    }
    const mark = document.createElement('mark');
    mark.textContent = characters.slice(start, start + words.length).join('');

    return [
        document.createTextNode(characters.slice(0, start).join('')),
        mark,
        document.createTextNode(characters.slice(start + words.length).join('')),
    ];
}
