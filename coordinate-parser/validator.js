/*
 * decaffeinate suggestions:
 * DS102: Remove unnecessary code created because of implicit returns
 * Full docs: https://github.com/decaffeinate/decaffeinate/blob/master/docs/suggestions.md
 */
class Validator {
    isValid(coordinates) {
        let isValid = true;
        try {
            this.validate(coordinates);
            return isValid;
        } catch (validationError) {
            isValid = false;
            return isValid;
        }
    }


    validate(coordinates) {
        this.checkContainsNoLetters(coordinates);
        this.checkValidOrientation(coordinates);
        return this.checkNumbers(coordinates);
    }


    checkContainsNoLetters(coordinates) {
        const containsLetters = /(?![neswd])[a-z]/i.test(coordinates);
        if (containsLetters) {
            throw new Error('Coordinate contains invalid alphanumeric characters.');
        }
    }


    checkValidOrientation(coordinates) {
        const validOrientation = /^[^nsew]*[ns]?[^nsew]*[ew]?[^nsew]*$/i.test(coordinates);
        if (!validOrientation) {
            throw new Error('Invalid cardinal direction.');
        }
    }


    checkNumbers(coordinates) {
        const coordinateNumbers = coordinates.match(/-?\d+(\.\d+)?/g);
        this.checkAnyCoordinateNumbers(coordinateNumbers);
        this.checkEvenCoordinateNumbers(coordinateNumbers);
        return this.checkMaximumCoordinateNumbers(coordinateNumbers);
    }


    checkAnyCoordinateNumbers(coordinateNumbers) {
        if (coordinateNumbers.length === 0) {
            throw new Error('Could not find any coordinate number');
        }
    }


    checkEvenCoordinateNumbers(coordinateNumbers) {
        const isUnevenNumbers = coordinateNumbers.length % 2;
        if (isUnevenNumbers) {
            throw new Error('Uneven count of latitude/longitude numbers');
        }
    }


    checkMaximumCoordinateNumbers(coordinateNumbers) {
        if (coordinateNumbers.length > 6) {
            throw new Error('Too many coordinate numbers');
        }
    }
}


