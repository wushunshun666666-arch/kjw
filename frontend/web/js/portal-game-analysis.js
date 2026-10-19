/** Read the selected measure from the stored draw without reclassifying game rules. */
export function analysisValue(row, measure) {
    return measure.field === 'balls' ? row.balls[measure.position] : row[measure.field];
}

/** Use one result per draw and the actual selected sample size for all frequency tables. */
export function analysisCounts(rows, measure) {
    return measure.values.map(value => {
        const count = rows.filter(row => analysisValue(row, measure) === value).length;
        return {value, count, percent: rows.length ? (count * 100 / rows.length).toFixed(1) : '0.0'};
    });
}
