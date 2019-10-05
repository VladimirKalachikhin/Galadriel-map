class Coordinates {
    constructor(coordinateString){
        this.coordinates = coordinateString;
        this.latitudeNumbers = null;
        this.longitudeNumbers = null;
        this.validate();
        this.parse();
    }


    validate(){
        const validator = new Validator;
        return validator.validate(this.coordinates);
    }


    parse() {
        this.groupCoordinateNumbers();
        this.latitude = this.extractLatitude();
        return this.longitude = this.extractLongitude();
    }


    groupCoordinateNumbers() {
        const coordinateNumbers = this.extractCoordinateNumbers(this.coordinates);
        const numberCountEachCoordinate = coordinateNumbers.length / 2;
        this.latitudeNumbers = coordinateNumbers.slice(0, numberCountEachCoordinate);
        return this.longitudeNumbers = coordinateNumbers.slice((0 - numberCountEachCoordinate));
    }


    extractCoordinateNumbers(coordinates) {
        return coordinates.match(/-?\d+(\.\d+)?/g);
    }


    extractLatitude() {
        let latitude = this.coordinateNumbersToDecimal(this.latitudeNumbers);
        if (this.latitudeIsNegative()) {
            latitude = latitude * -1;
        }
        return latitude;
    }


    extractLongitude() {
        let longitude = this.coordinateNumbersToDecimal(this.longitudeNumbers);
        if (this.longitudeIsNegative()) {
            longitude = longitude * -1;
        }
        return longitude;
    }


    coordinateNumbersToDecimal(coordinateNumbers) {
        const coordinate = new CoordinateNumber(coordinateNumbers);
        coordinate.detectSpecialFormats();
        const decimalCoordinate = coordinate.toDecimal();
        return decimalCoordinate;
    }


    latitudeIsNegative() {
         const isNegative = this.coordinates.match(/s/i);
         return isNegative;
     }


    longitudeIsNegative() {
         const isNegative = this.coordinates.match(/w/i);
         return isNegative;
     }


    getLatitude() {
        return this.latitude;
    }


    getLongitude() {
        return this.longitude;
    }
}

