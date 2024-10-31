export default (data, requiredFields) => {
    for (let i = 0; i < requiredFields.length; i++) {
        if (!data.hasOwnProperty(requiredFields[i]) || !data[requiredFields[i]]) {
            return false;
        }
    }
    return true;
}