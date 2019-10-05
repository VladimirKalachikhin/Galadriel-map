/*
 * decaffeinate suggestions:
 * DS102: Remove unnecessary code created because of implicit returns
 * Full docs: https://github.com/decaffeinate/decaffeinate/blob/master/docs/suggestions.md
 */
class CoordinateNumber {
    constructor(coordinateNumbers) {
        coordinateNumbers = this.normalizeCoordinateNumbers(coordinateNumbers);
        [this.degrees, this.minutes, this.seconds, this.milliseconds] = coordinateNumbers;
        this.sign = this.normalizedSignOf(this.degrees);
        this.degrees = Math.abs(this.degrees);
    }


    normalizeCoordinateNumbers(coordinateNumbers) {
        const normalizedNumbers = [0, 0, 0, 0];
        for (let i = 0; i < coordinateNumbers.length; i++) {
            const currentNumber = coordinateNumbers[i];
            normalizedNumbers[i] = parseFloat(currentNumber);
        }
        return normalizedNumbers;
    }

    normalizedSignOf(number) {
        if (number >= 0) { return 1; } else { return -1; }
    }

    detectSpecialFormats() {
        if (this.degreesCanBeSpecial()) {
            if (this.degreesCanBeMilliseconds()) {
                return this.degreesAsMilliseconds();
            } else if (this.degreesCanBeDegreesMinutesAndSeconds()) {
                return this.degreesAsDegreesMinutesAndSeconds();
            } else if (this.degreesCanBeDegreesAndMinutes()) {
                return this.degreesAsDegreesAndMinutes();
            }
        }
    }


    degreesCanBeSpecial() {
        let canBe = false;
        if (!this.minutes && !this.seconds) {
            canBe = true;
        }
        return canBe;
    }


    degreesCanBeMilliseconds() {
        let canBe;
        if (this.degrees > 909090) {
            canBe = true;
        } else {
            canBe = false;
        }
        return canBe;
    }


    degreesAsMilliseconds() {
        this.milliseconds = this.degrees;
        return this.degrees = 0;
    }


    degreesCanBeDegreesMinutesAndSeconds() {
        let canBe;
        if (this.degrees > 9090) {
            canBe = true;
        } else {
            canBe = false;
        }
        return canBe;
    }


    degreesAsDegreesMinutesAndSeconds() {
        const newDegrees = Math.floor(this.degrees / 10000);
        this.minutes = Math.floor((this.degrees - (newDegrees * 10000)) / 100);
        this.seconds = Math.floor(this.degrees - (newDegrees * 10000) - (this.minutes * 100));
        return this.degrees = newDegrees;
    }


    degreesCanBeDegreesAndMinutes() {
        let canBe;
        if (this.degrees > 360) {
            canBe = true;
        } else {
            canBe = false;
        }
        return canBe;
    }


    degreesAsDegreesAndMinutes() {
        const newDegrees = Math.floor(this.degrees / 100);
        this.minutes = this.degrees - (newDegrees * 100);
        return this.degrees = newDegrees;
    }


    toDecimal() {
        const decimalCoordinate = this.sign * (this.degrees + (this.minutes / 60) + (this.seconds / 3600) + (this.milliseconds / 3600000));
        return decimalCoordinate;
    }
}

