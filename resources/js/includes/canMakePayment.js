export default function (total_limitation, total) {
    let min = 0;
    let max = 0;

    if (total_limitation.max) {
        max = total_limitation.max * 100;
    }
    if (total_limitation.min) {
        min = total_limitation.min * 100;
    }

    min = total >= min;
    max = (max === 0) || (total <= max);

    return min && max;
}
